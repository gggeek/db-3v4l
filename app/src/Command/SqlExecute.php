<?php

namespace Db3v4l\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Yaml\Yaml;
use Db3v4l\API\Interfaces\TimedExecutor;
use Db3v4l\Service\DatabaseConfigurationManager;
use Db3v4l\Core\DatabaseSchemaManager;
use Db3v4l\Service\SqlExecutorFactory;
use Db3v4l\Service\ProcessManager;
use Db3v4l\Util\Process;

class SqlExecute extends BaseCommand
{
    protected static $defaultName = 'db3v4l:sql:execute';

    /** @var DatabaseConfigurationManager $dbManager */
    protected $dbManager;
    /** @var SqlExecutorFactory $executorFactory */
    protected $executorFactory;
    protected $processManager;

    public function __construct(
        DatabaseConfigurationManager $dbManager,
        SqlExecutorFactory $executorFactory,
        ProcessManager $processManager)
    {
        $this->dbManager = $dbManager;
        $this->executorFactory = $executorFactory;
        $this->processManager = $processManager;

        parent::__construct();
    }

    protected function configure()
    {
        $this
            ->setDescription('Executes an SQL command in parallel on all configured database servers, creating a dedicated database schema (and user)')
            ->addOption('sql', null, InputOption::VALUE_REQUIRED, 'The sql command(s) string to execute')
            ->addOption('file', null, InputOption::VALUE_REQUIRED, 'A file with sql commands to execute')
            ->addOption('output-type', null, InputOption::VALUE_REQUIRED, 'The format for the output: json, php, text or yml', 'text')
            ->addOption('timeout', null, InputOption::VALUE_REQUIRED, 'The maximum time to wait for execution (secs)', 600)
            ->addOption('max-parallel', null, InputOption::VALUE_REQUIRED, 'The maximum number of processes to run in parallel', 16)
            ->addOption('dont-force-enabled-sigchild', null, InputOption::VALUE_NONE, "When using a separate php process to run each sql command, do not force Symfony to believe that php was compiled with --enable-sigchild option")
        ;
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int|void|null
     * @throws \Exception
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $start = microtime(true);

        // as per https://www.php.net/manual/en/function.ignore-user-abort.php: for cli scripts, it is probably a good idea
        // to use ignore_user_abort
        ignore_user_abort(true);

        $this->setOutput($output);
        $this->setVerbosity($output->getVerbosity());

        $dbList = $this->dbManager->listDatabases();
        $sql = $input->getOption('sql');
        $file = $input->getOption('file');
        $timeout = $input->getOption('timeout');
        $maxParallel = $input->getOption('max-parallel');
        $dontForceSigchildEnabled = $input->getOption('dont-force-enabled-sigchild');
        $format = $input->getOption('output-type');

        if ($sql == null && $file == null) {
            throw new \Exception("Please provide an sql command/file to be executed");
        }
        if ($sql != null && $file != null) {
            throw new \Exception("Please provide either an sql command or file to be executed, not both");
        }

        // On Debian, which we use by default, SF has troubles understanding that php was compiled with --enable-sigchild
        // We thus force it, but give end users an option to disable this
        // For more details, see comment 12 at https://bugs.launchpad.net/ubuntu/+source/php5/+bug/516061
        if (!$dontForceSigchildEnabled) {
            Process::forceSigchildEnabled(true);
        }

        if ($format === 'text') {
            $this->writeln('<info>Creating temporary schemas...</info>', OutputInterface::VERBOSITY_VERBOSE);
        }

        $dbConnectionSpecs = $this->createSchemas($dbList, $maxParallel);

        if ($format === 'text') {
            $this->writeln('<info>Preparing commands...</info>', OutputInterface::VERBOSITY_VERBOSE);
        }

        /** @var Process[] $processes */
        $processes = [];
        $executors = [];
        foreach ($dbConnectionSpecs as $dbName => $dbConnectionSpec) {

            $executor = $this->executorFactory->createForkedExecutor($dbConnectionSpec);

            if ($sql != null) {
                $process = $executor->getExecuteCommandProcess($sql);
            } else {
                $process = $executor->getExecuteFileProcess($file);
            }

            if ($format === 'text') {
                $this->writeln('Command line: ' . $process->getCommandLine(), OutputInterface::VERBOSITY_VERBOSE);
            }

            $process->setTimeout($timeout);

            $executors[$dbName] = $executor;
            $processes[$dbName] = $process;
        }

        if ($format === 'text') {
            $this->writeln('<info>Starting parallel execution...</info>');
        }

        $this->processManager->runParallel($processes, $maxParallel, 100, array($this, 'onSubProcessOutput'));

        $failed = 0;
        $succeeded = 0;
        $results = array();
        foreach ($processes as $dbName => $process) {
            $results[$dbName] = array(
                'stdout' => rtrim($process->getOutput()),
                'stderr' => trim($process->getErrorOutput()),
                'exitcode' => $process->getExitCode()
            );

            if ($executors[$dbName] instanceof TimedExecutor) {
                $timingData = $executors[$dbName]->getTimingData();
                $results[$dbName] = array_merge($results[$dbName], $timingData);
            }

            if ($process->isSuccessful()) {
                $succeeded++;
            } else {
                $this->writeErrorln("\n<error>Execution on database '$dbName' failed! Reason: " . $process->getErrorOutput() . "</error>\n", OutputInterface::VERBOSITY_NORMAL);
                $failed++;
            }
        }

        if ($format === 'text') {
            $this->writeln('<info>Dropping temporary schemas...</info>', OutputInterface::VERBOSITY_VERBOSE);
        }
        $this->dropSchemas($dbConnectionSpecs, $maxParallel);

        $time = microtime(true) - $start;

        $this->writeResults($results, $succeeded, $failed, $time, $format);
    }

