<?php

namespace Db3v4l\Service;

class DatabaseConfigurationManager
{
    protected $dbList = [];

    public function __construct(array $dbList)
    {
        $this->dbList = $dbList;
    }

    /**
     * @return string[]
     */
    public function listDatabases()
    {
        return array_keys($this->dbList);
    }

    /**
     * @param string $dbName
     * @return string[]
     * @throws \OutOfBoundsException
     */
    public function getDatabaseConnectionSpecification($dbName)
    {
        if (isset($this->dbList[$dbName])) {
            return $this->dbList[$dbName];
        }

        throw new \OutOfBoundsException("Unknown database '$dbName'");
    }
}