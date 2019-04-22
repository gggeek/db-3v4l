<?php

namespace Db3v4l\Core\SqlExecutor\Forked;

use Db3v4l\API\Interfaces\ForkedCommandExecutor;
use Db3v4l\Util\Process;

class Doctrine extends ForkedExecutor implements ForkedCommandExecutor
{
    /**
     * @param string|string[] $sql
     * @return Process
     */
    public function getExecuteCommandProcess($sql)
    {
        throw new \RuntimeException('TO BE IMPLEMENTED');
    }
}
