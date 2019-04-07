<?php

namespace Db3v4l\Service\SqlExecutor;

abstract class BaseExecutor
{
    protected $databaseConfiguration;

    public function __construct(array $databaseConfiguration)
    {
        $this->databaseConfiguration = $databaseConfiguration;
    }

    public function buildCommandLine($command, array $arguments = array())
    {
        $arguments = array_map('escapeshellarg', $arguments);
        return escapeshellcmd($command) . ' ' . implode(' ', $arguments);
    }
}