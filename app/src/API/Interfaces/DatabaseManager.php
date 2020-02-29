<?php

namespace Db3v4l\API\Interfaces;

use Db3v4l\API\Interfaces\SqlAction\CommandAction;

/**
 * Manages databases (and users/policies) of an instance
 */
interface DatabaseManager
{
    /**
     * Returns the sql 'action' used to list all available databases
     * @return CommandAction
     * @todo for each database, retrieve the charset/collation
     */
    public function getListDatabasesSqlAction();

    /**
     * Returns the sql 'action' used to create a new db and accompanying user
     * @param string $dbName Max 63 chars for Postgres
     * @param string $userName Max 16 chars for MySQL 5.5
     * @param string $password
     * @param string $charset charset/collation name
     * @return CommandAction
     */
    public function getCreateDatabaseSqlAction($dbName, $userName, $password, $charset = null);

    /**
     * Returns the sql 'action' used to drop a db and associated user account
     * @param string $dbName
     * @param string $userName
     * @return CommandAction
     * @param bool $ifExists
     * @bug currently some DBs report failures for non-existing user, even when $ifExists = true
     */
    public function getDropDatabaseSqlAction($dbName, $userName, $ifExists = false);

    /**
     * Returns the sql 'action' used to list all existing db users
     * @return CommandAction
     */
    public function getListUsersSqlAction();

    /**
     * @param string $userName
     * @param bool $ifExists
     * @return CommandAction
     * @bug currently some DBs report failures for non-existing user, even when $ifExists = true
     */
    public function getDropUserSqlAction($userName, $ifExists = false);

    /**
     * Returns the sql 'action' used to retrieve the db instance version info
     * @return CommandAction
     */
    public function getRetrieveVersionInfoSqlAction();

    /**
     * Returns the sql 'action' used to list all available collations
     * @return CommandAction
     * @throws OutOfBoundsException for unsupported database types
     */
    public function getListCollationsSqlAction();
}
