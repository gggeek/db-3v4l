<?php

namespace Db3v4l\Command;

use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Db3v4l\Core\DatabaseSchemaManager;
use Db3v4l\Service\DatabaseConfigurationManager;
use Db3v4l\Service\ProcessManager;
use Db3v4l\Service\SqlExecutorFactory;
use Symfony\Component\Yaml\Yaml;

abstract class DatabaseManagingCommand extends BaseCommand
{
    /** @var DatabaseConfigurationManager $dbManager */
    protected $dbManager;
    /** @var SqlExecutorFactory $executorFactory */
    protected $executorFactory;
    protected $processManager;

    const DEFAULT_PARALLEL_PROCESSES = 16;
    const DEFAULT_PROCESS_TIMEOUT = 600;
    const DEFAULT_OUTPUT_FORMAT = 'text';

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

    protected function addCommonOptions()
    {
        $this
            ->addOption('output-type', null, InputOption::VALUE_REQUIRED, 'The format for the output: json, php, text or yml', self::DEFAULT_OUTPUT_FORMAT)
            ->addOption('timeout', null, InputOption::VALUE_REQUIRED, 'The maximum time to wait for execution (secs)', self::DEFAULT_PROCESS_TIMEOUT)
            ->addOption('max-parallel', null, InputOption::VALUE_REQUIRED, 'The maximum number of processes to run in parallel', self::DEFAULT_PARALLEL_PROCESSES)
            ->addOption('dont-force-enabled-sigchild', null, InputOption::VALUE_NONE, "When using a separate php process to run each sql command, do not force Symfony to believe that php was compiled with --enable-sigchild option");
    }

    /**
     * @param array[] $dbSpecList key: db name (as used to identify configured databases), value: array('user': mandatory, 'dbname': optional, if unspecified assumed same as user)
     * @param int $maxParallel
     * @param int $timeout
     * @return array 'succeeded': int, 'failed': int, 'results': same format as dbManager::getDatabaseConnectionSpecification
     */
    protected function createDatabases($dbSpecList, $maxParallel = self::DEFAULT_PARALLEL_PROCESSES, $timeout = self::DEFAULT_PROCESS_TIMEOUT)
    {
        $processes = [];
        $connectionSpecs = [];
        $tempSQLFileNames = [];

        foreach ($dbSpecList as $instanceName => $dbConnectionSpec) {
            $rootDbConnectionSpec = $this->dbManager->getDatabaseConnectionSpecification($instanceName);

            $schemaManager = new DatabaseSchemaManager($rootDbConnectionSpec);
            $sql = $schemaManager->getCreateDatabaseSQL(
                $dbConnectionSpec['user'],
                $dbConnectionSpec['password'],
                (isset($dbConnectionSpec['dbname']) && $dbConnectionSpec['dbname'] != '') ? $dbConnectionSpec['dbname'] : null
            );
            // sadly, psql does not allow to create a db and a user using a multiple-sql-commands string,
            // and we have to resort to using temp files
            /// @todo can we make this safer? Ideally the new userv name and pwd should neither hit disk nor the process list...
            $tempSQLFileName = tempnam(sys_get_temp_dir(), 'db3v4l_');
            file_put_contents($tempSQLFileName, $sql);
            $tempSQLFileNames[] = $tempSQLFileName;

            $executor = $this->executorFactory->createForkedExecutor($rootDbConnectionSpec, 'NativeClient', false);
            $process = $executor->getExecuteFileProcess($tempSQLFileName);

            $process->setTimeout($timeout);

            $processes[$instanceName] = $process;
            $connectionSpecs[$instanceName] = $rootDbConnectionSpec;
        }

        $this->processManager->runParallel($processes, $maxParallel, 100);

        $succeeded = 0;
        $failed = 0;
        $results = array();
        foreach ($processes as $instanceName => $process) {
            if ($process->isSuccessful()) {
                $results[$instanceName] = array_merge($connectionSpecs[$instanceName], array(
                    'user' => $dbConnectionSpec['user'],
                    'password' => $dbConnectionSpec['password'],
                    'dbname' => (isset($dbConnectionSpec['dbname']) && $dbConnectionSpec['dbname'] != '') ? $dbConnectionSpec['dbname'] : $dbConnectionSpec['user']
                ));
                $succeeded++;
            } else {
                $failed++;
                $this->writeErrorln("\n<error>Creation of new database & user on instance '$instanceName' failed! Reason: " . $process->getErrorOutput() . "</error>\n", OutputInterface::VERBOSITY_NORMAL);
            }
        }

        foreach($tempSQLFileNames as $tempSQLFileName) {
            unlink($tempSQLFileName);
        }

        return [
            'succeeded' => $succeeded,
            'failed' => $failed,
            'data' => $results
        ];
    }

