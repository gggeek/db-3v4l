<?php

namespace Db3v4l\API\Interfaces\SqlExecutor\InProcess;

interface CommandExecutor
{
    /**
     * @param string $sql
     * @return Callable
     */
    public function getExecuteCommandCallable($sql);
}
