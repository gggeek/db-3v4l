<?php

namespace Db3v4l\Command;

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
            ->setDescription('Executes an SQL command in parallel on all configured database instances, creating a dedicated temporary database (and user)')
            ->addOption('sql', null, InputOption::VALUE_REQUIRED, 'The sql command(s) string to execute')
            ->addOption('file', null, InputOption::VALUE_REQUIRED, 'A file with sql commands to execute')
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

        $dbList = $this->dbManager->listInstances();
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
            $this->writeln('<info>Creating temporary databases...</info>', OutputInterface::VERBOSITY_VERBOSE);
        }

        /// @todo inject more randomness in the username, by allowing more chars than bin2hex produces
        $userName = bin2hex(random_bytes(8)); // old mysql versions have a limitation of 16 chars for usernames
        $password = bin2hex(random_bytes(16));
        //$dbName = bin2hex(random_bytes(31));
        $dbName = null; // $userName will be used as db name

        $tempDbSpecs = [];
        foreach($dbList as $instanceName) {
            $tempDbSpecs[$instanceName] = [
                'user' => $userName,
                'password' => $password,
                'dbname' => $dbName
            ];
        }

        $results = $this->createDatabases($tempDbSpecs, $maxParallel, $timeout);
        $dbConnectionSpecs = $results['data'];

        $results = $this->executeSQL($dbConnectionSpecs, $sql, $file, $format, $maxParallel, $timeout);

        if ($format === 'text') {
            $this->writeln('<info>Dropping temporary databases...</info>', OutputInterface::VERBOSITY_VERBOSE);
        }

        $this->dropDatabases($dbConnectionSpecs, $maxParallel);

        $time = microtime(true) - $start;

        $this->writeResults($results, $time, $format);
    }

    /**
     * @param array $dbConnectionSpecs
     * @param string $sql
     * @param string $file
     * @param string $format
     * @param int $maxParallel
     * @param int $timeout
     * @return array
     */
    protected function executeSQL(array $dbConnectionSpecs, $sql, $file = null, $format = self::DEFAULT_OUTPUT_FORMAT, $maxParallel = self::DEFAULT_PARALLEL_PROCESSES, $timeout = self::DEFAULT_PROCESS_TIMEOUT)
    {
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

        $succeeded = 0;
        $failed = 0;
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

        return [
            'succeeded' => $succeeded,
            'failed' => $failed,
            'data' => $results
        ];
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
