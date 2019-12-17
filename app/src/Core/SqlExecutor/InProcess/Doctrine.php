<?php

namespace Db3v4l\Core\SqlExecutor\InProcess;

use Db3v4l\API\Interfaces\SqlExecutor\InProcess\StatementExecutor;
use Db3v4l\Core\SqlExecutor\BaseExecutor;
use Doctrine\DBAL\DriverManager;
use Doctrine\DBAL\Configuration;

class Doctrine extends BaseExecutor implements StatementExecutor
{
    /**
     * @param string $sql
     * @return Callable
     */
    public function getExecuteCommandCallable($sql)
    {
        /// @todo
        throw new \Exception('Not implemented yet');
    }
}
