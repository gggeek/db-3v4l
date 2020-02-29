<?php

namespace Db3v4l\Core\DatabaseManager;

use Db3v4l\API\Interfaces\DatabaseManager;
use Db3v4l\API\Interfaces\SqlExecutor\Executor;
use Db3v4l\Core\SqlAction\Command;

class SQLServer extends BaseManager implements DatabaseManager
{
    /**
     * Returns the sql 'action' used to create a new db and accompanying user
     * @param string $dbName Max 63 chars for Postgres
     * @param string $userName Max 16 chars for MySQL 5.5
     * @param string $password
     * @param string $charset charset/collation name
     * @return Command
     * @todo prevent sql injection!
     */
    public function getCreateDatabaseSqlAction($dbName, $userName, $password, $charset = null)
    {
        $collation = $this->getCollationName($charset);

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
    }

    /**
     * Returns the sql 'action' used to drop a db and associated user account
     * @param string $dbName
     * @param string $userName
     * @return Command
     * @param bool $ifExists
     * @bug currently some DBs report failures for non-existing user, even when $ifExists = true
     * @todo prevent sql injection!
     */
    public function getDropDatabaseSqlAction($dbName, $userName, $ifExists = false)
    {
        $ifClause = '';
        if ($ifExists) {
            $ifClause = 'IF EXISTS';
        }

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
    }

    /**
     * @param string $userName
     * @param bool $ifExists
     * @return Command
     * @bug currently some DBs report failures for non-existing user, even when $ifExists = true
     * @todo prevent sql injection!
     */
    public function getDropUserSqlAction($userName, $ifExists = false)
    {
        $ifClause = '';
        if ($ifExists) {
            $ifClause = 'IF EXISTS';
        }

        /// @todo if the user is created inside a specific db, this will fail. We need to add a USE DB cmd 1st...
        ///       to find out if a user exists in the current db: SELECT DATABASE_PRINCIPAL_ID('$user');
        return new Command([
            "SET QUOTED_IDENTIFIER ON;",
            "DROP LOGIN \"$userName\";",
            "DROP USER {$ifClause} \"$userName\";"
        ]);
    }

    /**
     * Returns the sql 'action' used to list all available collations
     * @return Command
     */
    public function getListCollationsSqlAction()
    {
        return new Command(
            'SELECT name AS Collation FROM fn_helpcollations();',
            function ($output, $executor) {
                /** @var Executor $executor */
                return $executor->resultSetToArray($output);
            }
        );
    }

    /**
     * Returns the sql 'action' used to list all available databases
     * @return Command
     * @todo for each database, retrieve the charset/collation
     */
    public function getListDatabasesSqlAction()
    {
        return new Command(
        // the way we create it, the user account is contained in the db
        // @todo add "WHERE name NOT IN ('master', 'tempdb', 'model', 'msdb')" ?
            "SELECT name AS 'Database' FROM sys.databases ORDER BY name;",
            function ($output, $executor) {
                /** @var Executor $executor */
                return $executor->resultSetToArray($output);
            }
        );
    }

    /**
     * Returns the sql 'action' used to list all existing db users
     * @return Command
     */
    public function getListUsersSqlAction()
    {
        return new Command(
            "SELECT name AS 'User' FROM sys.sql_logins ORDER BY name",
            function ($output, $executor) {
                /** @var Executor $executor */
                return $executor->resultSetToArray($output);
            }
        );
    }

    /**
     * Returns the sql 'action' used to retrieve the db instance version info
     * @return Command
     */
    public function getRetrieveVersionInfoSqlAction()
    {
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
    }

    /**
     * Transform collation name into a supported one
     * @param null|string $charset so far only 'utf8' is supported...
     * @return null|string
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

        return $charset;
    }
}
