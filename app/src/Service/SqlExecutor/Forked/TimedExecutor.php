<?php

namespace Db3v4l\Service\SqlExecutor\Forked;

use Db3v4l\API\Interfaces\ForkedSqlExecutor;
use Db3v4l\API\Interfaces\TimedExecutor as TimedExecutorInterface;

class TimedExecutor implements ForkedSqlExecutor, TimedExecutorInterface
{
    /** @var ForkedSqlExecutor */
    protected $wrappedExecutor;
    protected $timingFile;

    public function __construct(ForkedSqlExecutor $wrappedExecutor)
    {
        $this->wrappedExecutor = $wrappedExecutor;
    }

    public function getProcess($sql)
    {
        $process = $this->wrappedExecutor->getProcess($sql);

        // wrap in a `time` call
        $this->timingFile = tempnam(sys_get_temp_dir(), 'db3val_');
        $process->setCommandLine(
            'time ' . escapeshellarg('--output=' . $this->timingFile) . ' ' . escapeshellarg('--format=%M %e') . ' '
            . $process->getCommandLine());

        return $process;
    }

    public function getTimingData($onceIsEnough = true)
    {
        if (!is_file($this->timingFile)) {
            throw new \Exception("File with timing data gone missing: '{$this->timingFile}'");
        }

        $timingData = file_get_contents($this->timingFile);
        if ($timingData != '') {
            $timingData = explode(' ', $timingData, 2);
            $results['time'] = $timingData[1];
            $results['memory'] = $timingData[0];
        }

        if ($onceIsEnough) {
            unlink ($this->timingFile);
        }

        return $results;
    }
}
