<?php

namespace Db3v4l\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Yaml\Yaml;
use Db3v4l\Service\DatabaseConfigurationManager;

class DatabaseList extends BaseCommand
{
    protected static $defaultName = 'db3v4l:database:list';

    /** @var DatabaseConfigurationManager $dbManager */
    protected $dbManager;

    public function __construct(DatabaseConfigurationManager $dbManager)
    {
        $this->dbManager = $dbManager;

        parent::__construct();
    }

    protected function configure()
    {
        $this
            ->setDescription('Lists all configured database servers')
            ->addOption('output-type', null, InputOption::VALUE_REQUIRED, 'The format for the output: text, json or yml', 'text')
        ;
    }

    /**
     * @todo allow to dump full configuration, not just db name
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int|void|null
     * @throws \Exception
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->setOutput($output);
        $this->setVerbosity($output->getVerbosity());

        $list = $this->dbManager->listDatabases();

        switch ($input->getOption('output-type')) {
            case 'json':
                $result = json_encode($list, JSON_PRETTY_PRINT);
                break;
            case 'yml':
            case 'yaml':
                $result = Yaml::dump($list);
                break;
            default:
                throw new \Exception('to be implemented...');
                break;
        }

        $output->writeln($result);
    }
}
