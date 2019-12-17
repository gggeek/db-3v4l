<?php

namespace Db3v4l\Command;

use Db3v4l\Core\DatabaseSchemaManager;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputOption;
use Db3v4l\API\Interfaces\TimedExecutor;
use Db3v4l\Util\Process;

class SqlExecute extends DatabaseManagingCommand
{
    protected static $defaultName = 'db3v4l:sql:execute';

    protected function configure()
    {
        $this
            ->setDescription('Executes an SQL command in parallel on all configured database instances, by default in a dedicated temporary database/user')
            ->addOption('sql', null, InputOption::VALUE_REQUIRED, 'The sql command(s) string to execute')
            ->addOption('file', null, InputOption::VALUE_REQUIRED, "A file with sql commands to execute. The tokens '{dbtype}' and '{instancename}' will be replaced with actual values")

            ->addOption('database', null, InputOption::VALUE_REQUIRED, 'The name of an existing the database to use. If omitted a dedicated temporary database will be created on the fly and disposed after use')
            ->addOption('user', null, InputOption::VALUE_REQUIRED, 'The name of the user to use for connecting to the existing database. Temporary databases get created with a temp user and random password')
            ->addOption('password', null, InputOption::VALUE_REQUIRED, 'The user password')
            ->addOption('charset', null, InputOption::VALUE_REQUIRED, 'The collation/character-set to use, _only_ for the dedicated temporary database. If omitted, the default collation for the instance will be used')

            ->addCommonOptions()
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

        $instanceList = $this->parseCommonOptions($input);

        $sql = $input->getOption('sql');
        $file = $input->getOption('file');
        $dbName = $input->getOption('database');
        $userName = $input->getOption('user');
        $password = $input->getOption('password');

        if ($sql == null && $file == null) {
            throw new \Exception("Please provide an sql command or file to be executed");
        }
        if ($sql != null && $file != null) {
            throw new \Exception("Please provide either an sql command or file to be executed, not both");
        }

        if (($dbName == null) xor ($userName == null)) {

            throw new \Exception("Please provide both a custom database name and associated user account");
        }

        $createDB = ($dbName == null);

        if ($createDB) {
            // create temp databases

            if ($this->outputFormat === 'text') {
                $this->writeln('<info>Creating temporary databases...</info>', OutputInterface::VERBOSITY_VERBOSE);
            }

            /// @todo inject more randomness in the username, by allowing more chars than bin2hex produces
            $userName = 'db3v4l_' . substr(bin2hex(random_bytes(5)), 0, 9); // some mysql versions have a limitation of 16 chars for usernames
            $password = bin2hex(random_bytes(16));
            //$dbName = bin2hex(random_bytes(31));
            $dbName = null; // $userName will be used as db name

            $tempDbSpecs = [];
            foreach($instanceList as $instanceName => $instanceSpecs) {
                $tempDbSpecs[$instanceName] = [
                    'user' => $userName,
                    'password' => $password,
                    'dbname' => $dbName
                ];
            }

            if (($charset = $input->getOption('charset')) != '') {
                foreach($instanceList as $instanceName) {
                    $tempDbSpecs[$instanceName]['charset'] = $charset;
                }
            }

            $creationResults = $this->createDatabases($instanceList, $tempDbSpecs);
            $dbConnectionSpecs = $creationResults['data'];

        } else {
            // use existing databases

            if ($this->outputFormat === 'text') {
                $this->writeln('<info>Using existing databases...</info>', OutputInterface::VERBOSITY_VERBOSE);
            }

            $dbConfig = [
                'user' => $userName,
                'password' => $password,
                'dbname' => $dbName,
            ];

            $dbConnectionSpecs = [];
            foreach($instanceList as $instanceName => $instanceSpecs) {
                $dbConnectionSpecs[$instanceName] = array_merge(
                    //$this->dbManager->getConnectionSpecification($instanceName), $dbConfig
                    $instanceSpecs, $dbConfig
                );
            }
        }

        if (count($dbConnectionSpecs)) {
            $results = $this->executeSQL($dbConnectionSpecs, $sql, $file);

            if ($this->outputFormat === 'text') {
                $this->writeln('<info>Dropping temporary databases...</info>', OutputInterface::VERBOSITY_VERBOSE);
            }

            if ($createDB) {
                $results['failed'] += $creationResults['failed'];

                foreach($instanceList as $instanceName  => $instanceSpecs) {
                    if (!isset($dbConnectionSpecs[$instanceName])) {
                        unset($instanceList[$instanceName]);
                    }
                }
                $this->dropDatabases($instanceList, $dbConnectionSpecs);
            }
        } else {
            $results = ['succeeded' => 0,  'failed' => 0, 'data' => null];
        }

        $time = microtime(true) - $start;

        $this->writeResults($results, $time);

        return (int)$results['failed'];
    }

