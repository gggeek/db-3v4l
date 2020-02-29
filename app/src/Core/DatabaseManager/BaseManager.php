<?php

namespace Db3v4l\Core\DatabaseManager;

abstract class BaseManager
{
    protected $databaseConfiguration;

    public function __construct(array $databaseConfiguration)
    {
        $this->databaseConfiguration = $databaseConfiguration;
    }

    /**
     * @return string[]
     */
    public function getDatabaseConfiguration()
    {
        return $this->databaseConfiguration;
    }
}
