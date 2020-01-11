<?php

namespace Db3v4l\Command;

use Db3v4l\API\Interfaces\SqlAction\CommandAction;
use Db3v4l\API\Interfaces\SqlAction\FileAction;
use Db3v4l\Core\DatabaseSchemaManager;
use Db3v4l\Service\DatabaseConfigurationManager;
use Db3v4l\Service\ProcessManager;
use Db3v4l\Service\SqlExecutorFactory;
use Db3v4l\Util\Process;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Yaml\Yaml;

abstract class SQLExecutingCommand extends BaseCommand
{
    /** @var DatabaseConfigurationManager $dbConfigurationManager */
    protected $dbConfigurationManager;
    /** @var SqlExecutorFactory $executorFactory */
    protected $executorFactory;
    protected $processManager;

    const DEFAULT_OUTPUT_FORMAT = 'text';
    const DEFAULT_PARALLEL_PROCESSES = 16;
    const DEFAULT_PROCESS_TIMEOUT = 600;
    const DEFAULT_EXECUTOR_TYPE = 'NativeClient';

    protected $outputFormat;
    protected $outputFile;
    protected $maxParallelProcesses;
    protected $processTimeout;
    protected $executionStrategy;
    protected $executeInProcess;

    public function __construct(
        DatabaseConfigurationManager $dbConfigurationManager,
        SqlExecutorFactory $executorFactory,
        ProcessManager $processManager)
    {
        $this->dbConfigurationManager = $dbConfigurationManager;
        $this->executorFactory = $executorFactory;
        $this->processManager = $processManager;

        parent::__construct();
    }

    protected function addCommonOptions()
    {
        $this
            ->addOption('only-instances', 'o', InputOption::VALUE_REQUIRED|InputOption::VALUE_IS_ARRAY, 'Filter the database servers to run this command against. Usage of * and ? wildcards is allowed. To see all instances available, use `instance:list`', null)
            ->addOption('except-instances', 'x', InputOption::VALUE_REQUIRED|InputOption::VALUE_IS_ARRAY, 'Filter the database servers to run this command against', null)
            ->addOption('output-type', null, InputOption::VALUE_REQUIRED, 'The format for the output: json, php, text or yml', self::DEFAULT_OUTPUT_FORMAT)
            ->addOption('output-file', null, InputOption::VALUE_REQUIRED, 'Save output to a file instead of writing it to stdout. NB: take care that dbconsole runs in a container, which has a different view of the filesystem. A good dir for output is ./shared')
            ->addOption('timeout', null, InputOption::VALUE_REQUIRED, 'The maximum time to wait for subprocess execution (secs)', self::DEFAULT_PROCESS_TIMEOUT)
            ->addOption('max-parallel', null, InputOption::VALUE_REQUIRED, 'The maximum number of subprocesses to run in parallel', self::DEFAULT_PARALLEL_PROCESSES)
            ->addOption('dont-force-enabled-sigchild', null, InputOption::VALUE_NONE, "When using a separate process to run each sql command, do not force Symfony to believe that php was compiled with --enable-sigchild option")
            ->addOption('execution-strategy', null, InputOption::VALUE_REQUIRED, "EXPERIMENTAL. Internal usage", self::DEFAULT_EXECUTOR_TYPE)
            ->addOption('execute-in-process', null, InputOption::VALUE_NONE, "EXPERIMENTAL. Internal usage")
        ;
    }

    /**
     * @param InputInterface $input
     * @return string[][] the list of instances to use. key: name, value: connection spec
     */
    protected function parseCommonOptions(InputInterface $input)
    {
        $this->outputFormat = $input->getOption('output-type');
        $this->outputFile = $input->getOption('output-file');
        $this->processTimeout = $input->getOption('timeout');
        $this->maxParallelProcesses = $input->getOption('max-parallel');
        $this->executionStrategy = $input->getOption('execution-strategy');
        $this->executeInProcess = $input->getOption('execute-in-process');

        // On Debian, which we use by default, SF has troubles understanding that php was compiled with --enable-sigchild
        // We thus force it, but give end users an option to disable this
        // For more details, see comment 12 at https://bugs.launchpad.net/ubuntu/+source/php5/+bug/516061
        if (! $input->getOption('dont-force-enabled-sigchild')) {
            Process::forceSigchildEnabled(true);
        }

        return $this->dbConfigurationManager->getInstancesConfiguration(
            $this->dbConfigurationManager->listInstances($input->getOption('only-instances'), $input->getOption('except-instances'))
        );
    }

