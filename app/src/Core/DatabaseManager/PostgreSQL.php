<?php

namespace Db3v4l\Core\DatabaseManager;

use Db3v4l\API\Interfaces\DatabaseManager;
use Db3v4l\API\Interfaces\SqlExecutor\Executor;
use Db3v4l\Core\SqlAction\Command;

class PostgreSQL extends BaseManager implements DatabaseManager
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
            "DROP DATABASE {$ifClause} \"$dbName\";",
        ];
        if ($userName != '') {
            $statements[] = "DROP USER {$ifClause} \"$userName\";";
        }
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

        return new Command([
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
            'SELECT collname AS Collation FROM pg_collation ORDER BY collname',
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
            'SELECT datname AS "Database" FROM pg_database ORDER BY datname;',
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
            'SELECT usename AS "User" FROM pg_catalog.pg_user ORDER BY usename;',
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
            'SHOW server_version;',
            function ($output, $executor) {
                /** @var Executor $executor */
                return $executor->resultSetToArray($output)[0];
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

        return $charset;
    }
}
