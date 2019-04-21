<?php

namespace Db3v4l\Service;

class DatabaseSchemaManager
{
    protected $databaseConfiguration;

    public function __construct(array $databaseConfiguration)
    {
        $this->databaseConfiguration = $databaseConfiguration;
    }

    /**
     * Returns the sql commands used to create a new db schema and accompanying user
     * @param string $userName used both for user and schema if passed schemaName is null. Max 16 chars for MySQL 5.5
     * @param string $password
     * @param string $schemaName Max 63 chars for Postgres
     * @param string $charset
     * @return string
     */
    public function getCreateSchemaSQL($userName, $password, $schemaName = null, $charset = null)
    {
        if ($schemaName === null) {
            $schemaName = $userName;
        }

        $dbType = $this->getDbTypeFromDriver($this->databaseConfiguration['driver']);

        switch ($dbType) {
            case 'mysql':
                return
                    "CREATE DATABASE `$schemaName`" .
                    ($charset !== null ? " CHARACTER SET $charset" : '') . /// @todo transform charset name into a supported one
                    "; CREATE USER '$userName'@'%' IDENTIFIED BY '$password'" .
                    "; GRANT ALL PRIVILEGES ON `$schemaName`.* TO '$userName'@'%';";
            case 'pgsql':
                return
                    "CREATE DATABASE \"$schemaName\"" . // q: do we need to add 'TEMPLATE template0' ?
                    ($charset !== null ? " ENCODING $charset" : '') . /// @todo transform charset name into a supported one
                        "; COMMIT; CREATE USER \"$userName\" WITH PASSWORD '$password'" .
                        "; GRANT ALL ON DATABASE \"$schemaName\" TO \"$userName\""; // q: should we avoid granting CREATE?
            default:
                throw new \OutOfBoundsException("Unsupported database type '$dbType'");
        }
    }

    /**
     * Returns the sql commands used to create a new db schema and accompanying user
     * @param string $userName
     * @param string $schemaName
     * @return string
     */
    public function getDropSchemaSQL($userName, $schemaName = null)
    {
        if ($schemaName === null) {
            $schemaName = $userName;
        }

        $dbType = $this->getDbTypeFromDriver($this->databaseConfiguration['driver']);

        switch ($dbType) {
            case 'mysql':
                return
                    "DROP DATABASE IF EXISTS `$schemaName`; DROP USER '$userName'@'%';";
            case 'pgsql':
                return
                    "DROP DATABASE IF EXISTS \"$schemaName\"; DROP USER IF EXISTS \"$userName\";";
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
