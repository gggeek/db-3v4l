<?php

namespace Db3v4l\Core\SqlExecutor\InProcess;

use Db3v4l\API\Interfaces\InProcessCommandExecutor;
use Db3v4l\API\Interfaces\InProcessFileExecutor;

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
}
