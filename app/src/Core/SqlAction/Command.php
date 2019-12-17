<?php

namespace Db3v4l\Core\SqlAction;

use Db3v4l\API\Interfaces\SqlAction\CommandAction;

class Command implements CommandAction
{
    protected $sql;
    protected $callable;
    protected $isSingleStatement;
    protected $statementSeparator = ' ';
    //protected $statementTerminator = ';';

    /**
     * @param string|string[]|null $sql for single statements, pass in either a string or an array with a single string element
     *                                  for multiple statements, pass in an array
     *                                  pass in null when all you need to execute is the callable
     * @param callable|null $callable
     * @param bool $isSingleStatement if left null, it will be inferred
     * @todo allow support for sql comments as part of statements...
     */
    public function __construct($sql, $callable = null, $isSingleStatement = null)
    {
        if (is_array($sql)) {
            foreach ($sql as &$statement) {
                // Trimming of newlines disabled as it messes up with mssql GO statements...
                $statement = trim($statement, " \t");
                // Disabled as it messes up with mssql GO statements, which take no terminator...
                // @todo find a nice way to reintroduce this
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
