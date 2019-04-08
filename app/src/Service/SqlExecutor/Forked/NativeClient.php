<?php

namespace Db3v4l\Service\SqlExecutor;

use Symfony\Component\Process\Process;

use Db3v4l\API\Interfaces\SqlExecutor;

class NativeClient extends BaseExecutor implements SqlExecutor
{
    /**
     * @param string $sql
     * @return Process
     */
    public function getProcess($sql)
    {
        return new Process($this->buildCommandLine('echo', [$sql]));
    }
}
