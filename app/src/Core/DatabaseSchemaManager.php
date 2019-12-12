<?php

namespace Db3v4l\Core;

use \OutOfBoundsException;

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
     * @param string $charset charset/collation name
     * @return string
     * @throws OutOfBoundsException for unsupported database types
     */
    public function getCreateDatabaseSQL($userName, $password, $dbName = null, $charset = null)
    {
        if ($dbName === null) {
            $dbName = $userName;
        }

        $collation = $this->getCollationName($charset);

        switch ($this->databaseConfiguration['vendor']) {
            case 'mariadb':
            case 'mysql':
                return
                    "CREATE DATABASE `$dbName`" .
                    ($collation !== null ? " CHARACTER SET $collation" : '') .
                    "; CREATE USER '$userName'@'%' IDENTIFIED BY '$password'" .
                    "; GRANT ALL PRIVILEGES ON `$dbName`.* TO '$userName'@'%';";
            case 'mssql':
                return
                    /// @see https://docs.microsoft.com/en-us/sql/tools/sqlcmd-utility
                    // When using sqlcmd, we are told _not_ to use GO as query terminator.
                    // Also, by default connections ar in autocommit mode...
                    // And yet, we need a GO to commit the db creation...
                    "SET QUOTED_IDENTIFIER ON; CREATE DATABASE \"$dbName\"" .
                    ($collation !== null ? " COLLATE $collation" : '') .
                    "; CREATE LOGIN \"$userName\" WITH PASSWORD = '$password', DEFAULT_DATABASE = \"$dbName\", CHECK_POLICY = OFF, CHECK_EXPIRATION = OFF" .
                    ";\nGO\n" .
                    "USE \"$dbName\"" .
                    "; CREATE USER \"$userName\" FOR LOGIN \"$userName\"" .
                    ";  ALTER ROLE db_owner ADD MEMBER \"$userName\"";
            //case 'oracle':
            case 'postgresql':
                return
                    "CREATE DATABASE \"$dbName\"" .
                    // q: do we need to add 'TEMPLATE template0' ?
                    //    see f.e. https://www.vertabelo.com/blog/collations-in-postgresql/
                    ($collation !== null ? " ENCODING $collation" : '') .
                        "; COMMIT; CREATE USER \"$userName\" WITH PASSWORD '$password'" .
                        "; GRANT ALL ON DATABASE \"$dbName\" TO \"$userName\""; // q: should we avoid granting CREATE?
            case 'sqlite':
                /// @todo this does not support creation of the new db with a different character encoding...
                ///       see https://stackoverflow.com/questions/21348459/set-pragma-encoding-utf-16-for-main-database-in-sqlite
                $fileName = dirname($this->databaseConfiguration['path']) . '/' . $dbName . '.sqlite';
                return
                    "ATTACH '$fileName' AS \"$dbName\";";
            default:
                throw new OutOfBoundsException("Unsupported database type '{$this->databaseConfiguration['vendor']}'");
        }
    }

    /**
     * Returns the sql commands used to drop a db
     * @param string $userName
     * @param string $dbName
     * @return string|Callable a function when the only way to drop a database is to do something more complex than a sql query
     * @throws OutOfBoundsException for unsupported database types
     */
    public function getDropDatabaseQL($userName, $dbName = null)
    {
        if ($dbName === null) {
            $dbName = $userName;
        }

        switch ($this->databaseConfiguration['vendor']) {

            case 'mariadb':
            case 'mysql':
                return
                    "DROP USER '$userName'@'%'; DROP DATABASE IF EXISTS `$dbName`;";
            case 'mssql':
                return
                    "SET QUOTED_IDENTIFIER ON; DROP DATABASE IF EXISTS \"$dbName\"; DROP USER IF EXISTS \"$userName\"; DROP LOGIN \"$userName\";";
            //case 'oracle':
            case 'postgresql':
                return
                    "DROP DATABASE IF EXISTS \"$dbName\"; DROP USER IF EXISTS \"$userName\";";
            case 'sqlite':
                $fileName = dirname($this->databaseConfiguration['path']) . '/' . $dbName . '.sqlite';
                return function() use($fileName) {
                    unlink($fileName);
                };
            default:
                throw new OutOfBoundsException("Unsupported database type '{$this->databaseConfiguration['vendor']}'");
        }
    }

    /**
     * @return string|Callable a function when the only way to list databases is to do something more complex than a sql query
     * @throws OutOfBoundsException for unsupported database types
     */
    public function getListDatabasesSQL()
    {
        switch ($this->databaseConfiguration['vendor']) {
            case 'mariadb':
            case 'mysql':
                return
                    /// @todo use 'SHOW DATABASES' for versions < 5
                    "SELECT SCHEMA_NAME AS 'Database' FROM information_schema.SCHEMATA ORDER BY SCHEMA_NAME;";
            case 'mssql':
                return
                    // the way we create it, the user account is contained in the db
                    // @todo add "WHERE name NOT IN ('master', 'tempdb', 'model', 'msdb')" ?
                    "SELECT name AS 'Database' FROM sys.databases ORDER BY name;";
            //case 'oracle':
            case 'postgresql':
                return
                    'SELECT datname AS "Database" FROM pg_database ORDER BY datname;';
            case 'sqlite':
                $fileGlob = dirname($this->databaseConfiguration['path']) . '/*.sqlite';
                return function() use ($fileGlob) {
                    $out = "Database";
                    foreach (glob($fileGlob) as $fileName) {
                        $out .= "\n" . basename($fileName);
                    }
                    return $out;
                };
            default:
                throw new OutOfBoundsException("Unsupported database type '{$this->databaseConfiguration['vendor']}'");
        }
    }

    /**
     * @return string|Callable when the only way to list users is to do something more complex than a sql query
     * @throws OutOfBoundsException for unsupported database types
     */
    public function getListUsersSQL()
    {
        switch ($this->databaseConfiguration['vendor']) {
            case 'mariadb':
            case 'mysql':
                return
                    'SELECT DISTINCT User FROM mysql.user ORDER BY User;';
            //case 'oci':
            case 'postgresql':
                return
                    'SELECT usename AS "User" FROM pg_catalog.pg_user ORDER BY usename;';
            case 'sqlite':
                return function () {
                    return '';
                };
            case 'mssql':
                return "SELECT name AS 'User' FROM sys.sql_logins ORDER BY name";
            default:
                throw new OutOfBoundsException("Unsupported database type '{$this->databaseConfiguration['vendor']}'");
        }
    }

    /**
     * List all available collations
     * @return \Closure|string|array string: plain sql to execute
     *                               array: [string, \Closure] execute some sql and filter the results with a function
     *                               \Closure: execute the closure
     */
    public function getListCollationsSQL()
    {
        switch ($this->databaseConfiguration['vendor']) {
            case 'mariadb':
            case 'mysql':
                return [
                    'SHOW COLLATION;',
                    function ($output) {
                        $out = [];
                        foreach(explode("\n", $output) as $line) {
                            $out[] = explode("\t", $line, 2)[0];
                        }
                        $title = array_shift($out);
                        sort($out);
                        array_unshift($out, $title);
                        return implode("\n", $out);
                    }
                ];
            //case 'oci':
            case 'postgresql':
                return
                    'SELECT collname AS Collation FROM pg_collation ORDER BY collname';
            case 'sqlite':
                return function () {
                    return '';
                };
                /*return [
                    // q: are these comparable to other databases ? we probably should instead list the values for https://www.sqlite.org/pragma.html#pragma_encoding
                    'PRAGMA collation_list;',
                    function ($output) {
                        $out = [];
                        foreach(explode("\n", $output) as $line) {
                            $out[] = explode("|", $line, 2)[1];
                        }
                        sort($out);
                        return implode("\n", $out);
                    }
                ];*/
            case 'mssql':
                return
                    'SELECT name AS Collation FROM fn_helpcollations();';
            default:
                throw new OutOfBoundsException("Unsupported database type '{$this->databaseConfiguration['vendor']}'");
        }
    }

    /**
     * Transform ccollation name into a supported one
     * @param null|string $charset so far only 'utf8' is supported...
     * @return null|string
     * @throws OutOfBoundsException
     * @todo implement
     * @todo what shall we accept as valid input, ie. 'generic' charset names ? maybe do 2 passes: known-db-charset => generic => specific for each db ?
     *       see: https://www.iana.org/assignments/character-sets/character-sets.xhtml for IANA names
     */
    protected function getCollationName($charset)
    {
        if ($charset == null) {
            return null;
        }

        $charset = trim(strtolower($charset));

        // accept official iana charset name, but most dbs prefer 'utf8'...
        if ($charset == 'utf-8') {
            $charset = 'utf8';
        }

        switch ($this->databaseConfiguration['vendor']) {
            case 'mariadb':
                break;

            case 'mysql':
                break;

            case 'mssql':
                if ($charset == 'utf8') {
                    if (version_compare(
                        str_replace(array('.ga', '.cu'), array('.0', '.'), $this->databaseConfiguration['version']),
                        '2019',
                        '>=')
                    ) {
                        /// @todo allow to set this via configuration
                        // default collation for sql server on Linux is SQL_Latin1_General_CP1_CI_AS; we use the UTF8 variant
                        $charset = 'Latin1_General_100_CI_AI_SC_UTF8';
                    }
                }
                break;

            //case 'oci':
            //    break;

            case 'postgresql':
                break;

            case 'sqlite':
                break;

            default:
                throw new OutOfBoundsException("Unsupported database type '{$this->databaseConfiguration['vendor']}'");
        }

        return $charset;
    }
}
