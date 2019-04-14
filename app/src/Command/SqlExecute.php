<?php

namespace Db3v4l\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputOption;
use Db3v4l\Util\Process;
use Symfony\Component\Yaml\Yaml;
use Db3v4l\Service\DatabaseConfigurationManager;
use Db3v4l\Service\SqlExecutorFactory;
use Db3v4l\Service\ProcessManager;

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
            ->setDescription('Executes an SQL command in parallel on all configured database servers')
            ->addOption('sql', null, InputOption::VALUE_REQUIRED, 'The command to execute')
            //->addOption('output-type', null, InputOption::VALUE_REQUIRED, 'The format for the output: text, json or yml', 'text')
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
        $timeout = $input->getOption('timeout');
        $maxParallel = $input->getOption('max-parallel');
        $dontForceSigchildEnabled = $input->getOption('dont-force-enabled-sigchild');

        if ($sql === '') {
            throw new \Exception("Please provide an sql command/snippted to be executed");
        }

        // On Debian, which we use by default, SF has troubles understanding that php was compiled with --enable-sigchild
        // We thus force it, but give end users an option to disable this
        // For more details, see comment 12 at https://bugs.launchpad.net/ubuntu/+source/php5/+bug/516061
        if (!$dontForceSigchildEnabled) {

            Process::forceSigchildEnabled(true);
        }

        /** @var Process[] $processes */
        $processes = [];
        foreach ($dbList as $dbName) {
            $dbConnectionSpec = $this->dbManager->getDatabaseConnectionSpecification($dbName);
            $process = $this->executorFactory->createForkedExecutor($dbConnectionSpec)->getProcess($sql);

            $process->setTimeout($timeout);

            $processes[$dbName] = $process;
        }

        $this->writeln("Starting parallel execution...");

        $this->processManager->runParallel($processes, $maxParallel, 100, array($this, 'onSubProcessOutput'));

        $failed = 0;
        $succeeded = 0;
        $results = array();
        foreach ($processes as $dbName => $process) {
            $results[$dbName] = array(
                'stdout' => $process->getOutput(),
                'stderr' => $process->getErrorOutput(),
                'exitcode' => $process->getExitCode()
            );
            if ($process->isSuccessful()) {
                $succeeded++;
            } else {
                $output->writeln("\n<error>Execution on database '$dbName' failed! Reason: " . $process->getErrorOutput() . "</error>\n");
                $failed++;
            }
        }

        $time = microtime(true) - $start;

        $this->writeResults($results, $succeeded, $failed, $time);

    }

    /**
     * @param array $results
     * @param int $succeeded
     * @param int $failed
     * @param float $time
     *
     * @todo
     */
    protected function writeResults(array $results, $succeeded, $failed, $time)
    {
        $this->writeln('<info>' . $succeeded . ' succeeded, ' . $failed . ' failed</info>');

        // since we use subprocesses, we can not measure max memory used
        $this->writeln("Time taken: ".sprintf('%.2f', $time)." secs");

        var_dump($results);
    }

    /**
     * @param $type
     * @param string $buffer
     * @param string $processIndex;
     * @param Process $process
     *
     * @todo add support for verbosity level
     */
    public function onSubProcessOutput($type, $buffer, $processIndex, $process=null)
    {
        $lines = explode("\n", trim($buffer));

        foreach ($lines as $line) {
            /*if (preg_match('/Migrations executed: ([0-9]+), failed: ([0-9]+), skipped: ([0-9]+)/', $line, $matches)) {
                $this->migrationsDone[0] += $matches[1];
                $this->migrationsDone[1] += $matches[2];
                $this->migrationsDone[2] += $matches[3];

                // swallow these lines unless we are in verbose mode
                if ($this->verbosity <= Output::VERBOSITY_NORMAL) {
                    return;
                }
            }*/

            // we tag the output from the different processes
            if (trim($line) !== '') {
                echo '[' . $processIndex . '][' . ($process ? $process->getPid() : '') . '] ' . trim($line) . "\n";
            }

            //$this->results[$p]
        }
    }
}
