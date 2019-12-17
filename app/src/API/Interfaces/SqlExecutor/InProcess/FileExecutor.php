<?php

namespace Db3v4l\API\Interfaces\SqlExecutor\InProcess;

/**
 * Executes the SQL commands found in a file, using php
 */
interface FileExecutor
{
    /**
     * @param string $filename
     * @return Callable
     */
    public function getExecuteFileCallable($filename);
}
