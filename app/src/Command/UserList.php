<?php

namespace Db3v4l\Command;

use Db3v4l\Core\DatabaseSchemaManager;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Db3v4l\Util\Process;

class UserList extends DatabaseManagingCommand
{
    protected static $defaultName = 'db3v4l:user:list';

    protected function configure()
    {
        $this
            ->setDescription('Lists all existing users on all configured database instances')
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

        $timeout = $input->getOption('timeout');
        $maxParallel = $input->getOption('max-parallel');
        $dontForceSigchildEnabled = $input->getOption('dont-force-enabled-sigchild');
        $format = $input->getOption('output-type');

        // On Debian, which we use by default, SF has troubles understanding that php was compiled with --enable-sigchild
        // We thus force it, but give end users an option to disable this
        // For more details, see comment 12 at https://bugs.launchpad.net/ubuntu/+source/php5/+bug/516061
        if (!$dontForceSigchildEnabled) {
            Process::forceSigchildEnabled(true);
        }

        if ($format === 'text') {
            $this->writeln('<info>Analyzing databases...</info>', OutputInterface::VERBOSITY_VERBOSE);
        }

        $results = $this->listUsers($dbList, $maxParallel, $timeout);

        $time = microtime(true) - $start;

        $this->writeResults($results, $time, $format);
    }

    protected function listUsers($dbList, $maxParallel, $timeout, $format = self::DEFAULT_OUTPUT_FORMAT)
    {
        $processes = [];

        foreach ($dbList as $dbName) {
            $rootDbConnectionSpec = $this->dbManager->getDatabaseConnectionSpecification($dbName);

            $schemaManager = new DatabaseSchemaManager($rootDbConnectionSpec);
            $sql = $schemaManager->getlistUsersSQL();

            $executor = $this->executorFactory->createForkedExecutor($rootDbConnectionSpec, 'NativeClient', false);
            $process = $executor->getExecuteCommandProcess($sql);

            if ($format === 'text') {
                $this->writeln('Command line: ' . $process->getCommandLine(), OutputInterface::VERBOSITY_VERY_VERBOSE);
            }

            $process->setTimeout($timeout);

            $processes[$dbName] = $process;
        }

        if ($format === 'text') {
            $this->writeln('<info>Starting parallel execution...</info>', OutputInterface::VERBOSITY_VERY_VERBOSE);
        }

        $this->processManager->runParallel($processes, $maxParallel, 100);

        $succeeded = 0;
        $failed = 0;
        $results = [];
        foreach ($processes as $dbName => $process) {
            if ($process->isSuccessful()) {
                $results[$dbName] = rtrim($process->getOutput());
                $succeeded++;
            } else {
                $failed++;
                $this->writeErrorln("\n<error>Listing of users in instance '$dbName' failed! Reason: " . $process->getErrorOutput() . "</error>\n", OutputInterface::VERBOSITY_NORMAL);
            }
        }

        return [
            'succeeded' => $succeeded,
            'failed' => $failed,
            'data' => $results
        ];
    }
}
