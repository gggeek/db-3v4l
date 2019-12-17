<?php

namespace Db3v4l\Core\SqlAction;

use Db3v4l\API\Interfaces\SqlAction\FileAction;

class File implements FileAction
{
    protected $filename;
    protected $callable;

    /**
     * @param string $filename
     * @param callable|null $callable
     */
    public function __construct($filename, $callable = null)
    {
        $this->filename = $filename;
        $this->callable = $callable;
    }

    /**
     * @return string|null the file with sql statements to execute
     */
    public function getFilename()
    {
        return $this->filename;
    }

    /**
     * The returned function should accept a single parameter, of type string
     * @return callable|null
     */
    public function getResultsFilterCallable()
    {
        return $this->callable;
    }
}
