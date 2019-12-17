<?php

namespace Db3v4l\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Yaml\Yaml;
use Db3v4l\Service\DatabaseConfigurationManager;

class InstanceList extends BaseCommand
{
    protected static $defaultName = 'db3v4l:instance:list';

    /** @var DatabaseConfigurationManager $dbConfigurationManager */
    protected $dbConfigurationManager;
    protected $outputFormat;

    public function __construct(DatabaseConfigurationManager $dbConfigurationManager)
    {
        $this->dbConfigurationManager = $dbConfigurationManager;

        parent::__construct();
    }

    protected function configure()
    {
        $this
            ->setDescription('Lists all configured database servers')
            ->addOption('output-type', null, InputOption::VALUE_REQUIRED, 'The format for the output: json, php, text or yml', 'text')
        ;
    }

    /**
     * @todo allow to dump full configuration, not just db vendor + version
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int
     * @throws \Exception
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->setOutput($output);
        $this->setVerbosity($output->getVerbosity());

        $this->outputFormat = $input->getOption('output-type');

        $list = $this->dbConfigurationManager->listInstances();

        $result = $this->listInstances($list);

        $this->writeResult($result);

        return 0;
    }

    /**
     * @todo allow to retrieve exact version number dynamically, see getRetrieveVersionInfoSqlAction
     * @param string[] $instanceList
     * @return string[][]
     */
    protected function listInstances($instanceList)
    {
        $out = [];
        foreach($instanceList as $instanceName) {
            $connectionSpec = $this->dbConfigurationManager->getInstanceConfiguration($instanceName);
            $out[$instanceName] = [
                'vendor' => $connectionSpec['vendor'],
                'version' => $connectionSpec['version']
            ];
        }
        return $out;
    }

    protected function writeResult($result)
    {
        switch ($this->outputFormat) {
            case 'json':
                $data = json_encode($result, JSON_PRETTY_PRINT);
                break;
            case 'php':
                $data = var_export($result, true);
                break;
            case 'text':
            case 'yml':
            case 'yaml':
                $data = Yaml::dump($result);
                break;
            default:
                throw new \Exception("Unsupported output format: '{$this->outputFormat}'");
                break;
        }

        $this->writeln($data, OutputInterface::VERBOSITY_QUIET,  OutputInterface::OUTPUT_RAW);
    }
}
