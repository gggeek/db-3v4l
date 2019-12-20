<?php

namespace Db3v4l\Core\SqlExecutor\InProcess;

use Db3v4l\API\Interfaces\SqlExecutor\InProcess\CommandExecutor;
use Db3v4l\Core\SqlExecutor\BaseExecutor;

class PDO extends BaseExecutor implements CommandExecutor
{
    const EXECUTION_STRATEGY = 'PDO';

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
