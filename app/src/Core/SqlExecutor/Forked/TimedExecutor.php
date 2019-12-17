<?php

namespace Db3v4l\Core\SqlExecutor\Forked;

use Db3v4l\API\Interfaces\SqlExecutor\Forked\CommandExecutor;
use Db3v4l\API\Interfaces\SqlExecutor\Forked\FileExecutor;
use Db3v4l\API\Interfaces\TimedExecutor as TimedExecutorInterface;

class TimedExecutor implements CommandExecutor, FileExecutor, TimedExecutorInterface
{
    /** @var CommandExecutor|FileExecutor */
    protected $wrappedExecutor;
    protected $timingFile;

    protected $timeCmd = '/usr/bin/time';

    /**
     * @param CommandExecutor|FileExecutor $wrappedExecutor
     */
    public function __construct($wrappedExecutor)
    {
        $this->wrappedExecutor = $wrappedExecutor;
    }

    public function getExecuteStatementProcess($sql)
    {
        $process = $this->wrappedExecutor->getExecuteStatementProcess($sql);

        // wrap in a `time` call
        $this->timingFile = tempnam(sys_get_temp_dir(), 'db3v4l_');
        $process->setCommandLine(
            $this->timeCmd . ' ' . escapeshellarg('--output=' . $this->timingFile) . ' ' . escapeshellarg('--format=%M %e') . ' '
            . $process->getCommandLine());
        return $process;
    }

    public function getExecuteFileProcess($sql)
    {
        $process = $this->wrappedExecutor->getExecuteFileProcess($sql);

        // wrap in a `time` call
        $this->timingFile = tempnam(sys_get_temp_dir(), 'db3v4l_');
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
            $timingData = preg_replace('/Command exited with non-zero status [0-9]+/', '', $timingData);
            $timingData = explode(' ', trim($timingData), 2);
            $results['time'] = $timingData[1];
            $results['memory'] = $timingData[0];
        } else {
            // happens eg. if `time` command is not available
            throw new \Exception("File with timing data empty: '{$this->timingFile}'");
        }

        if ($onceIsEnough) {
            unlink ($this->timingFile);
        }

        return $results;
    }
}
