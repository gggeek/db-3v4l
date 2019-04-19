<?php

namespace Db3v4l\Service\SqlExecutor\Forked;

use Db3v4l\API\Interfaces\ForkedCommandExecutor;
use Db3v4l\API\Interfaces\ForkedFileExecutor;
use Db3v4l\API\Interfaces\TimedExecutor as TimedExecutorInterface;

class TimedExecutor implements ForkedCommandExecutor, ForkedFileExecutor, TimedExecutorInterface
{
    /** @var ForkedCommandExecutor */
    protected $wrappedExecutor;
    protected $timingFile;

    protected $timeCmd = '/usr/bin/time';

    public function __construct(ForkedCommandExecutor $wrappedExecutor)
    {
        $this->wrappedExecutor = $wrappedExecutor;
    }

    public function getExecuteCommandProcess($sql)
    {
        $process = $this->wrappedExecutor->getExecuteCommandProcess($sql);

        // wrap in a `time` call
        $this->timingFile = tempnam(sys_get_temp_dir(), 'db3val_');
        $process->setCommandLine(
            $this->timeCmd . ' ' . escapeshellarg('--output=' . $this->timingFile) . ' ' . escapeshellarg('--format=%M %e') . ' '
            . $process->getCommandLine());

        return $process;
    }

    public function getExecuteFileProcess($sql)
    {
        $process = $this->wrappedExecutor->getExecuteFileProcess($sql);

        // wrap in a `time` call
        $this->timingFile = tempnam(sys_get_temp_dir(), 'db3val_');
        $process->setCommandLine(
            $this->timeCmd . ' ' . escapeshellarg('--output=' . $this->timingFile) . ' ' . escapeshellarg('--format=%M %e') . ' '
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
