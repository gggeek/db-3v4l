<?php

namespace Db3v4l\API\Interfaces\SqlExecutor\Forked;

use Symfony\Component\Process\Process;

interface CommandExecutor
{
    /**
     * @param string $sql
     * @return Process
     */
    public function getExecuteStatementProcess($sql);
}
