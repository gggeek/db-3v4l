<?php

namespace Db3v4l\Core\SqlExecutor\Forked;

use Db3v4l\Core\SqlExecutor\BaseExecutor;

abstract class ForkedExecutor extends BaseExecutor
{
    const EXECUTE_COMMAND = 0;
    const EXECUTE_FILE = 1;

    public function buildCommandLine($command, array $arguments = array())
    {
        $arguments = array_map('escapeshellarg', $arguments);
        return escapeshellcmd($command) . ' ' . implode(' ', $arguments);

        return new Process($commandLine, null, $env);
    }
}
