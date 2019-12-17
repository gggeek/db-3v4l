<?php

namespace Db3v4l\API\Interfaces\SqlExecutor\Forked;

use Symfony\Component\Process\Process;

/**
 * Executes the SQL commands found in a file, using a Symfony Process
 */
interface FileExecutor
{
    /**
     * @param string $filename
     * @return Process
     */
    public function getExecuteFileProcess($filename);
}
