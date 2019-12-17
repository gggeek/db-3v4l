<?php

namespace Db3v4l\API\Interfaces\SqlExecutor;

interface Executor
{
    /**
     * Converts the data generated by the callable (eg. a PDO statement) or Process (ie. stdout) into an array
     * @param string|mixed $data
     * @return array
     */
    public function resultSetToArray($data);
}