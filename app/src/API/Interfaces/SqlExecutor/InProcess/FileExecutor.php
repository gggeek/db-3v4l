<?php

namespace Db3v4l\API\Interfaces\SqlExecutor\InProcess;

use Db3v4l\API\Interfaces\SqlExecutor\Executor;

/**
 * Executes the SQL commands found in a file, using php
 */
interface FileExecutor extends Executor
{
    /**
     * @param string $filename
     * @return Callable
     */
    public function getExecuteFileCallable($filename);
}