    /**
     * @param string[] $dbList
     * @param int $maxParallel
     * @return array same format as dbManager::getDatabaseConnectionSpecification
     */
    protected function createSchemas($dbList, $maxParallel)
    {
        $processes = [];
        $connectionSpecs = [];
        $tempSQLFileNames = [];

        /// @todo inject more randomness in the username, by allowing more chars than bin2hex produces
        $userName = bin2hex(random_bytes(8)); // old mysql versions have a limitation of 16 chars for usernames
        $password = bin2hex(random_bytes(16));
        //$schemaName = bin2hex(random_bytes(31));
        $schemaName = null; // $userName will be used as schema name

        foreach ($dbList as $dbName) {
            $rootDbConnectionSpec = $this->dbManager->getDatabaseConnectionSpecification($dbName);

            $schemaManager = new DatabaseSchemaManager($rootDbConnectionSpec);
            $sql = $schemaManager->getCreateSchemaSQL($userName, $password, $schemaName);
            // sadly, psql does not allow to create a db and a user using a multiple-sql-commands string,
            // and we have to resort to using temp files
            /// @todo can we make this safer? Ideally the new userv name and pwd should neither hit disk nor the process list...
            $tempSQLFileName = tempnam(sys_get_temp_dir(), 'db3val_');
            file_put_contents($tempSQLFileName, $sql);
            $tempSQLFileNames[] = $tempSQLFileName;

            $executor = $this->executorFactory->createForkedExecutor($rootDbConnectionSpec, 'NativeClient', false);
            $process = $executor->getExecuteFileProcess($tempSQLFileName);
            $processes[$dbName] = $process;
            $connectionSpecs[$dbName] = $rootDbConnectionSpec;
        }

        $this->processManager->runParallel($processes, $maxParallel, 100);

        $results = array();
        foreach ($processes as $dbName => $process) {
            if ($process->isSuccessful()) {
                $results[$dbName] = array_merge($connectionSpecs[$dbName], array(
                    'user' => $userName,
                    'password' => $password,
                    'dbname' => $userName
                ));
            } else {
                $this->writeErrorln("\n<error>Creation of new schema & user on database '$dbName' failed! Reason: " . $process->getErrorOutput() . "</error>\n", OutputInterface::VERBOSITY_NORMAL);
            }
        }

        foreach($tempSQLFileNames as $tempSQLFileName) {
            unlink($tempSQLFileName);
        }

        return $results;
    }

