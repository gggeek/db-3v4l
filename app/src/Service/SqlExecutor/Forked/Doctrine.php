<?php

namespace Db3v4l\Service\SqlExecutor\Forked;

use Symfony\Component\Process\Process;
use Db3v4l\API\Interfaces\ForkedSqlExecutor;

class Doctrine extends ForkedExecutor implements ForkedSqlExecutor
{
    /**
     * @param string $sql
     * @return Process
     */
    public function getProcess($sql)
    {
        throw new \RuntimeException('TO BE IMPLEMENTED');
    }
}