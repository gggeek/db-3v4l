<?php

namespace Db3v4l\API\Interfaces\SqlExecutor\Forked;

use Symfony\Component\Process\Process;

interface FileExecutor
{
    /**
     * @param string $filename
     * @return Process
     */
    public function getExecuteFileProcess($filename);
}
