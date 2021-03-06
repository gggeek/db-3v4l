<?php

namespace Db3v4l\API\Interfaces\SqlAction;

/**
 * Encapsulates an 'action' that involves:
 * - executing some SQL statements stored in a file
 * - running php code to filter/manipulate the results of that execution
 */
interface FileAction
{
    /**
     * @return string|null the file with sql statements to execute. Null == only execute the Filter callable
     */
    public function getFilename();

    /**
     * The returned function should accept two parameters: execution-result and executor
     * @return callable|null
     */
    public function getResultsFilterCallable();
}
