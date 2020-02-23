<?php

namespace Db3v4l\Core;

use Db3v4l\API\Interfaces\SqlAction\CommandAction;
use Db3v4l\API\Interfaces\SqlExecutor\Executor;
use Db3v4l\Core\SqlAction\Command;

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
     * Returns the sql 'action' used to create a new db and accompanying user
     * @param string $dbName Max 63 chars for Postgres
     * @param string $userName Max 16 chars for MySQL 5.5
     * @param string $password
     * @param string $charset charset/collation name
     * @return CommandAction
     * @throws OutOfBoundsException for unsupported database types
     * @todo prevent sql injection!
     */
    public function getCreateDatabaseSqlAction($dbName, $userName, $password, $charset = null)
    {
        $collation = $this->getCollationName($charset);

        switch ($this->databaseConfiguration['vendor']) {

            case 'mariadb':
            case 'mysql':
            case 'percona':
                /// @todo if mysql version is bigger than 8.0, add `WITH mysql_native_password`
                $statements = [
                    "CREATE DATABASE `$dbName`" . ($collation !== null ? " CHARACTER SET $collation" : '') . ';'
                ];
                if ($userName != '') {
                    $statements[] = "CREATE USER '$userName'@'%' IDENTIFIED BY '$password';";
                    $statements[] = "GRANT ALL PRIVILEGES ON `$dbName`.* TO '$userName'@'%';";
                }
                return new Command($statements);

            case 'mssql':
                $statements = [
                    /// @see https://docs.microsoft.com/en-us/sql/tools/sqlcmd-utility
                    // When using sqlcmd, we are told _not_ to use GO as query terminator.
                    // Also, by default connections are in autocommit mode...
                    // And yet, we need a GO to commit the db creation...
                    "SET QUOTED_IDENTIFIER ON;",
                    "CREATE DATABASE \"$dbName\"" . ($collation !== null ? " COLLATE $collation" : '') . ';'
                ];
                if ($userName != '') {
                    $statements[] = "CREATE LOGIN \"$userName\" WITH PASSWORD = '$password', DEFAULT_DATABASE = \"$dbName\", CHECK_POLICY = OFF, CHECK_EXPIRATION = OFF;";
                    $statements[] = "GO";
                    $statements[] = "USE \"$dbName\";";
                    $statements[] = "CREATE USER \"$userName\" FOR LOGIN \"$userName\";";
                    $statements[] = "ALTER ROLE db_owner ADD MEMBER \"$userName\";";
                }
                return new Command($statements);

            //case 'oracle':

            case 'postgresql':
                $statements = [
                    // q: do we need to add 'TEMPLATE template0' ?
                    //    see f.e. https://www.vertabelo.com/blog/collations-in-postgresql/
                    "CREATE DATABASE \"$dbName\"" . ($collation !== null ? " ENCODING $collation" : '') . ';',
                ];
                if ($userName != '') {
                    $statements[] = "COMMIT;";
                    $statements[] = "CREATE USER \"$userName\" WITH PASSWORD '$password'" . ';';
                    $statements[] = "GRANT ALL ON DATABASE \"$dbName\" TO \"$userName\""; // q: should we avoid granting CREATE?
                }
                return new Command($statements);

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
     * Returns the sql 'action' used to drop a db and associated user account
     * @param string $dbName
     * @param string $userName
     * @return CommandAction
     * @param bool $ifExists
     * @throws OutOfBoundsException for unsupported database types
     * @bug currently some DBs report failures for non-existing user, even when $ifExists = true
     * @todo prevent sql injection!
     */
    public function getDropDatabaseSqlAction($dbName, $userName, $ifExists = false)
    {
        $ifClause = '';
        if ($ifExists) {
            $ifClause = 'IF EXISTS';
        }

        switch ($this->databaseConfiguration['vendor']) {

            case 'mariadb':
            case 'mysql':
            case 'percona':
                $statements = [
                    "DROP DATABASE {$ifClause} `$dbName`;"
                ];
                if ($userName != '') {
                    /// @todo since mysql 5.7, 'DROP USER IF EXISTS' is supported. We could use it...
                    $statements[] = "DROP USER '$userName'@'%';";
                }
                return new Command($statements);

            case 'mssql':
                $statements = [
                    "SET QUOTED_IDENTIFIER ON;"
                ];
                if ($userName != '') {
                    // we assume users are 'local' to each db, as we create them by default
                    $statements[] = "USE \"$dbName\";";
                    $statements[] = "DROP LOGIN \"$userName\";";
                    $statements[] = "DROP USER {$ifClause} \"$userName\";";
                    $statements[] = "USE \"master\";";
                }
                $statements[] = "DROP DATABASE {$ifClause} \"$dbName\";";

                return new Command($statements);

            //case 'oracle':

            case 'postgresql':
                $statements = [
                    "DROP DATABASE {$ifClause} \"$dbName\";",
                ];
                if ($userName != '') {
                    $statements[] = "DROP USER {$ifClause} \"$userName\";";
                }
                return new Command($statements);

            case 'sqlite':
                $filename = dirname($this->databaseConfiguration['path']) . '/' . $dbName . '.sqlite';
                return new Command(
                    null,
                    function() use($filename, $dbName, $ifExists) {
                        if (is_file($filename)) {
                            unlink($filename);
                        } else {
                            if (!$ifExists) {
                                throw new \Exception("Can not drop database '$dbName': file not found");
                            }
                        }
                    }
                );

            default:
                throw new OutOfBoundsException("Unsupported database type '{$this->databaseConfiguration['vendor']}'");
        }
    }

    /**
     * @param string $userName
     * @param bool $ifExists
     * @return Command
     * @throws OutOfBoundsException for unsupported database types
     * @bug currently some DBs report failures for non-existing user, even when $ifExists = true
     * @todo prevent sql injection!
     */
    public function getDropUserSqlAction($userName, $ifExists = false)
    {
        $ifClause = '';
        if ($ifExists) {
            $ifClause = 'IF EXISTS';
        }

        switch ($this->databaseConfiguration['vendor']) {

            case 'mariadb':
            case 'mysql':
            case 'percona':
                /// @todo since mysql 5.7, 'DROP USER IF EXISTS' is supported. We could use it...
                return new Command([
                    "DROP USER '$userName'@'%';"
                ]);

            case 'mssql':
                /// @todo if the user is created inside a specific db, this will fail. We need to add a USE DB cmd 1st...
                ///       to find out if a user exists in the current db: SELECT DATABASE_PRINCIPAL_ID('$user');
                return new Command([
                    "SET QUOTED_IDENTIFIER ON;",
                    "DROP LOGIN \"$userName\";",
                    "DROP USER {$ifClause} \"$userName\";"
                ]);

            //case 'oracle':

            case 'postgresql':
                return new Command([
                    "DROP USER {$ifClause} \"$userName\";"
                ]);

            case 'sqlite':
                return new Command(
                    null,
                    function() {
                        /// @todo should we return something ?
                    }
                );

            default:
                throw new OutOfBoundsException("Unsupported database type '{$this->databaseConfiguration['vendor']}'");
        }
    }

    /**
     * Returns the sql 'action' used to list all available collations
     * @return CommandAction
     * @throws OutOfBoundsException for unsupported database types
     */
    public function getListCollationsSqlAction()
    {
        switch ($this->databaseConfiguration['vendor']) {

            case 'mariadb':
            case 'mysql':
            case 'percona':
                return new Command(
                    'SHOW COLLATION;',
                    function ($output, $executor) {
                        /** @var Executor $executor */
                        $lines = $executor->resultSetToArray($output);
                        $out = [];
                        foreach($lines as $line) {
                            $parts = explode("|", $line, 3);
                            $out[] = trim($parts[0]) . ' (' . trim($parts[1]) .')';
                        }
                        return $out;
                    }
                );

            //case 'oracle':

            case 'postgresql':
                return new Command(
                    'SELECT collname AS Collation FROM pg_collation ORDER BY collname',
                    function ($output, $executor) {
                        /** @var Executor $executor */
                        return $executor->resultSetToArray($output);
                    }
                );

            case 'sqlite':
                return new Command(
                    null,
                    /// @todo list the supported utf16 variants as soon as allow using them
                    function () {
                        return [];
                    }
                );
                /*return [
                    // q: are these comparable to other databases ? we probably should instead list the values for https://www.sqlite.org/pragma.html#pragma_encoding
                    'PRAGMA collation_list;',
                    function ($output, $executor) {
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
                    'SELECT name AS Collation FROM fn_helpcollations();',
                    function ($output, $executor) {
                        /** @var Executor $executor */
                        return $executor->resultSetToArray($output);
                    }
                );

            default:
                throw new OutOfBoundsException("Unsupported database type '{$this->databaseConfiguration['vendor']}'");
        }
    }

    /**
     * Returns the sql 'action' used to list all available databases
     * @return CommandAction
     * @throws OutOfBoundsException for unsupported database types
     * @todo for each database, retrieve the charset/collation
     */
    public function getListDatabasesSqlAction()
    {
        switch ($this->databaseConfiguration['vendor']) {

            case 'mariadb':
            case 'mysql':
            case 'percona':
                return new Command(
                    /// @todo use 'SHOW DATABASES' for versions < 5
                    "SELECT SCHEMA_NAME AS 'Database' FROM information_schema.SCHEMATA ORDER BY SCHEMA_NAME;",
                    function ($output, $executor) {
                        /** @var Executor $executor */
                        return $executor->resultSetToArray($output);
                    }
                );

            case 'mssql':
                return new Command(
                    // the way we create it, the user account is contained in the db
                    // @todo add "WHERE name NOT IN ('master', 'tempdb', 'model', 'msdb')" ?
                    "SELECT name AS 'Database' FROM sys.databases ORDER BY name;",
                    function ($output, $executor) {
                        /** @var Executor $executor */
                        return $executor->resultSetToArray($output);
                    }
                );

            case 'oracle':
                return new Command(
                    // @todo add "WHERE name NOT IN ('...')" ?
                    "SELECT username AS schema_name FROM sys.all_users ORDER BY username;",
                    function ($output, $executor) {
                        /** @var Executor $executor */
                        return $executor->resultSetToArray($output);
                    }
                );

            case 'postgresql':
                return new Command(
                    'SELECT datname AS "Database" FROM pg_database ORDER BY datname;',
                    function ($output, $executor) {
                        /** @var Executor $executor */
                        return $executor->resultSetToArray($output);
                    }
                );

            case 'sqlite':
                $fileGlob = dirname($this->databaseConfiguration['path']) . '/*.sqlite';
                return new Command(
                    null,
                    function() use ($fileGlob) {
                        $out = [];
                        foreach (glob($fileGlob) as $filename) {
                            $out[] =  basename($filename);
                        }
                        return $out;
                    }
                );

            default:
                throw new OutOfBoundsException("Unsupported database type '{$this->databaseConfiguration['vendor']}'");
        }
    }

    /**
     * Returns the sql 'action' used to list all existing db users
     * @return CommandAction
     * @throws OutOfBoundsException for unsupported database types
     */
    public function getListUsersSqlAction()
    {
        switch ($this->databaseConfiguration['vendor']) {

            case 'mariadb':
            case 'mysql':
            case 'percona':
                return new Command(
                    'SELECT DISTINCT User FROM mysql.user ORDER BY User;',
                    function ($output, $executor) {
                        /** @var Executor $executor */
                        return $executor->resultSetToArray($output);
                    }
                );

            case 'mssql':
                return new Command(
                    "SELECT name AS 'User' FROM sys.sql_logins ORDER BY name",
                    function ($output, $executor) {
                        /** @var Executor $executor */
                        return $executor->resultSetToArray($output);
                    }
                );

            //case 'oracle':

            case 'postgresql':
                return new Command(
                    'SELECT usename AS "User" FROM pg_catalog.pg_user ORDER BY usename;',
                    function ($output, $executor) {
                        /** @var Executor $executor */
                        return $executor->resultSetToArray($output);
                    }
                );

            case 'sqlite':
                return new Command(
                    null,
                    function () {
                        // since sqlite does not support users, null seems more appropriate than an empty array...
                        return null;
                    }
                );

            default:
                throw new OutOfBoundsException("Unsupported database type '{$this->databaseConfiguration['vendor']}'");
        }
    }

    /**
     * Returns the sql 'action' used to retrieve the db instance version info
     * @return Command
     */
    public function getRetrieveVersionInfoSqlAction()
    {
        switch ($this->databaseConfiguration['vendor']) {

            case 'mariadb':
            case 'mysql':
            case 'percona':
                return new Command(
                    'SHOW VARIABLES LIKE "version";',
                    function ($output, $executor) {
                        /** @var Executor $executor */
                        $line = $executor->resultSetToArray($output)[0];
                        $parts = explode('|', $line);
                        return trim($parts[1]);
                    }
                );

            case 'oracle':
                return new Command(
                    "SELECT * FROM v\$version WHERE banner LIKE 'Oracle%';",
                    function ($output, $executor) {
                        /** @var Executor $executor */
                        return $executor->resultSetToArray($output)[0];
                    }
                );

            case 'postgresql':
                return new Command(
                    'SHOW server_version;',
                    function ($output, $executor) {
                        /** @var Executor $executor */
                        return $executor->resultSetToArray($output)[0];
                    }
                );

            case 'sqlite':
                return new Command(
                    "select sqlite_version();",
                    function ($output, $executor) {
                        /** @var Executor $executor */
                        return $executor->resultSetToArray($output)[0];
                    }
                );

            case 'mssql':
                return new Command(
                    "SELECT @@version",
                    function ($output, $executor) {
                        /** @var Executor $executor */
                        $output = $executor->resultSetToArray($output);
                        $line = $output[0];
                        preg_match('/Microsoft SQL Server +([^ ]+) +([^ ]+) +/', $line, $matches);
                        return $matches[1] . ' ' . $matches[2];
                    }
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

            case 'percona':
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

            //case 'oracle':
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
