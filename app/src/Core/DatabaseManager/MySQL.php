<?php

namespace Db3v4l\Core\DatabaseManager;

use Db3v4l\API\Interfaces\DatabaseManager;
use Db3v4l\API\Interfaces\SqlExecutor\Executor;
use Db3v4l\Core\SqlAction\Command;

class MySQL extends BaseManager implements DatabaseManager
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

        /// @todo if mysql version is bigger than 8.0, add `WITH mysql_native_password`
        $statements = [
            "CREATE DATABASE `$dbName`" . ($collation !== null ? " CHARACTER SET $collation" : '') . ';'
        ];
        if ($userName != '') {
            $statements[] = "CREATE USER '$userName'@'%' IDENTIFIED BY '$password';";
            $statements[] = "GRANT ALL PRIVILEGES ON `$dbName`.* TO '$userName'@'%';";
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
            "DROP DATABASE {$ifClause} `$dbName`;"
        ];
        if ($userName != '') {
            /// @todo since mysql 5.7, 'DROP USER IF EXISTS' is supported. We could use it...
            $statements[] = "DROP USER '$userName'@'%';";
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

        /// @todo since mysql 5.7, 'DROP USER IF EXISTS' is supported. We could use it...
        return new Command([
            "DROP USER '$userName'@'%';"
        ]);
    }

    /**
     * Returns the sql 'action' used to list all available collations
     * @return Command
     */
    public function getListCollationsSqlAction()
    {
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
    }

    /**
     * Returns the sql 'action' used to list all available databases
     * @return Command
     * @todo for each database, retrieve the charset/collation
     */
    public function getListDatabasesSqlAction()
    {
        return new Command(
            /// @todo use 'SHOW DATABASES' for versions < 5
            "SELECT SCHEMA_NAME AS 'Database' FROM information_schema.SCHEMATA ORDER BY SCHEMA_NAME;",
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
            'SELECT DISTINCT User FROM mysql.user ORDER BY User;',
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
            'SHOW VARIABLES LIKE "version";',
            function ($output, $executor) {
                /** @var Executor $executor */
                $line = $executor->resultSetToArray($output)[0];
                $parts = explode('|', $line);
                return trim($parts[1]);
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
