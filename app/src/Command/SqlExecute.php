<?php

namespace Db3v4l\Command;

use Db3v4l\Core\DatabaseSchemaManager;
use Db3v4l\Core\SqlAction\Command;
use Db3v4l\Core\SqlAction\File;
use Db3v4l\Core\SqlExecutor\Forked\NativeClient;
use Db3v4l\Core\SqlExecutor\Forked\TimedExecutor;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputOption;
use Db3v4l\Util\Process;

class SqlExecute extends DatabaseManagingCommand
{
    protected static $defaultName = 'db3v4l:sql:execute';

    protected function configure()
    {
        $this
            ->setDescription('Executes an SQL command in parallel on all configured database instances, by default in a dedicated temporary database/user')
            ->addOption('sql', 's', InputOption::VALUE_REQUIRED, 'The sql command(s) string to execute')
            ->addOption('file', null, InputOption::VALUE_REQUIRED, "A file with sql commands to execute. The tokens '{dbtype}' and '{instancename}' will be replaced with actual values")

            ->addOption('database', 'd', InputOption::VALUE_REQUIRED, 'The name of an existing database to use. If omitted, a dedicated temporary database will be created on the fly and disposed after use')
            ->addOption('user', 'u', InputOption::VALUE_REQUIRED, 'The name of the user to use for connecting to the existing database. Temporary databases get created with a temp user and random password')
            ->addOption('password', 'p', InputOption::VALUE_REQUIRED, 'The user password')
            ->addOption('charset', 'c', InputOption::VALUE_REQUIRED, 'The collation/character-set to use, _only_ for the dedicated temporary database. If omitted, the default collation for the instance will be used')

            ->addCommonOptions()
        ;
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int
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

            /// @todo is it possible to let the user log in to the 'root' db, regardless of db type ?

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
            $password = bin2hex(random_bytes(15)); // some oracle versions have a limit of 30 chars on passwords
            //$dbName = bin2hex(random_bytes(31));
            $dbName = $userName; // $userName will be used as db name

            $tempDbSpecs = [];
            foreach($instanceList as $instanceName => $instanceSpecs) {
                $tempDbSpecs[$instanceName] = [
                    'dbname' => $dbName,
                    'user' => $userName,
                    'password' => $password
                ];
            }

            if (($charset = $input->getOption('charset')) != '') {
                foreach($instanceList as $instanceName) {
                    $tempDbSpecs[$instanceName]['charset'] = $charset;
                }
            }

            // for now, we always use NativeClient for creating/dropping temp dbs
            $previousStrategy = $this->executionStrategy;
            $this->executionStrategy = NativeClient::EXECUTION_STRATEGY;
            $creationResults = $this->createDatabases($instanceList, $tempDbSpecs);
            $this->executionStrategy = $previousStrategy;

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
                $dbConnectionSpecs[$instanceName] = array_merge($instanceSpecs, $dbConfig);
            }
        }

        if (count($dbConnectionSpecs)) {

            if ($this->outputFormat === 'text') {
                $this->writeln('<info>Executing sql command/file...</info>', OutputInterface::VERBOSITY_VERBOSE);
            }

            $results = $this->executeSQL($dbConnectionSpecs, $sql, $file);

            if ($this->outputFormat === 'text') {
                $this->writeln('<info>Dropping temporary databases...</info>', OutputInterface::VERBOSITY_VERBOSE);
            }

            if ($createDB) {
                $results['failed'] += $creationResults['failed'];

                foreach($instanceList as $instanceName  => $instanceSpecs) {
                    if (!isset($dbConnectionSpecs[$instanceName])) {
                        /// @todo retrieve the actual temp db creation error instead of doing this...
                        $results['data'][$instanceName] = [
                            'exitcode' => '1',
                            'stderr' => 'Error in creation of temporary database'
                        ];
                        unset($instanceList[$instanceName]);
                    }
                }
                // for now, we always use NativeClient for creating/dropping temp dbs
                $previousStrategy = $this->executionStrategy;
                $this->executionStrategy = NativeClient::EXECUTION_STRATEGY;
                $this->dropDatabases($instanceList, $dbConnectionSpecs, true);
                $this->executionStrategy = $previousStrategy;
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
     * @param string $filename
     * @param string $format
     * @param int $maxParallel
     * @param int $timeout
     * @return array
     * @throws \Exception
     */
    protected function executeSQL(array $dbConnectionSpecs, $sql, $filename = null)
    {
        return $this->executeSqlAction(
            $dbConnectionSpecs,
            'Execution of SQL',
            function ($schemaManager, $instanceName) use ($sql, $filename) {
                if ($sql != null) {
                    return new Command(
                        $sql,
                        function ($output, $executor) {
                            /** @var TimedExecutor $executor */
                            return array_merge(['stdout' => $output], $executor->getTimingData());
                        }
                    );
                } else {
                    /** @var DatabaseSchemaManager $schemaManager */
                    $realFileName = $this->replaceDBSpecTokens($filename, $instanceName, $schemaManager->getDatabaseConfiguration());
                    if (!is_file($realFileName)) {
                        throw new \RuntimeException("Can not find sql file for execution: '$realFileName'");
                    }
                    return new File(
                        $realFileName,
                        function ($output, $executor) {
                            /** @var TimedExecutor $executor */
                            return array_merge(['stdout' => $output], $executor->getTimingData());
                        }
                    );
                }
            },
            true,
            array($this, 'onSubProcessOutput')
        );
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

    protected function hasDBSpecTokens($string)
    {
        return $string != str_replace(
            array('{dbtype}', '{instancename}', '{vendor}'),
            '',
            $string
        );
    }

    protected function writeResultsToFile(array $results)
    {
        if (!$this->hasDBSpecTokens($this->outputFile)) {
            parent::writeResultsToFile($results);
            return;
        }

        foreach($results['data'] as $instanceName => $data) {
            $formattedData = $this->formatResults(array('data' => $data));
            $outputFile = $this->replaceDBSpecTokens($this->outputFile, $instanceName, $this->dbConfigurationManager->getInstanceConfiguration($instanceName));
            file_put_contents($outputFile, $formattedData);
        }
    }
}
