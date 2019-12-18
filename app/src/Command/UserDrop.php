<?php

namespace Db3v4l\Command;

use Db3v4l\Core\DatabaseSchemaManager;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class UserDrop extends DatabaseManagingCommand
{
    protected static $defaultName = 'db3v4l:user:drop';

    protected function configure()
    {
        $this
            ->setDescription('Drops a database user in parallel on all configured database instances')
            ->addOption('user', null, InputOption::VALUE_REQUIRED, 'The name of the user to drop')
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

        if ($userName == null) {
            throw new \Exception("Please provide a username");
        }

        if ($this->outputFormat === 'text') {
            $this->writeln('<info>Dropping users...</info>');
        }

        $userToDropSpecs = [];
        foreach($instanceList as $instanceName => $instanceSpecs) {
            $userToDropSpecs[$instanceName] = [
                'user' => $userName
            ];
        }
        $results = $this->dropUsers($instanceList, $userToDropSpecs);

        // BC with versions < 0.8
        $results['data'] = null;

        $time = microtime(true) - $start;

        $this->writeResults($results, $time);

        return (int)$results['failed'];
    }

    /**
     * @param $instanceList
     * @param $userToDropSpecs
     * @param bool $ifExists
     * @return array
     * @throws \Exception
     */
    protected function dropUsers($instanceList, $userToDropSpecs, $ifExists = false)
    {
        return $this->executeSqlAction(
            $instanceList,
            'Dropping of user',
            function ($schemaManager, $instanceName) use ($userToDropSpecs, $ifExists) {
                $dbConnectionSpec = $userToDropSpecs[$instanceName];
                /** @var DatabaseSchemaManager $schemaManager */
                return $schemaManager->getDropUserSqlAction(
                    $dbConnectionSpec['user'],
                    $ifExists
                );
            }
        );
    }
}
