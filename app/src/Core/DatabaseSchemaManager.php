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
            //case 'oracle':
            case 'pgsql':
                return
                    "CREATE DATABASE \"$dbName\"" . // q: do we need to add 'TEMPLATE template0' ?
                    ($charset !== null ? " ENCODING $charset" : '') . /// @todo transform charset name into a supported one
                        "; COMMIT; CREATE USER \"$userName\" WITH PASSWORD '$password'" .
                        "; GRANT ALL ON DATABASE \"$dbName\" TO \"$userName\""; // q: should we avoid granting CREATE?
            //case 'sqlite':
            case 'sqlsrv':
                return
                    /// @see https://docs.microsoft.com/en-us/sql/tools/sqlcmd-utility
                    // When using sqlcmd, we are told _not_ to use GO as query terminator.
                    // Also, by default connections ar in autocommit mode...
                    // And yet, we need a GO to commit the db creation...
                    "SET QUOTED_IDENTIFIER ON; CREATE DATABASE \"$dbName\"" .
                    ($charset !== null ? " COLLATE $charset" : '') . /// @todo transform charset name into a supported one
                    "; CREATE LOGIN \"$userName\" WITH PASSWORD = '$password', DEFAULT_DATABASE = \"$dbName\", CHECK_POLICY = OFF, CHECK_EXPIRATION = OFF" .
                    ";\nGO\n" .
                    "USE \"$dbName\"" .
                    "; CREATE USER \"$userName\" FOR LOGIN \"$userName\"" .
                    ";  ALTER ROLE db_owner ADD MEMBER \"$userName\"";
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
            //case 'oracle':
            case 'pgsql':
                return
                    "DROP DATABASE IF EXISTS \"$dbName\"; DROP USER IF EXISTS \"$userName\";";
            //case 'sqlite':
            case 'sqlsrv':
                return
                    "SET QUOTED_IDENTIFIER ON; DROP DATABASE IF EXISTS \"$dbName\"; DROP USER IF EXISTS \"$userName\"; DROP LOGIN \"$userName\";";
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
            //case 'oracle':
            case 'pgsql':
                return
                    'SELECT datname AS "Database" FROM pg_database;';
            //case 'sqlite':
            case 'sqlsrv':
                return
                    // the way we create it, the user account is contained in the db
                    "SELECT name AS 'Database' FROM sys.databases";
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
                    'SELECT DISTINCT User FROM mysql.user ORDER BY User;';
            //case 'oracle':
            case 'pgsql':
                return
                    'SELECT usename AS "User" FROM pg_catalog.pg_user ORDER BY usename;';
            //case 'sqlite':
            case 'sqlsrv':
                return "SELECT name AS 'User' FROM sys.sql_logins ORDER BY name";
            default:
                throw new \OutOfBoundsException("Unsupported database type '$dbType'");
        }
    }

    /**
     * @see https://www.doctrine-project.org/projects/doctrine-dbal/en/2.9/reference/configuration.html for supported aliases
     * @param string $driver
     * @return string
     */
    protected function getDbTypeFromDriver($driver)
    {
        return str_replace(
            array('pdo_', 'mssql', 'mysql2', 'postgres', 'postgresql', 'sqlite3'),
            array('', 'sqlsrv', 'mysql', 'pgsql', 'pgsql', 'sqlite'),
            $driver
        );
    }
}
