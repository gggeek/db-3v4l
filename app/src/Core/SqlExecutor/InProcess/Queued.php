<?php

namespace Db3v4l\Core\SqlExecutor\InProcess;

use Db3v4l\API\Interfaces\SqlExecutor\InProcess\CommandExecutor;
use Db3v4l\API\Interfaces\SqlExecutor\InProcess\FileExecutor;

/**
 * Implements execution of sql commands via the message bus
 */
class Queued implements CommandExecutor, FileExecutor
{
    /**
     * @param string $sql
     * @return Callable
     */
    public function getExecuteCommandCallable($sql)
    {
        /// @todo
        throw new \Exception('Not implemented yet');
        return function() {
        };
    }

    /**
     * @param string $filename
     * @return Callable
     */
    public function getExecuteFileCallable($filename)
    {
        /// @todo
        throw new \Exception('Not implemented yet');
        return function() {
        };
    }

    public function resultSetToArray($data)
    {
        /// @todo
        throw new \Exception('Not implemented yet');
    }
}
