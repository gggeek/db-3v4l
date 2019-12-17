<?php

namespace Db3v4l\API\Interfaces\SqlAction;

/**
 * Encapsulates an action that involves:
 * - executing some SQL statements stored in a file
 * - running php code on the results of that execution
 */
interface FileAction
{
    /**
     * @return string|null the file with sql statements to execute
     */
    public function getFilename();

    /**
     * The returned function should accept a single parameter, of type string
     * @return callable|null
     */
    public function getResultsFilterCallable();
}
