<?php

namespace Db3v4l\Service\SqlExecutor;

use Symfony\Component\Process\Process;
use Doctrine\DBAL\DriverManager;
use Doctrine\DBAL\Configuration;
use Db3v4l\API\Interfaces\SqlExecutor;

class Doctrine extends BaseExecutor implements SqlExecutor
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