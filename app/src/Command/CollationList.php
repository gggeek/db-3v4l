<?php

namespace Db3v4l\Command;

use Db3v4l\Core\DatabaseSchemaManager;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class CollationList extends DatabaseManagingCommand
{
    protected static $defaultName = 'db3v4l:collation:list';

    protected function configure()
    {
        $this
            ->setDescription('Lists available collations for all database instances')
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

        if ($this->outputFormat === 'text') {
            $this->writeln('<info>Analyzing databases...</info>', OutputInterface::VERBOSITY_VERBOSE);
        }

        $results = $this->listCollations($instanceList);

        $time = microtime(true) - $start;

        $this->writeResults($results, $time);

        return (int)$results['failed'];
    }

    protected function listCollations($instanceList)
    {
        return $this->executeSqlAction(
            $instanceList,
            'Listing of collations',
            function ($schemaManager, $instanceName) {
                /** @var DatabaseSchemaManager $schemaManager */
                return $schemaManager->getListCollationsSqlAction();
            }
        );
    }
}
