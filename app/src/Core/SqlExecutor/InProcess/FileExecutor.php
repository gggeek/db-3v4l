<?php

namespace Db3v4l\Core\SqlExecutor\InProcess;

use Db3v4l\API\Interfaces\SqlExecutor\InProcess\CommandExecutor as InProcessCommandExecutor;
use Db3v4l\API\Interfaces\SqlExecutor\InProcess\FileExecutor as InProcessFileExecutor;

/**
 * Executes sql commands stored in a file by reading the file and passing the commands in it to a command executor
 * @todo if/when we will have executors that can deal with single sql statements but not commands, this one could
 *       eventually attempt to split the command in statements
 */
class FileExecutor implements InProcessFileExecutor
{
    /** @var InProcessCommandExecutor $wrappedExecutor */
    protected $wrappedExecutor;

    /**
     * @param InProcessCommandExecutor $wrappedExecutor
     */
    public function __construct($wrappedExecutor)
    {
        $this->wrappedExecutor = $wrappedExecutor;
    }

    /**
     * @param string $filename
     * @return Callable
     */
    public function getExecuteFileCallable($filename)
    {
        return $this->wrappedExecutor->getExecuteCommandCallable(file_get_contents($filename));
    }

    public function resultSetToArray($data)
    {
        return $this->wrappedExecutor->resultSetToArray($data);
    }
}
