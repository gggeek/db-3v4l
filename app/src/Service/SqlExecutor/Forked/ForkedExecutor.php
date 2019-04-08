<?php

namespace Db3v4l\Service\SqlExecutor\Forked;

use Db3v4l\Service\SqlExecutor\BaseExecutor;

abstract class ForkedExecutor extends BaseExecutor
{
    public function buildCommandLine($command, array $arguments = array())
    {
        $arguments = array_map('escapeshellarg', $arguments);
        return escapeshellcmd($command) . ' ' . implode(' ', $arguments);
    }
}
