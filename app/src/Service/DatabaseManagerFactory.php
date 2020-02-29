<?php

namespace Db3v4l\Service;

use Db3v4l\API\Interfaces\DatabaseManager;
use OutOfBoundsException;

class DatabaseManagerFactory
{
    protected $databaseManagers = [];

    public function __construct(array $databaseManagersList)
    {
        $this->databaseManagers = $databaseManagersList;
    }

    public function registerDatabaseManager($vendorName, $managerClass)
    {
        $this->databaseManagers[$vendorName] = $managerClass;
    }

    /**
     * @param array $dbConnectionSpec
     * @return DatabaseManager
     * @throws OutOfBoundsException for unsupported database types
     */
    public function getDatabaseManager(array $dbConnectionSpec)
    {
        if (!isset($dbConnectionSpec['vendor'])) {
            throw new OutOfBoundsException("Unspecified database type  (miss 'vendor' key in definition)");
        }

        $vendor = $dbConnectionSpec['vendor'];
        if (isset($this->databaseManagers[$vendor])) {
            $class = $this->databaseManagers[$vendor];
            return new $class($dbConnectionSpec);
        }

        throw new OutOfBoundsException("Unsupported database type '$vendor'");
    }
}
