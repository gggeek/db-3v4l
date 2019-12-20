<?php

namespace Db3v4l\Command;

use Db3v4l\API\Interfaces\SqlExecutor\Forked\ShellExecutor;
use Db3v4l\Service\DatabaseConfigurationManager;
use Db3v4l\Service\SqlExecutorFactory;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class DatabaseShell extends BaseCommand
{
    protected static $defaultName = 'db3v4l:database:shell';

    /** @var DatabaseConfigurationManager $dbConfigurationManager */
    protected $dbConfigurationManager;
    /** @var SqlExecutorFactory $executorFactory */
    protected $executorFactory;

    public function __construct(
        DatabaseConfigurationManager $dbConfigurationManager,
        SqlExecutorFactory $executorFactory)
    {
        $this->dbConfigurationManager = $dbConfigurationManager;
        $this->executorFactory = $executorFactory;

        parent::__construct();
    }

    protected function configure()
    {
        $this
            ->setDescription('Connects to one of the configured database instances, using the appropriate native sql client')
            ->addOption('instance', null, InputOption::VALUE_REQUIRED, 'The instance to connect to', null)
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $instanceName = $input->getOption('instance');

        if ($instanceName == null) {
            throw new \Exception("Please provide an instance name");
        }

        $dbConnectionSpec = $this->dbConfigurationManager->getInstanceConfiguration($instanceName);
        $executor = $this->executorFactory->createForkedExecutor($dbConnectionSpec, 'NativeClient', false);

        if (! $executor instanceof ShellExecutor) {
            throw new \Exception("Can not start an interactive shell for databases of type '{$dbConnectionSpec['vendor']}'");
        }

        $process = $executor->getExecuteShellProcess();

        $process->setTty(true);
        $process->run();

        return $process->getExitCode();
    }
}