    /**
     * @param string[][] $instanceList list of instances to execute the action on. Key: instance name, value: connection spec
     * @param string $actionName used to build error messages
     * @param callable $getSqlActionCallable the method used to retrieve the desired SqlAction.
     *                                       It will be passed as arguments the SchemaManager and instance name, and should return a CommandAction or FileAction
     * @param bool $timed whether to use a timed executor
     * @param callable $onForkedProcessOutput a callback invoked when forked processes produce output
     * @return array 'succeeded': int, 'failed': int, 'data': mixed[]
     * @throws \Exception
     */
    protected function executeSqlAction($instanceList, $actionName, $getSqlActionCallable, $timed = false, $onForkedProcessOutput = null)
    {
        $processes = [];
        $callables = [];
        $outputFilters = [];
        $tempSQLFileNames = [];
        $executors = [];

        try {

            foreach ($instanceList as $instanceName => $dbConnectionSpec) {

                $schemaManager = new DatabaseSchemaManager($dbConnectionSpec);

                /** @var CommandAction|FileAction $sqlAction */
                $sqlAction = call_user_func_array($getSqlActionCallable, [$schemaManager, $instanceName]);

                if ($sqlAction instanceof CommandAction) {
                    $filename = null;
                    $sql = $sqlAction->getCommand();
                } else if ($sqlAction instanceof FileAction) {
                    $filename = $sqlAction->getFilename();
                    $sql = null;
                } else {
                    // this is a coding error, not a sql execution error
                    throw new \Exception("Unsupported action type: " . get_class($sqlAction));
                }
                $filterCallable = $sqlAction->getResultsFilterCallable();

                if ($filename === null && $sql === null) {
                    // no sql to execute as forked process - we run the 'filter' functions in a separate loop
                    $callables[$instanceName] = $filterCallable;
                } else {
                    $outputFilters[$instanceName] = $filterCallable;

                    if ($filename === null && !$sqlAction->isSingleStatement()) {
                        $filename = tempnam(sys_get_temp_dir(), 'db3v4l_') . '.sql';
                        file_put_contents($filename, $sql);
                        $tempSQLFileNames[] = $filename;
                    }

                    if ($this->executeInProcess) {
                        $executor = $this->executorFactory->createInProcessExecutor($instanceName, $dbConnectionSpec, $this->executionStrategy, $timed);
                        if ($filename === null) {
                            $callables[$instanceName] = $executor->getExecuteCommandCallable($sql);
                        } else {
                            $callables[$instanceName] = $executor->getExecuteFileCallable($filename);
                        }
                    } else {
                        $executor = $this->executorFactory->createForkedExecutor($instanceName, $dbConnectionSpec, $this->executionStrategy, $timed);
                        $executors[$instanceName] = $executor;

                        if ($filename === null) {
                            $process = $executor->getExecuteStatementProcess($sql);
                        } else {
                            $process = $executor->getExecuteFileProcess($filename);
                        }

                        if ($this->outputFormat === 'text') {
                            $this->writeln('Command line: ' . $process->getCommandLine(), OutputInterface::VERBOSITY_VERY_VERBOSE);
                        }

                        $process->setTimeout($this->processTimeout);

                        $processes[$instanceName] = $process;
                    }
                }
            }

            /// @todo refactor the filtering loop so that filters can be applied as well to inProcess executors

            $succeeded = 0;
            $failed = 0;
            $results = [];

            foreach ($callables as $instanceName => $callable) {
                try {
                    $results[$instanceName] = call_user_func($callable);
                    $succeeded++;
                } catch (\Throwable $t) {
                    $failed++;
                    $this->writeErrorln("\n<error>$actionName in instance '$instanceName' failed! Reason: " . $t->getMessage() . "</error>\n", OutputInterface::VERBOSITY_NORMAL);
                }
            }

            if (count($processes)) {
                if ($this->outputFormat === 'text') {
                    $this->writeln('<info>Starting parallel execution...</info>', OutputInterface::VERBOSITY_VERY_VERBOSE);
                }
                $this->processManager->runParallel($processes, $this->maxParallelProcesses, 100, $onForkedProcessOutput);

                foreach ($processes as $instanceName => $process) {
                    if ($process->isSuccessful()) {
                        /// @todo is it necessary to have rtrim here ? shall we maybe move it to the executor ?
                        $output = rtrim($process->getOutput());
                        if (isset($outputFilters[$instanceName])) {
                            try {
                                $output = call_user_func_array($outputFilters[$instanceName], [$output, $executors[$instanceName]]);
                            } catch (\Throwable $t) {
                                /// @todo shall we reset $result to null or not?
                                //$result = null;
                                $failed++;
                                $succeeded--;
                                $this->writeErrorln("\n<error>$actionName in instance '$instanceName' failed! Reason: " . $t->getMessage() . "</error>\n", OutputInterface::VERBOSITY_NORMAL);
                            }
                        }
                        $results[$instanceName] = $output;
                        $succeeded++;
                    } else {
                        $results[$instanceName] = [
                            'stderr' => trim($process->getErrorOutput()),
                            'exitcode' => $process->getExitCode()
                        ];
                        $failed++;
                        $this->writeErrorln("\n<error>$actionName in instance '$instanceName' failed! Reason: " . $process->getErrorOutput() . "</error>\n", OutputInterface::VERBOSITY_NORMAL);
                    }
                }
            }

        } finally {
            // make sure that we clean up temp files, as they might contain sensitive data
            foreach($tempSQLFileNames as $tempSQLFileName) {
                unlink($tempSQLFileName);
            }
        }

        uksort($results, function ($a, $b) {
            $aParts = explode('_', $a, 2);
            $bParts = explode('_', $b, 2);
            $cmp = strcasecmp($aParts[0], $bParts[0]);
            if ($cmp !== 0) {
                return $cmp;
            }
            if (count($aParts) == 1) {
                return -1;
            }
            if (count($bParts) == 1) {
                return 1;
            }
            $aVersion = str_replace('_', '.', $aParts[1]);
            $bVersion = str_replace('_', '.', $bParts[1]);
            return version_compare($aVersion, $bVersion);
        });

        return [
            'succeeded' => $succeeded,
            'failed' => $failed,
            'data' => $results
        ];
    }

