<?php

namespace Db3v4l\Core\SqlExecutor\InProcess;

use Db3v4l\API\Interfaces\SqlExecutor\InProcess\CommandExecutor;
use Db3v4l\Core\SqlExecutor\BaseExecutor;
use Doctrine\DBAL\DriverManager;
use Doctrine\DBAL\Configuration;

class Doctrine extends BaseExecutor implements CommandExecutor
{
    const EXECUTION_STRATEGY = 'Doctrine';

    public function getExecuteCommandCallable($sql)
    {
        /// @todo
        throw new \Exception('Not implemented yet');
    }

    public function resultSetToArray($data)
    {
        /// @todo
        throw new \Exception('Not implemented yet');
    }
}