    /**
     * @param array $dbConnectionSpecs
     * @param string $sql
     * @param string $fileName
     * @param string $format
     * @param int $maxParallel
     * @param int $timeout
     * @return array
     *
     * @todo check output in verbose and very-verbose mode and compare it to before refactoring
     */
    protected function executeSQL(array $dbConnectionSpecs, $sql, $fileName = null)
    {
        return $this->executeSqlAction(
            $dbConnectionSpecs,
            'Execution of SQL',
            function ($schemaManager, $instanceName) use ($sql, $fileName) {
                /** @var DatabaseSchemaManager $schemaManager */
                if ($sql != null) {
                    return $schemaManager->getExecuteSqlCommandAction($sql);
                } else {
                    $realFileName = $this->replaceDBSpecTokens($fileName, $instanceName, $schemaManager->getDatabaseConfiguration());
                    if (!is_file($realFileName)) {
                        throw new \RuntimeException("Can not find sql file for execution: '$realFileName'");
                    }
                    return $schemaManager->getExecuteSqlFileAction($realFileName);
                }
            },
            array($this, 'onSubProcessOutput')
        );

        /*if ($this->outputFormat === 'text') {
            $this->writeln('<info>Preparing commands...</info>', OutputInterface::VERBOSITY_VERBOSE);
        }

        /** @var Process[] $processes * /
        $processes = [];
        $executors = [];
        $filePattern = $file;
        foreach ($dbConnectionSpecs as $instanceName => $dbConnectionSpec) {

            $executor = $this->executorFactory->createForkedExecutor($dbConnectionSpec);

            if ($sql != null) {
                $process = $executor->getExecuteStatementProcess($sql);
            } else {
                $file = $this->replaceDBSpecTokens($filePattern, $instanceName, $dbConnectionSpec);
                if (!is_file($file)) {
                    throw new \RuntimeException("Can not find sql file for execution: '$file'");
                }
                $process = $executor->getExecuteFileProcess($file);
            }

            if ($this->outputFormat === 'text') {
                $this->writeln('Command line: ' . $process->getCommandLine(), OutputInterface::VERBOSITY_VERBOSE);
            }

            $process->setTimeout($timeout);

            $executors[$instanceName] = $executor;
            $processes[$instanceName] = $process;
        }

        if ($this->outputFormat === 'text') {
            $this->writeln('<info>Starting parallel execution...</info>');
        }

        $this->processManager->runParallel($processes, $maxParallel, 100, array($this, 'onSubProcessOutput'));

        $succeeded = 0;
        $failed = 0;
        $results = array();
        foreach ($processes as $instanceName => $process) {
            $results[$instanceName] = array(
                'stdout' => rtrim($process->getOutput()),
                'stderr' => trim($process->getErrorOutput()),
                'exitcode' => $process->getExitCode()
            );

            if ($executors[$instanceName] instanceof TimedExecutor) {
                $timingData = $executors[$instanceName]->getTimingData();
                $results[$instanceName] = array_merge($results[$instanceName], $timingData);
            }

            if ($process->isSuccessful()) {
                $succeeded++;
            } else {
                $this->writeErrorln("\n<error>Execution on database '$instanceName' failed! Reason: " . $process->getErrorOutput() . "</error>\n", OutputInterface::VERBOSITY_NORMAL);
                $failed++;
            }
        }

        return [
            'succeeded' => $succeeded,
            'failed' => $failed,
            'data' => $results
        ];*/
    }

    /**
     * @param string $type
     * @param string $buffer
     * @param string $processIndex;
     * @param Process $process
     */
    public function onSubProcessOutput($type, $buffer, $processIndex, $process = null)
    {
        if (is_object($processIndex) && $process === null) {
            /// @todo php bug ? investigate deeper...
            $process = $processIndex;
            $processIndex = '?';
        }

        $pid = is_object($process) ? $process->getPid() : '';

        $lines = explode("\n", trim($buffer));

        foreach ($lines as $line) {
            // we tag the output from the different processes
            if (trim($line) !== '') {
                if ($type === 'err') {
                    $this->writeErrorln(
                        '[' . $processIndex . '][' . $pid . '] ' . trim($line),
                        OutputInterface::VERBOSITY_VERBOSE,
                        OutputInterface::OUTPUT_RAW
                    );
                } else {
                    $this->writeln(
                        '[' . $processIndex . '][' . $pid . '] ' . trim($line),
                        OutputInterface::VERBOSITY_VERBOSE,
                        OutputInterface::OUTPUT_RAW
                    );
                }
            }
        }
    }

    /**
     * Replaces tokens
     * @param string $string the original string
     * @param string $instanceName
     * @param string[] $dbConnectionSpec
     * @return string
     * @todo add support for more tokens, eg '{version}'
     */
    protected function replaceDBSpecTokens($string, $instanceName, $dbConnectionSpec)
    {
        $dbType = $dbConnectionSpec['vendor'];
        return str_replace(
            array('{dbtype}', '{instancename}', '{vendor}'),
            array($dbType, $instanceName, $dbConnectionSpec['vendor']),
            $string
        );
    }
}
