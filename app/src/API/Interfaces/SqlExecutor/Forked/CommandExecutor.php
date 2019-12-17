<?php

namespace Db3v4l\API\Interfaces\SqlExecutor\Forked;

use Db3v4l\API\Interfaces\SqlExecutor\Executor;
use Symfony\Component\Process\Process;

/**
 * Executes a SQL 'command' (ie. one or more statements) using a Symfony Process
 */
interface CommandExecutor extends Executor
{
    /**
     * @param string $sql
     * @return Process
     */
    public function getExecuteStatementProcess($sql);
}
