<?php

namespace Db3v4l\Core\SqlExecutor;

abstract class BaseExecutor
{
    protected $databaseConfiguration;

    public function __construct(array $databaseConfiguration)
    {
        $this->databaseConfiguration = $databaseConfiguration;
    }
}