    /**
     * @param array[] $dbSpecList key: db name (as used to identify configured databases), value: array('user': mandatory, 'dbname': optional, if unspecified assumed same as user)
     * @param int $maxParallel
     * @param int $timeout
     * @return array 'succeeded': int, 'failed': int
     */
    protected function dropDatabases($dbSpecList, $maxParallel = self::DEFAULT_PARALLEL_PROCESSES, $timeout = self::DEFAULT_PROCESS_TIMEOUT)
    {
        $processes = [];
        $tempSQLFileNames = [];

        foreach ($dbSpecList as $instanceName => $dbConnectionSpec) {
            $rootDbConnectionSpec = $this->dbManager->getDatabaseConnectionSpecification($instanceName);

            $schemaManager = new DatabaseSchemaManager($rootDbConnectionSpec);
            $sql = $schemaManager->getDropDatabaseQL(
                $dbConnectionSpec['user'],
                (isset($dbConnectionSpec['dbname']) && $dbConnectionSpec['dbname'] != '') ? $dbConnectionSpec['dbname'] : null
            );
            $tempSQLFileName = tempnam(sys_get_temp_dir(), 'db3v4l_');
            file_put_contents($tempSQLFileName, $sql);
            $tempSQLFileNames[] = $tempSQLFileName;

            $executor = $this->executorFactory->createForkedExecutor($rootDbConnectionSpec, 'NativeClient', false);
            $process = $executor->getExecuteFileProcess($tempSQLFileName);

            $process->setTimeout($timeout);

            $processes[$instanceName] = $process;
        }

        $this->processManager->runParallel($processes, $maxParallel, 100);

        $succeeded = 0;
        $failed = 0;
        foreach ($processes as $instanceName => $process) {
            if ($process->isSuccessful()) {
                $succeeded++;
            } else {
                $failed++;
                $this->writeErrorln("\n<error>Drop of new database & user on instance '$instanceName' failed! Reason: " . $process->getErrorOutput() . "</error>\n", OutputInterface::VERBOSITY_NORMAL);
            }
        }

        foreach($tempSQLFileNames as $tempSQLFileName) {
            unlink($tempSQLFileName);
        }

        return [
            'succeeded' => $succeeded,
            'failed' => $failed,
            'data' => null
        ];
    }

    /**
     * @param array $results
     * @param float $time
     * @param string $format
     */
    protected function writeResults(array $results, $time, $format = 'text')
    {
        switch ($format) {
            case 'json':
                $data = json_encode($results['data'], JSON_PRETTY_PRINT);
                break;
            case 'php':
                $data = var_export($results['data'], true);
                break;
            case 'text':
            case 'yml':
            case 'yaml':
                $data = Yaml::dump($results['data'], 2, 4, Yaml::DUMP_MULTI_LINE_LITERAL_BLOCK);
                break;
            default:
                throw new \Exception("Unsupported output format: '$format'");
                break;
        }
        $this->writeln($data, OutputInterface::VERBOSITY_QUIET,  OutputInterface::OUTPUT_RAW);

        if ($format === 'text') {
            $this->writeln($results['succeeded'] . ' succeeded, ' . $results['failed'] . ' failed');

            // since we use subprocesses, we can not measure max memory used
            $this->writeln("<info>Time taken: ".sprintf('%.2f', $time)." secs</info>");
        }
    }

}