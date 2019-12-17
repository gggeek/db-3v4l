<?php

namespace Db3v4l\Core\SqlExecutor\InProcess;

use Db3v4l\API\Interfaces\SqlExecutor\InProcess\CommandExecutor;
use Db3v4l\API\Interfaces\SqlExecutor\InProcess\FileExecutor;
use Db3v4l\API\Interfaces\TimedExecutor as TimedExecutorInterface;

class TimedExecutor implements TimedExecutorInterface, CommandExecutor, FileExecutor
{
    /** @var CommandExecutor|FileExecutor $wrappedExecutor */
    protected $wrappedExecutor;
    protected $timingData = [];

    /**
     * @param CommandExecutor|FileExecutor $wrappedExecutor
     */
    public function __construct($wrappedExecutor)
    {
        $this->wrappedExecutor = $wrappedExecutor;
    }

    /**
     * @param string $sql
     * @return Callable
     */
    public function getExecuteCommandCallable($sql)
    {
        $callable = $this->wrappedExecutor->getExecuteCommandCallable($sql);
        return function() use ($callable) {
            $time = microtime(true);
            $results = call_user_func($callable);
            $time = microtime(true) - $time;
            $this->timingData = ['memory' => null, 'time' => sprintf('%.3d', $time)];
            return $results;
        };
    }

    /**
     * @param string $filename
     * @return Callable
     */
    public function getExecuteFileCallable($filename)
    {
        $callable = $this->wrappedExecutor->getExecuteFileCallable($filename);
        return function() use ($callable) {
            $time = microtime(true);
            $results = call_user_func($callable);
            $time = microtime(true) - $time;
            $this->timingData = ['memory' => null, 'time' => sprintf('%.3d', $time)];
            return $results;
        };
    }

    public function getTimingData($onceIsEnough = true)
    {
        return $this->timingData;
    }
}
