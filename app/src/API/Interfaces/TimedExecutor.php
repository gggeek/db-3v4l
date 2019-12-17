<?php

namespace Db3v4l\API\Interfaces;

/// @rename no need to have 'Executor' in this interface...
interface TimedExecutor
{
    /**
     * Returns time/memory/cpu consumption of last execution
     * @return array keys: 'time', 'memory'
     */
    public function getTimingData();
}
