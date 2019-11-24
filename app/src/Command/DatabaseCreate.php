<?php

namespace Db3v4l\Command;

use Db3v4l\Util\Process;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class DatabaseCreate extends DatabaseManagingCommand
{
    protected static $defaultName = 'db3v4l:database:create';

    protected function configure()
    {
        $this
            ->setDescription('Creates a user+database in parallel on all configured database instances')
            ->addOption('user', null, InputOption::VALUE_REQUIRED, 'The name of the user to create')
            ->addOption('password', null, InputOption::VALUE_REQUIRED, 'The password')
            ->addOption('database', null, InputOption::VALUE_REQUIRED, 'The name of the database. If omitted, the user name will be used as database name')
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
        $userName = $input->getOption('user');
        $password = $input->getOption('password');
        $dbName = $input->getOption('database');

        $timeout = $input->getOption('timeout');
        $maxParallel = $input->getOption('max-parallel');
        $dontForceSigchildEnabled = $input->getOption('dont-force-enabled-sigchild');
        $format = $input->getOption('output-type');

        if ($userName == null) {
            throw new \Exception("Please provide a username");
        }

        /// @todo warn the user...
        if ($password == null) {
            $password = bin2hex(random_bytes(16));
        }

        // On Debian, which we use by default, SF has troubles understanding that php was compiled with --enable-sigchild
        // We thus force it, but give end users an option to disable this
        // For more details, see comment 12 at https://bugs.launchpad.net/ubuntu/+source/php5/+bug/516061
        if (!$dontForceSigchildEnabled) {
            Process::forceSigchildEnabled(true);
        }

        if ($format === 'text') {
            $this->writeln('<info>Creating databases...</info>');
        }

        $newDbSpecs = [];
        foreach($dbList as $instanceName) {
            $newDbSpecs[$instanceName] = [
                'user' => $userName,
                'password' => $password,
                'dbname' => $dbName
            ];
        }
        $results = $this->createDatabases($newDbSpecs, $maxParallel, $timeout, $format);

        $time = microtime(true) - $start;

        $this->writeResults($results, $time, $format);
    }
}
