<?php

namespace Db3v4l\API\Interfaces\SqlExecutor\InProcess;

use Db3v4l\API\Interfaces\SqlExecutor\Executor;

/**
 * Executes a SQL 'command' (ie. one or more statements) using php
 */
interface CommandExecutor extends Executor
{
    /**
     * @param string $sql
     * @return Callable
     */
    public function getExecuteCommandCallable($sql);
}
