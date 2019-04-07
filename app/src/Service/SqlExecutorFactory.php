<?php

namespace Db3v4l\Service;

use Db3v4l\API\Interfaces\SqlExecutor;
use Db3v4l\Service\SqlExecutor\NativeClient;
use Db3v4l\Service\SqlExecutor\Doctrine;

class SqlExecutorFactory
{
    /**
     * @param array $databaseConnectionConfiguration
     * @param string $executionStrategy
     * @return SqlExecutor
     * @throws \OutOfBoundsException
     */
    public function createExecutor($databaseConnectionConfiguration, $executionStrategy = 'NativeClient')
    {
        switch ($executionStrategy) {
            case 'Doctrine':
                return new Doctrine($databaseConnectionConfiguration);
                break;
            case 'NativeClient':
                return new NativeClient($databaseConnectionConfiguration);
                break;
            default:
                throw new \OutOfBoundsException("Unsupported executor strategy '$executionStrategy'");
        }
    }
}