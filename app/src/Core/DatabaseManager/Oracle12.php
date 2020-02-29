<?php

namespace Db3v4l\Core\DatabaseManager;

use Db3v4l\API\Interfaces\DatabaseManager;
use Db3v4l\API\Interfaces\SqlExecutor\Executor;
use Db3v4l\Core\SqlAction\Command;

class Oracle12 extends BaseManager implements DatabaseManager
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

        /// @todo throw if $charset is not the same as the db one

        // if treating 'database' as 'schema'...
        /// @todo what if password is empty?
        $statements = [
            "CREATE USER \"$dbName\" IDENTIFIED BY $password;",
            "GRANT CONNECT, RESOURCE TO \"$dbName\";",
            "GRANT UNLIMITED_TABLESPACE TO \"$dbName\";",
        ];
        if ($userName != '' && $userName != $dbName) {
            /// @what to do ?
            //$statements[] = "CREATE USER '$userName'@'%' IDENTIFIED BY '$password';";
            //$statements[] = "GRANT ALL PRIVILEGES ON `$dbName`.* TO '$userName'@'%';";
        }

        // if treating 'database' as 'pdb' (slower)
        $statements = [
            "CREATE PLUGGABLE DATABASE \"$dbName\" ADMIN USER \"$userName\" IDENTIFIED BY \"$password\" CREATE_FILE_DEST='/opt/oracle/oradata';",
            "ALTER PLUGGABLE DATABASE \"$dbName\" OPEN READ WRITE;"
        ];

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

        /// @todo check support for IF EXISTS
        $statements = [
            "DROP USER \"$dbName\";",
        ];
        if ($userName != '' && $userName != $dbName) {
            /// @todo what to do ?
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
            "DROP USER \"$userName\";",
        ]);
    }

    /**
     * Returns the sql 'action' used to list all available collations
     * @return Command
     */
    public function getListCollationsSqlAction()
    {
        return new Command(
            "SELECT value AS Collation FROM v\$nls_valid_values WHERE parameter = 'CHARACTERSET' ORDER BY value;",
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
            "SELECT username AS Database FROM sys.all_users WHERE oracle_maintained != 'Y' ORDER BY username;",
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
        // NB: we filter out 'system' users, as there are many...
            "SELECT username FROM sys.all_users WHERE oracle_maintained != 'Y' ORDER BY username;",
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
            "SELECT version_full || ' (' || banner || ')' AS version FROM  v\$version, v\$instance;",
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

        if ($charset == 'utf8') {
            $charset = 'AL32UTF8';
        }

        return $charset;
    }
}
