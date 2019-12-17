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
            ->setDescription('Drops a database user+database in parallel on all configured database instances')
            ->addOption('user', null, InputOption::VALUE_REQUIRED, 'The name of the user to drop')
            ->addOption('database', null, InputOption::VALUE_REQUIRED, 'The name of the database. If omitted, the user name will be used as database name')
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

        if ($userName == null) {
            throw new \Exception("Please provide a username");
        }

        if ($this->outputFormat === 'text') {
            $this->writeln('<info>Dropping databases...</info>');
        }

        $dbToDropSpecs = [];
        foreach($instanceList as $instanceName => $instanceSpecs) {
            $dbToDropSpecs[$instanceName] = [
                'user' => $userName,
                'dbname' => $dbName
            ];
        }
        $results = $this->dropDatabases($instanceList, $dbToDropSpecs);

        // BC with versions < 0.8
        $results['data'] = null;

        $time = microtime(true) - $start;

        $this->writeResults($results, $time);

        return (int)$results['failed'];
    }
}
