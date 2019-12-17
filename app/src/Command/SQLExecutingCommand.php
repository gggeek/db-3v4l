<?php

namespace Db3v4l\Command;

use Db3v4l\API\Interfaces\SqlAction\CommandAction;
use Db3v4l\API\Interfaces\SqlAction\FileAction;
use Db3v4l\Core\DatabaseSchemaManager;
use Db3v4l\Core\SqlAction\Command;
use Db3v4l\Core\SqlAction\File;
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

    protected $outputFormat;
    protected $maxParallelProcesses;
    protected $processTimeout;

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
            ->addOption('output-type', null, InputOption::VALUE_REQUIRED, 'The format for the output: json, php, text or yml', self::DEFAULT_OUTPUT_FORMAT)
            ->addOption('timeout', null, InputOption::VALUE_REQUIRED, 'The maximum time to wait for execution (secs)', self::DEFAULT_PROCESS_TIMEOUT)
            ->addOption('max-parallel', null, InputOption::VALUE_REQUIRED, 'The maximum number of processes to run in parallel', self::DEFAULT_PARALLEL_PROCESSES)
            ->addOption('dont-force-enabled-sigchild', null, InputOption::VALUE_NONE, "When using a separate php process to run each sql command, do not force Symfony to believe that php was compiled with --enable-sigchild option")
            ->addOption('only-instances', null, InputOption::VALUE_REQUIRED, 'Filter the database servers to run this command against. Usage of * and ? wildcards is allowed. To see all instances available, use `instance:list`', null)
            ->addOption('except-instances', null, InputOption::VALUE_REQUIRED, 'Filter the database servers to run this command against.', null)
        ;
    }

    /**
     * @param InputInterface $input
     * @return string[][] the list of instances to use. key: name, value: connection spec
     */
    protected function parseCommonOptions(InputInterface $input)
    {
        $this->outputFormat = $input->getOption('output-type');
        $this->processTimeout = $input->getOption('timeout');
        $this->maxParallelProcesses = $input->getOption('max-parallel');

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
     * @param callable $onForkedProcessOutput a callback invoked when forked processes produce output
     * @return array 'succeeded': int, 'failed': int, 'data': mixed[]
     * @throws \Exception
     */
    protected function executeSqlAction($instanceList, $actionName, $getSqlActionCallable, $onForkedProcessOutput = null)
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

                    $executor = $this->executorFactory->createForkedExecutor($dbConnectionSpec, 'NativeClient', false);
                    $executors[$instanceName] = $executor;

                    if ($filename === null) {
                        if (!$sqlAction->isSingleStatement()) {
                            $tempSQLFileName = tempnam(sys_get_temp_dir(), 'db3v4l_') . '.sql';
                            file_put_contents($tempSQLFileName, $sql);
                            $tempSQLFileNames[] = $tempSQLFileName;

                            $process = $executor->getExecuteFileProcess($tempSQLFileName);
                        } else {
                            $process = $executor->getExecuteStatementProcess($sql);
                        }
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
                        $result = rtrim($process->getOutput());
                        if (isset($outputFilters[$instanceName])) {
                            try {
                                $result = call_user_func_array($outputFilters[$instanceName], [$result, $executors[$instanceName]]);
                            } catch (\Throwable $t) {
                                /// @todo shall we reset $result to null or not?
                                //$result = null;
                                $failed++;
                                $this->writeErrorln("\n<error>$actionName in instance '$instanceName' failed! Reason: " . $t->getMessage() . "</error>\n", OutputInterface::VERBOSITY_NORMAL);
                            }
                        }
                        $results[$instanceName] = $result;
                        $succeeded++;
                    } else {
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

        /// @todo implement proper sorting based on vendor name + version
        ksort($results);

        return [
            'succeeded' => $succeeded,
            'failed' => $failed,
            'data' => $results
        ];
    }

    /**
     * @param array $results should contain elements: succeeded(int) failed(int), data(mixed)
     * @throws \Exception for unsupported formats
     */
    protected function writeResults(array $results, $time)
    {
        switch ($this->outputFormat) {
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
                throw new \Exception("Unsupported output format: '{$this->outputFormat}'");
                break;
        }
        $this->writeln($data, OutputInterface::VERBOSITY_QUIET,  OutputInterface::OUTPUT_RAW);

        if ($this->outputFormat === 'text') {
            $this->writeln($results['succeeded'] . ' succeeded, ' . $results['failed'] . ' failed');

            // since we use forked processes, we can not measure max memory used
            $this->writeln("<info>Time taken: ".sprintf('%.2f', $time)." secs</info>");
        }
    }
}
