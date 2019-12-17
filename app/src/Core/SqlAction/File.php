<?php

namespace Db3v4l\Core\SqlAction;

use Db3v4l\API\Interfaces\SqlAction\FileAction;

class File implements FileAction
{
    protected $fileName;
    protected $callable;
    protected $sqlToFile;

    /**
     * @param string $fileName
     * @param callable|null $callable
     * @param bool $sqlToFile
     */
    public function __construct($fileName, $callable = null)
    {
        $this->fileName = $fileName;
        $this->callable = $callable;
    }

    /**
     * @return string|null the file with sql statements to execute
     */
    public function getFilename()
    {
        return $this->fileName;
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
