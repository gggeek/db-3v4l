<?php

namespace Db3v4l\API\Interfaces;

use Symfony\Component\Process\Process;

interface SqlExecutor
{
    /**
     * @param string $sql
     * @return Process
     */
    public function getProcess($sql);
}
