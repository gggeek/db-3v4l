<?php

namespace Db3v4l\Core\SqlAction;

use Db3v4l\API\Interfaces\SqlAction\CommandAction;

/**
 * @todo allow extraction of the statements as an array, in case we have some executors that can only work on statements...
 */
class Command implements CommandAction
{
    protected $sql;
    protected $callable;
    protected $isSingleStatement;
    protected $statementSeparator = "\n";
    //protected $statementTerminator = ';';

    /**
     * @param string|string[]|null $sql for single statements, pass in either a string or an array with a single string element
     *                                  for multiple statements, pass in an array of strings
     *                                  pass in null when all you need to execute is the callable
     * @param callable|null $callable
     * @param bool $isSingleStatement if left null, it will be inferred
     */
    public function __construct($sql, $callable = null, $isSingleStatement = null)
    {
        if (is_array($sql)) {
            foreach ($sql as &$statement) {
                $statement = trim($statement);
                // Disabled as it messes up with mssql GO statements, which take no terminator...
                // @todo find a simple way to reintroduce this, while allowing support for sql comments as part of statements...
                //if (substr($statement, -1) !== $this->statementTerminator) {
                //    $statement .= $this->statementTerminator;
                //}
            }
            $this->sql = implode($this->statementSeparator, $sql);
            $isSingleStatement = ($isSingleStatement === null) ? (count($sql) < 2) : $isSingleStatement;
        } else {
            $this->sql = ($sql === null) ? $sql : trim($sql);
            $isSingleStatement = ($isSingleStatement === null) ? true : $isSingleStatement;
        }
        $this->callable = $callable;
        $this->isSingleStatement = $isSingleStatement;
    }

    public function getCommand()
    {
        return $this->sql;
    }

    public function isSingleStatement()
    {
        return $this->isSingleStatement;
    }

    /**
     * @todo should we unwrap PDOStatement into a string ?
     * @return Callable|null
     */
    public function getResultsFilterCallable()
    {
        return $this->callable;
    }
}
