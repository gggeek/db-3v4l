<?php

namespace Db3v4l\API\Interfaces\SqlAction;

/**
 * Encapsulates an action that involves:
 * - executing some SQL
 * - running php code on the results of that execution
 */
interface CommandAction
{
    /**
     * @return string|null the sql statement to execute
     */
    public function getCommand();

    /**
     * Whether the command is a single sql statement or not
     * @return bool
     */
    public function isSingleStatement();

    /**
     * The returned function should accept a single parameter, of type string
     * @return callable|null
     */
    public function getResultsFilterCallable();
}
