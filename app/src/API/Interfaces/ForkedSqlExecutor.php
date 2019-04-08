<?php

namespace Db3v4l\API\Interfaces;

use Symfony\Component\Process\Process;

interface ForkedSqlExecutor
{
    /**
     * @param string $sql
     * @return Process
     */
    public function getProcess($sql);
}
