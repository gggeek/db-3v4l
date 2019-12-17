<?php

namespace Db3v4l\API\Interfaces\SqlExecutor\Forked;

use Symfony\Component\Process\Process;

/**
 * Executes a SQL 'command' (ie. one or more statements) using a Symfony Process
 */
interface CommandExecutor
{
    /**
     * @param string $sql
     * @return Process
     */
    public function getExecuteStatementProcess($sql);
}
