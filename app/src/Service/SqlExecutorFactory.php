<?php

namespace Db3v4l\Service;

use Db3v4l\API\Interfaces\SqlExecutor\Forked\CommandExecutor as ForkedCommandExecutor;
use Db3v4l\API\Interfaces\SqlExecutor\Forked\FileExecutor as ForkedFileExecutor;
use Db3v4l\API\Interfaces\SqlExecutor\InProcess\CommandExecutor as InProcessCommandExecutor;
use Db3v4l\API\Interfaces\SqlExecutor\InProcess\FileExecutor as InProcessFileExecutor;
use Db3v4l\Core\SqlExecutor\Forked\NativeClient;
use Db3v4l\Core\SqlExecutor\Forked\Doctrine as ForkedDoctrine;
use Db3v4l\Core\SqlExecutor\Forked\PDO as ForkedPDO;
use Db3v4l\Core\SqlExecutor\Forked\TimedExecutor as ForkedTimeExecutor;
use Db3v4l\Core\SqlExecutor\InProcess\Doctrine as InProcessDoctrine;
use Db3v4l\Core\SqlExecutor\InProcess\PDO as InProcessPDO;
use Db3v4l\Core\SqlExecutor\InProcess\TimedExecutor as InProcessTimedExecutor;

class SqlExecutorFactory
{
    /** @var DatabaseConfigurationManager $dbConfigurationManager */
    protected $dbConfigurationManager;

    public function __construct(DatabaseConfigurationManager $dbConfigurationManager)
    {
        $this->dbConfigurationManager = $dbConfigurationManager;
    }

    /**
     * @param string $instanceName
     * @param array $databaseConnectionConfiguration
     * @param string $executionStrategy
     * @param bool $timed
     * @return ForkedCommandExecutor|ForkedFileExecutor
     * @throws \OutOfBoundsException
     */
    public function createForkedExecutor($instanceName, array $databaseConnectionConfiguration, $executionStrategy = 'NativeClient', $timed = true)
    {
        switch ($executionStrategy) {
            case 'Doctrine':
                $executor = new ForkedDoctrine($databaseConnectionConfiguration);
                $executor->setInstanceName($instanceName);
                $executor->setDbConfigurationManager($this->dbConfigurationManager);
                break;
            case 'NativeClient':
                $executor = new NativeClient($databaseConnectionConfiguration);
                break;
            case 'PDO':
                $executor = new ForkedPDO($databaseConnectionConfiguration);
                $executor->setInstanceName($instanceName);
                $executor->setDbConfigurationManager($this->dbConfigurationManager);
                break;
            default:
                throw new \OutOfBoundsException("Unsupported execution strategy '$executionStrategy'");
        }

        if ($timed) {
            $executor = new ForkedTimeExecutor($executor);
        }

        return $executor;
    }

    /**
     * @param string $instanceName
     * @param array $databaseConnectionConfiguration
     * @param string $executionStrategy
     * @param bool $timed
     * @return InProcessCommandExecutor|InProcessFileExecutor
     * @throws \OutOfBoundsException
     */
    public function createInProcessExecutor($instanceName, array $databaseConnectionConfiguration, $executionStrategy = 'Doctrine', $timed = true)
    {
        switch ($executionStrategy) {
            case 'Doctrine':
                $executor = new InProcessDoctrine($databaseConnectionConfiguration);
                break;
            case 'PDO':
                $executor = new InProcessPDO($databaseConnectionConfiguration);
                break;
            default:
                throw new \OutOfBoundsException("Unsupported execution strategy '$executionStrategy'");
        }

        if ($timed) {
            $executor = new InprocessTimedExecutor($executor);
        }

        return $executor;
    }
}
