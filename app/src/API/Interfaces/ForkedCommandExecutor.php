<?php

namespace Db3v4l\API\Interfaces;

use Symfony\Component\Process\Process;

interface ForkedCommandExecutor
{
    /**
     * @param string $sql
     * @return Process
     */
    public function getExecuteCommandProcess($sql);
}
