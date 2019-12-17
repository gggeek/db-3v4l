<?php

namespace Db3v4l\API\Interfaces\SqlAction;

/**
 * Encapsulates an 'action' that involves:
 * - executing some SQL (a 'command' consists of one or more statements)
 * - running php code to filter/manipulate the results of that execution
 */
interface CommandAction
{
    /**
     * @return string|null the sql statement to execute. Null == only execute the Filter callable
     */
    public function getCommand();

    /**
     * Whether the command is a single sql statement or not.
     * NB: different executors/databases might adopt different strategies for single-statement vs multiple-statement
     * commands. Eg. save to a temporary file a  multiple-statement command
     * @return bool
     */
    public function isSingleStatement();

    /**
     * The returned function should accept a single parameter, of type string
     * @return callable|null
     */
    public function getResultsFilterCallable();
}
