<?php

namespace Db3v4l\Command;

use Db3v4l\Core\DatabaseSchemaManager;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Yaml\Yaml;

class InstanceList extends SQLExecutingCommand
{
    protected static $defaultName = 'db3v4l:instance:list';

    protected function configure()
    {
        $this
            ->setDescription('Lists all configured database servers')
            ->addCommonOptions()
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

        $instanceList = $this->parseCommonOptions($input);

        $result = $this->listInstances($instanceList);

        $extraResults = $this->listDatabasesVersion($instanceList)['data'];
        foreach($result as $instanceName => $instanceDesc) {
            if (isset($extraResults[$instanceName])) {
                $result[$instanceName]['version'] = $extraResults[$instanceName];
            }
        }

        $this->writeResult($result);

        return 0;
    }

    /**
     * @todo allow to retrieve exact version number dynamically, see getRetrieveVersionInfoSqlAction
     * @param string[][] $instanceList
     * @return string[][]
     */
    protected function listInstances($instanceList)
    {
        $out = [];
        foreach($instanceList as $instanceName => $connectionSpec) {
            //$connectionSpec = $this->dbConfigurationManager->getInstanceConfiguration($instanceName);
            $out[$instanceName] = [
                'vendor' => $connectionSpec['vendor'],
                'version' => $connectionSpec['version']
            ];
        }
        return $out;
    }

    protected function listDatabasesVersion($instanceList)
    {
        return $this->executeSqlAction(
            $instanceList,
            'Getting database version information',
            function ($schemaManager, $instanceName) {
                /** @var DatabaseSchemaManager $schemaManager */
                return $schemaManager->getRetrieveVersionInfoSqlAction();
            }
        );
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
