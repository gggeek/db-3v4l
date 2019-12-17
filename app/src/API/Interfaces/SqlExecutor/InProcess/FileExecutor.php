<?php

namespace Db3v4l\API\Interfaces\SqlExecutor\InProcess;

interface FileExecutor
{
    /**
     * @param string $filename
     * @return Callable
     */
    public function getExecuteFileCallable($filename);
}