    /**
     * @param array $dbSpecList
     * @param int $maxParallel
     */
    protected function dropSchemas($dbSpecList, $maxParallel)
    {
        $processes = [];
        $tempSQLFileNames = [];

        foreach ($dbSpecList as $dbName => $dbConnectionSpec) {
            $rootDbConnectionSpec = $this->dbManager->getDatabaseConnectionSpecification($dbName);

            $schemaManager = new DatabaseSchemaManager($rootDbConnectionSpec);
            $sql= $schemaManager->getDropSchemaSQL($dbConnectionSpec['user'], isset($dbConnectionSpec['dbname']) ? $dbConnectionSpec['dbname'] : null );
            $tempSQLFileName = tempnam(sys_get_temp_dir(), 'db3val_');
            file_put_contents($tempSQLFileName, $sql);
            $tempSQLFileNames[] = $tempSQLFileName;

            $executor = $this->executorFactory->createForkedExecutor($rootDbConnectionSpec, 'NativeClient', false);
            $process = $executor->getExecuteFileProcess($tempSQLFileName);
            $processes[$dbName] = $process;
        }

        $this->processManager->runParallel($processes, $maxParallel, 100);

        foreach ($processes as $dbName => $process) {
            if (!$process->isSuccessful()) {
                $this->writeErrorln("\n<error>Drop of new schema & user on database '$dbName' failed! Reason: " . $process->getErrorOutput() . "</error>\n", OutputInterface::VERBOSITY_NORMAL);
            }
        }

        foreach($tempSQLFileNames as $tempSQLFileName) {
            unlink($tempSQLFileName);
        }
    }

    /**
     * @param array $results
     * @param int $succeeded
     * @param int $failed
     * @param float $time
     * @param string $format
     */
    protected function writeResults(array $results, $succeeded, $failed, $time, $format = 'text')
    {
        switch ($format) {
            case 'json':
                $results = json_encode($results, JSON_PRETTY_PRINT);
                break;
            case 'php':
                $results = var_export($results, true);
                break;
            case 'text':
            case 'yml':
            case 'yaml':
                $results = Yaml::dump($results, 2, 4, Yaml::DUMP_MULTI_LINE_LITERAL_BLOCK);
                break;
            default:
                throw new \Exception("Unsupported output format: '$format'");
                break;
        }
        $this->writeln($results, OutputInterface::VERBOSITY_QUIET,  OutputInterface::OUTPUT_RAW);

        if ($format === 'text') {
            $this->writeln($succeeded . ' succeeded, ' . $failed . ' failed');

            // since we use subprocesses, we can not measure max memory used
            $this->writeln("<info>Time taken: ".sprintf('%.2f', $time)." secs</info>");
        }
    }

    /**
     * @param string $type
     * @param string $buffer
     * @param string $processIndex;
     * @param Process $process
     */
    public function onSubProcessOutput($type, $buffer, $processIndex, $process=null)
    {
        $lines = explode("\n", trim($buffer));

        foreach ($lines as $line) {
            // we tag the output from the different processes
            if (trim($line) !== '') {
                if ($type === 'err') {
                    $this->writeErrorln(
                        '[' . $processIndex . '][' . ($process ? $process->getPid() : '') . '] ' . trim($line),
                        OutputInterface::VERBOSITY_VERBOSE,
                        OutputInterface::OUTPUT_RAW
                    );
                } else {
                    $this->writeln(
                        '[' . $processIndex . '][' . ($process ? $process->getPid() : '') . '] ' . trim($line),
                        OutputInterface::VERBOSITY_VERBOSE,
                        OutputInterface::OUTPUT_RAW
                    );
                }
            }
        }
    }
}
