<?php

namespace Db3v4l\Core;

use Db3v4l\API\Interfaces\SqlAction\CommandAction;
use Db3v4l\Core\SqlAction\Command;
use Db3v4l\Core\SqlAction\File;
use OutOfBoundsException;

/**
 * @see \Doctrine\DBAL\Platforms for more comprehensive abstractions of per-platform sql
 */
class DatabaseSchemaManager
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

    /**
     * Returns the sql commands used to create a new db and accompanying user
     * @param string $userName used both for user and db if passed dbName is null. Max 16 chars for MySQL 5.5
     * @param string $password
     * @param string $dbName Max 63 chars for Postgres
     * @param string $charset charset/collation name
     * @return CommandAction
     * @throws OutOfBoundsException for unsupported database types
     */
    public function getCreateDatabaseSqlAction($userName, $password, $dbName = null, $charset = null)
    {
        if ($dbName === null) {
            $dbName = $userName;
        }

        $collation = $this->getCollationName($charset);

        switch ($this->databaseConfiguration['vendor']) {
            case 'mariadb':
            case 'mysql':
                return new Command([
                    "CREATE DATABASE `$dbName`" . ($collation !== null ? " CHARACTER SET $collation" : '') . ';',
                    "CREATE USER '$userName'@'%' IDENTIFIED BY '$password';",
                    "GRANT ALL PRIVILEGES ON `$dbName`.* TO '$userName'@'%';"
                ]);
            case 'mssql':
                return new Command([
                    /// @see https://docs.microsoft.com/en-us/sql/tools/sqlcmd-utility
                    // When using sqlcmd, we are told _not_ to use GO as query terminator.
                    // Also, by default connections are in autocommit mode...
                    // And yet, we need a GO to commit the db creation...
                    "SET QUOTED_IDENTIFIER ON;",
                    "CREATE DATABASE \"$dbName\"" . ($collation !== null ? " COLLATE $collation" : '') . ';',
                    "CREATE LOGIN \"$userName\" WITH PASSWORD = '$password', DEFAULT_DATABASE = \"$dbName\", CHECK_POLICY = OFF, CHECK_EXPIRATION = OFF;" ,
                    "GO",
                    "USE \"$dbName\";",
                    "CREATE USER \"$userName\" FOR LOGIN \"$userName\";",
                    "ALTER ROLE db_owner ADD MEMBER \"$userName\";"
                ]);
            //case 'oracle':
            case 'postgresql':
                return  new Command([
                    // q: do we need to add 'TEMPLATE template0' ?
                    //    see f.e. https://www.vertabelo.com/blog/collations-in-postgresql/
                    "CREATE DATABASE \"$dbName\"" . ($collation !== null ? " ENCODING $collation" : '') . ';',
                    "COMMIT;",
                    "CREATE USER \"$userName\" WITH PASSWORD '$password'" . ';',
                    "GRANT ALL ON DATABASE \"$dbName\" TO \"$userName\"" // q: should we avoid granting CREATE?
                ]);
            case 'sqlite':
                /// @todo this does not support creation of the new db with a different character encoding...
                ///       see https://stackoverflow.com/questions/21348459/set-pragma-encoding-utf-16-for-main-database-in-sqlite
                $filename = dirname($this->databaseConfiguration['path']) . '/' . $dbName . '.sqlite';
                return new Command(
                    "ATTACH '$filename' AS \"$dbName\";"
                );
            default:
                throw new OutOfBoundsException("Unsupported database type '{$this->databaseConfiguration['vendor']}'");
        }
    }

    /**
     * Returns the sql commands used to drop a db
     * @param string $userName
     * @param string $dbName
     * @return CommandAction
     * @throws OutOfBoundsException for unsupported database types
     * @todo currently some DBs report failures for non-existing user/db, some do not...
     */
    public function getDropDatabaseSqlAction($userName, $dbName = null)
    {
        if ($dbName === null) {
            $dbName = $userName;
        }

        switch ($this->databaseConfiguration['vendor']) {

            case 'mariadb':
            case 'mysql':
                /// @todo since mysql 5.7, 'DROP USER IF EXISTS' is supported. We could use it...
                return new Command([
                    "DROP DATABASE IF EXISTS `$dbName`;",
                    "DROP USER '$userName'@'%';"
                ]);
            case 'mssql':
                return new Command([
                    "SET QUOTED_IDENTIFIER ON;",
                    "DROP DATABASE IF EXISTS \"$dbName\";",
                    "DROP USER IF EXISTS \"$userName\";",
                    "DROP LOGIN \"$userName\";"
                ]);
            //case 'oracle':
            case 'postgresql':
                return new Command([
                    "DROP DATABASE IF EXISTS \"$dbName\";",
                    "DROP USER IF EXISTS \"$userName\";"
                ]);
            case 'sqlite':
                $filename = dirname($this->databaseConfiguration['path']) . '/' . $dbName . '.sqlite';
                return new Command(
                    null,
                    function() use($filename, $dbName) {
                        if (is_file($filename)) {
                            unlink($filename);
                        } else {
                            throw new \Exception("Can not drop database '$dbName': file not found");
                        }
                    }
                );
            default:
                throw new OutOfBoundsException("Unsupported database type '{$this->databaseConfiguration['vendor']}'");
        }
    }

    /**
     * @param string $sql
     * @return Command
     */
    public function getExecuteCommandSqlAction($sql)
    {
        return new Command($sql);
    }

    /**
     * @param string $filename
     * @return File
     */
    public function getExecuteFileSqlAction($filename)
    {
        return new File($filename);
    }

    /**
     * List all available collations
     * @return CommandAction
     * @throws OutOfBoundsException for unsupported database types
     */
    public function getListCollationsSqlAction()
    {
        switch ($this->databaseConfiguration['vendor']) {
            case 'mariadb':
            case 'mysql':
                return new Command(
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
                );
            //case 'oci':
            case 'postgresql':
                return new Command(
                    'SELECT collname AS Collation FROM pg_collation ORDER BY collname'
                );
            case 'sqlite':
                return new Command(
                    null,
                    function () {
                        return '';
                    }
                );
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
                return new Command(
                    'SELECT name AS Collation FROM fn_helpcollations();'
                );
            default:
                throw new OutOfBoundsException("Unsupported database type '{$this->databaseConfiguration['vendor']}'");
        }
    }

    /**
     * @return CommandAction
     * @throws OutOfBoundsException for unsupported database types
     */
    public function getListDatabasesSqlAction()
    {
        switch ($this->databaseConfiguration['vendor']) {
            case 'mariadb':
            case 'mysql':
                return new Command(
                    /// @todo use 'SHOW DATABASES' for versions < 5
                    "SELECT SCHEMA_NAME AS 'Database' FROM information_schema.SCHEMATA ORDER BY SCHEMA_NAME;"
                );
            case 'mssql':
                return new Command(
                    // the way we create it, the user account is contained in the db
                    // @todo add "WHERE name NOT IN ('master', 'tempdb', 'model', 'msdb')" ?
                    "SELECT name AS 'Database' FROM sys.databases ORDER BY name;"
                );
            //case 'oracle':
            case 'postgresql':
                return new Command(
                    'SELECT datname AS "Database" FROM pg_database ORDER BY datname;'
                );
            case 'sqlite':
                $fileGlob = dirname($this->databaseConfiguration['path']) . '/*.sqlite';
                return new Command(
                    null,
                    function() use ($fileGlob) {
                        $out = "Database";
                        foreach (glob($fileGlob) as $filename) {
                            $out .= "\n" . basename($filename);
                        }
                        return $out;
                    }
                );
            default:
                throw new OutOfBoundsException("Unsupported database type '{$this->databaseConfiguration['vendor']}'");
        }
    }

    /**
     * @return CommandAction
     * @throws OutOfBoundsException for unsupported database types
     */
    public function getListUsersSqlAction()
    {
        switch ($this->databaseConfiguration['vendor']) {
            case 'mariadb':
            case 'mysql':
                return new Command(
                    'SELECT DISTINCT User FROM mysql.user ORDER BY User;'
                );
            //case 'oci':
            case 'postgresql':
                return new Command(
                    'SELECT usename AS "User" FROM pg_catalog.pg_user ORDER BY usename;'
                );
            case 'sqlite':
                return new Command(
                    null,
                    function () {
                        return '';
                    }
                );
            case 'mssql':
                return new Command(
                    "SELECT name AS 'User' FROM sys.sql_logins ORDER BY name"
                );
            default:
                throw new OutOfBoundsException("Unsupported database type '{$this->databaseConfiguration['vendor']}'");
        }
    }

    public function getRetrieveVersionInfoSqlAction()
    {
        switch ($this->databaseConfiguration['vendor']) {
            case 'mariadb':
            case 'mysql':
                return new Command(
                    'SELECT DISTINCT User FROM mysql.user ORDER BY User;'
                );
            //case 'oci':
            case 'postgresql':
                return new Command(
                    'SELECT usename AS "User" FROM pg_catalog.pg_user ORDER BY usename;'
                );
            case 'sqlite':
                return new Command(
                    null,
                    function () {
                        return '';
                    }
                );
            case 'mssql':
                return new Command(
                    "SELECT @@version"
                );
            default:
                throw new OutOfBoundsException("Unsupported database type '{$this->databaseConfiguration['vendor']}'");
        }
    }

    /**
     * Transform collation name into a supported one
     * @param null|string $charset so far only 'utf8' is supported...
     * @return null|string
     * @throws OutOfBoundsException for unsupported database types
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