    /**
     * @param array $results
     * @return string
     * @throws \OutOfBoundsException for unsupported output formats
     */
    protected function formatResults(array $results)
    {
        switch ($this->outputFormat) {
            case 'json':
                return json_encode($results['data'], JSON_PRETTY_PRINT);
            case 'php':
                return var_export($results['data'], true);
            case 'text':
            case 'yml':
            case 'yaml':
                return Yaml::dump($results['data'], 2, 4, Yaml::DUMP_MULTI_LINE_LITERAL_BLOCK);
            default:
                throw new \OutOfBoundsException("Unsupported output format: '{$this->outputFormat}'");
                break;
        }
    }

    /**
     * @param array $results should contain elements: succeeded(int) failed(int), data(mixed)
     * @param float $time execution time in seconds
     * @throws \Exception for unsupported formats
     * @todo since we use could be using forked processes, we can not measure total memory used... is it worth measuring just ours?
     */
    protected function writeResults(array $results, $time = null)
    {
        if ($this->outputFile != null) {
            $this->writeResultsToFile($results);
        } else {
            $formattedResults = $this->formatResults($results);
            $this->writeln($formattedResults, OutputInterface::VERBOSITY_QUIET,  OutputInterface::OUTPUT_RAW);
        }

        if ($this->outputFormat === 'text' || $this->outputFile != null) {
            $this->writeln($results['succeeded'] . ' succeeded, ' . $results['failed'] . ' failed');
            if ($this->outputFile != null) {
                $this->writeln("Results saved to file {$this->outputFile}");
            }
            if ($time !== null) {
                $this->writeln("<info>Time taken: ".sprintf('%.2f', $time)." secs</info>");
            }
        }
    }

    protected function writeResultsToFile(array $results)
    {
        $formattedResults = $this->formatResults($results);
        file_put_contents($this->outputFile, $formattedResults);
    }
}
