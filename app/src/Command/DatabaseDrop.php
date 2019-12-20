<?php

namespace Db3v4l\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class DatabaseDrop extends DatabaseManagingCommand
{
    protected static $defaultName = 'db3v4l:database:drop';

    protected function configure()
    {
        $this
            ->setDescription('Drops a database & associated user in parallel on all configured database instances')
            ->addOption('database', 'd', InputOption::VALUE_REQUIRED, 'The name of the database to drop')
            ->addOption('user', 'c', InputOption::VALUE_REQUIRED, 'The name of a user to drop. If omitted, no user will be dropped')
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

        $userName = $input->getOption('user');
        $dbName = $input->getOption('database');

        // BC api
        if ($dbName == null && $userName != null) {
            $dbName = $userName;
        }

        if ($dbName == null) {
            throw new \Exception("Please provide a database name");
        }

        if ($this->outputFormat === 'text') {
            $this->writeln('<info>Dropping databases...</info>');
        }

        $dbToDropSpecs = [];
        foreach($instanceList as $instanceName => $instanceSpecs) {
            $dbToDropSpecs[$instanceName] = [
                'dbname' => $dbName
            ];
            if ($userName != null) {
                $dbToDropSpecs[$instanceName]['user'] = $userName;
            }
        }

        $results = $this->dropDatabases($instanceList, $dbToDropSpecs);

        // q: shall we print something more useful?
        $results['data'] = null;

        $time = microtime(true) - $start;

        $this->writeResults($results, $time);

        return (int)$results['failed'];
    }
}
