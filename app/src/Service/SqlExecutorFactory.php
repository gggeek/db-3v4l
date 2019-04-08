<?php

namespace Db3v4l\Service;

use Db3v4l\API\Interfaces\ForkedSqlExecutor;
use Db3v4l\Service\SqlExecutor\Forked\NativeClient;
use Db3v4l\Service\SqlExecutor\Forked\Doctrine;

class SqlExecutorFactory
{
    /**
     * @param array $databaseConnectionConfiguration
     * @param string $executionStrategy
     * @return ForkedSqlExecutor
     * @throws \OutOfBoundsException
     */
    public function createForkedExecutor($databaseConnectionConfiguration, $executionStrategy = 'NativeClient')
    {
        switch ($executionStrategy) {
            case 'Doctrine':
                return new Doctrine($databaseConnectionConfiguration);
            case 'NativeClient':
                return new NativeClient($databaseConnectionConfiguration);
            default:
                throw new \OutOfBoundsException("Unsupported executor strategy '$executionStrategy'");
        }
    }
}