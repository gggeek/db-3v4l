<?php

namespace Db3v4l\Service\SqlExecutor;

abstract class BaseExecutor
{
    protected $databaseConfiguration;

    public function __construct(array $databaseConfiguration)
    {
        $this->databaseConfiguration = $databaseConfiguration;
    }
}