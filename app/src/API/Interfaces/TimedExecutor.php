<?php

namespace Db3v4l\API\Interfaces;

interface TimedExecutor
{
    /**
     * Returns time/memory/cpu consumption of last execution
     * @return array
     */
    public function getTimingData();
}
