<?php

namespace Db3v4l\Service;

use Db3v4l\API\Interfaces\ForkedCommandExecutor;
use Db3v4l\API\Interfaces\ForkedFileExecutor;
use Db3v4l\Service\SqlExecutor\Forked\NativeClient;
use Db3v4l\Service\SqlExecutor\Forked\Doctrine;
use Db3v4l\Service\SqlExecutor\Forked\TimedExecutor;

class SqlExecutorFactory
{
    /**
     * @param array $databaseConnectionConfiguration
     * @param string $executionStrategy
     * @param bool $timed
     * @return ForkedCommandExecutor|ForkedFileExecutor
     * @throws \OutOfBoundsException
     */
    public function createForkedExecutor(array $databaseConnectionConfiguration, $executionStrategy = 'NativeClient', $timed = true)
    {
        switch ($executionStrategy) {
            case 'Doctrine':
                $executor = new Doctrine($databaseConnectionConfiguration);
                break;
            case 'NativeClient':
                $executor = new NativeClient($databaseConnectionConfiguration);
                break;
            default:
                throw new \OutOfBoundsException("Unsupported executor strategy '$executionStrategy'");
        }

        if ($timed) {
            $executor = new TimedExecutor($executor);
        }

        return $executor;
    }
}
