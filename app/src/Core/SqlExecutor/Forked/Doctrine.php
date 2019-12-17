<?php

namespace Db3v4l\Core\SqlExecutor\Forked;

use Db3v4l\API\Interfaces\SqlExecutor\Forked\CommandExecutor;
use Db3v4l\Util\Process;

/**
 * Executes sql queries via a symfony console command based on Doctrine
 */
class Doctrine extends ForkedExecutor implements ForkedCommandExecutor
{
    /**
     * @param string|string[] $sql
     * @return Process
     */
    public function getExecuteStatementProcess($sql)
    {
        throw new \RuntimeException('TO BE IMPLEMENTED');
    }
}
