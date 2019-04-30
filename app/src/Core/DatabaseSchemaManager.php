<?php

namespace Db3v4l\Core;

class DatabaseSchemaManager
{
    protected $databaseConfiguration;

    public function __construct(array $databaseConfiguration)
    {
        $this->databaseConfiguration = $databaseConfiguration;
    }

    /**
     * Returns the sql commands used to create a new db and accompanying user
     * @param string $userName used both for user and db if passed dbName is null. Max 16 chars for MySQL 5.5
     * @param string $password
     * @param string $dbName Max 63 chars for Postgres
     * @param string $charset
     * @return string
     */
    public function getCreateDatabaseSQL($userName, $password, $dbName = null, $charset = null)
    {
        if ($dbName === null) {
            $dbName = $userName;
        }

        $dbType = $this->getDbTypeFromDriver($this->databaseConfiguration['driver']);

        switch ($dbType) {
            case 'mysql':
                return
                    "CREATE DATABASE `$dbName`" .
                    ($charset !== null ? " CHARACTER SET $charset" : '') . /// @todo transform charset name into a supported one
                    "; CREATE USER '$userName'@'%' IDENTIFIED BY '$password'" .
                    "; GRANT ALL PRIVILEGES ON `$dbName`.* TO '$userName'@'%';";
            case 'pgsql':
                return
                    "CREATE DATABASE \"$dbName\"" . // q: do we need to add 'TEMPLATE template0' ?
                    ($charset !== null ? " ENCODING $charset" : '') . /// @todo transform charset name into a supported one
                        "; COMMIT; CREATE USER \"$userName\" WITH PASSWORD '$password'" .
                        "; GRANT ALL ON DATABASE \"$dbName\" TO \"$userName\""; // q: should we avoid granting CREATE?
            default:
                throw new \OutOfBoundsException("Unsupported database type '$dbType'");
        }
    }

    /**
     * Returns the sql commands used to drop a db
     * @param string $userName
     * @param string $dbName
     * @return string
     */
    public function getDropDatabaseQL($userName, $dbName = null)
    {
        if ($dbName === null) {
            $dbName = $userName;
        }

        $dbType = $this->getDbTypeFromDriver($this->databaseConfiguration['driver']);

        switch ($dbType) {
            case 'mysql':
                return
                    "DROP USER '$userName'@'%'; DROP DATABASE IF EXISTS `$dbName`;";
            case 'pgsql':
                return
                    "DROP DATABASE IF EXISTS \"$dbName\"; DROP USER IF EXISTS \"$userName\";";
            default:
                throw new \OutOfBoundsException("Unsupported database type '$dbType'");
        }
    }

    /**
     * @return string
     */
    public function getListInstancesSQL()
    {
        $dbType = $this->getDbTypeFromDriver($this->databaseConfiguration['driver']);

        switch ($dbType) {
            case 'mysql':
                return
                    'SHOW DATABASES;';
            case 'pgsql':
                return
                    'SELECT datname AS "Database" FROM pg_database;';
            default:
                throw new \OutOfBoundsException("Unsupported database type '$dbType'");
        }
    }

    public function getListUsersSQL()
    {
        $dbType = $this->getDbTypeFromDriver($this->databaseConfiguration['driver']);

        switch ($dbType) {
            case 'mysql':
                return
                    'SELECT User FROM mysql.user;';
            case 'pgsql':
                return
                    'SELECT usename AS "User" FROM pg_catalog.pg_user;';
            default:
                throw new \OutOfBoundsException("Unsupported database type '$dbType'");
        }
    }

    /**
     * @param string $driver
     * @return string
     */
    protected function getDbTypeFromDriver($driver)
    {
        return str_replace('pdo_', '', $driver);
    }
}
