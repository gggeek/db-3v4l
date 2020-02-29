<?php

namespace Db3v4l\Core\DatabaseManager;

use Db3v4l\API\Interfaces\DatabaseManager;
use Db3v4l\API\Interfaces\SqlExecutor\Executor;
use Db3v4l\Core\SqlAction\Command;

class SQLite extends BaseManager implements DatabaseManager
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
        //$collation = $this->getCollationName($charset);

        /// @todo this does not support creation of the new db with a different character encoding...
        ///       see https://stackoverflow.com/questions/21348459/set-pragma-encoding-utf-16-for-main-database-in-sqlite
        $filename = dirname($this->databaseConfiguration['path']) . '/' . $dbName . '.sqlite';
        return new Command(
            "ATTACH '$filename' AS \"$dbName\";"
        );
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

        return new Command(
            null,
            function() {
                /// @todo should we return something ?
            }
        );
    }

    /**
     * Returns the sql 'action' used to list all available collations
     * @return Command
     */
    public function getListCollationsSqlAction()
    {
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
    }

    /**
     * Returns the sql 'action' used to list all available databases
     * @return Command
     * @todo for each database, retrieve the charset/collation
     */
    public function getListDatabasesSqlAction()
    {
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
    }

    /**
     * Returns the sql 'action' used to list all existing db users
     * @return Command
     */
    public function getListUsersSqlAction()
    {
        return new Command(
            null,
            function () {
                // since sqlite does not support users, null seems more appropriate than an empty array...
                return null;
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
            "select sqlite_version();",
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
