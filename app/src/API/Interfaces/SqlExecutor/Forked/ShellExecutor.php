<?php

namespace Db3v4l\API\Interfaces\SqlExecutor\Forked;

use Symfony\Component\Process\Process;

interface ShellExecutor
{
    /**
     * @return Process
     */
    public function getExecuteShellProcess();
}
