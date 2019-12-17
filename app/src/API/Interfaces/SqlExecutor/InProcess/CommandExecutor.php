<?php

namespace Db3v4l\API\Interfaces\SqlExecutor\InProcess;

/**
 * Executes a SQL 'command' (ie. one or more statements) using php
 */
interface CommandExecutor
{
    /**
     * @param string $sql
     * @return Callable
     */
    public function getExecuteCommandCallable($sql);
}
