<?php

namespace Db3v4l\Core\DatabaseManager;

use Db3v4l\API\Interfaces\DatabaseManager;
use Db3v4l\API\Interfaces\SqlExecutor\Executor;
use Db3v4l\Core\SqlAction\Command;

/**
 * This DBManager uses schemas as 'database'.
 * NB: if used with Oracle >= 12, usernames have to be prefixed with c## to be 'common' (ie. in the CDB), so you might
 *     want to run everything inside a pdb...
 */
class Oracle extends BaseManager implements DatabaseManager
{
    /**
     * Returns the sql 'action' used to list all available databases
     * @return Command
     * @todo for each database, retrieve the charset/collation
     */
    public function getListDatabasesSqlAction()
    {
        return new Command(
            "SELECT username AS Database FROM all_users WHERE oracle_maintained != 'Y' ORDER BY username;",
            function ($output, $executor) {
                /** @var Executor $executor */
                return $executor->resultSetToArray($output);
            }
        );
    }

    /**
     * Returns the sql 'action' used to create a new db and accompanying user
     * @param string $dbName
     * @param string $userName
     * @param string $password
     * @param string $charset charset/collation name
     * @return Command
     * @throws \Exception
     * @todo prevent sql injection!
     */
    public function getCreateDatabaseSqlAction($dbName, $userName, $password, $charset = null)
    {
        //$collation = $this->getCollationName($charset);

        /// @todo throw if $charset is not the same as the db one

        if ($userName != '' && $userName != $dbName) {
            throw new \Exception("Can not create a database (schema) on Oracle with a different accompanying user");
        }

        // NB: if we use a quoted identifier for the username, logging in becomes more difficult, as it will require quotes too...
        $statements = [
            "CREATE USER $dbName IDENTIFIED BY \"$password\";",
            "GRANT CONNECT, RESOURCE TO $dbName;",
            "GRANT UNLIMITED TABLESPACE TO $dbName;",
        ];

        return new Command($statements);
    }

    /**
     * Returns the sql 'action' used to drop a db and associated user account
     * @param string $dbName
     * @param string $userName
     * @return Command
     * @param bool $ifExists
     * @bug $ifExists = true not supported
     * @throws \Exception
     * @todo prevent sql injection!
     */
    public function getDropDatabaseSqlAction($dbName, $userName, $ifExists = false)
    {
        if ($userName != '' && $userName != $dbName) {
            throw new \Exception("Can not drop a database (schema) on Oracle with a different accompanying user");
        }

        /// @todo check support for IF EXISTS
        $statements = [
            "DROP USER $dbName CASCADE;",
        ];

        return new Command($statements);
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
     * @param string $userName
     * @param bool $ifExists
     * @return Command
     * @bug $ifExists = true not supported
     * @todo prevent sql injection!
     * @todo we should somehow warn users that this destroys the schema too
     */
    public function getDropUserSqlAction($userName, $ifExists = false)
    {
        /// @todo check support for IF EXISTS

        return new Command([
            "DROP USER $userName CASCADE;",
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
    /*protected function getCollationName($charset)
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
    }*/
}
