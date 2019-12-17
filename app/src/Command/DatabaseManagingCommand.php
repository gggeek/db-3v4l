<?php

namespace Db3v4l\Command;

use Db3v4l\Core\DatabaseSchemaManager;
use Symfony\Component\Console\Output\OutputInterface;

abstract class DatabaseManagingCommand extends SQLExecutingCommand
{
    /**
     * @param string[][] $instanceList
     * @param array[] $dbSpecList key: db name (as used to identify configured databases), value: array('user': mandatory, 'dbname': optional, if unspecified assumed same as user)
     * @return array 'succeeded': int, 'failed': int, 'results': same format as dbManager::getConnectionSpecification
     */
    protected function createDatabases($instanceList, $dbSpecList)
    {
        // Sadly, psql does not allow to create a db and a user using a multiple-sql-commands string,
        // and we have to resort to using temp files
        /// @todo can we make this safer? Ideally the new user name and pwd should neither hit disk nor the process list...
        $results = $this->executeSqlAction(
            $instanceList,
            'Creating new database & user',
            function ($schemaManager, $instanceName) use ($dbSpecList) {
                $dbConnectionSpec = $dbSpecList[$instanceName];
                /** @var DatabaseSchemaManager $schemaManager */
                return $schemaManager->getCreateDatabaseSqlAction(
                    $dbConnectionSpec['user'],
                    $dbConnectionSpec['password'],
                    (isset($dbConnectionSpec['dbname']) && $dbConnectionSpec['dbname'] != '') ? $dbConnectionSpec['dbname'] : null,
                    (isset($dbConnectionSpec['charset']) && $dbConnectionSpec['charset'] != '') ? $dbConnectionSpec['charset'] : null
                );
            }
        );

        $finalData = [];
        foreach($results['data'] as $instanceName => $data) {
            $dbConnectionSpec = $dbSpecList[$instanceName];
            $finalData[$instanceName] = array_merge(
                $instanceList[$instanceName],
                [
                    'user' => $dbConnectionSpec['user'],
                    'password' => $dbConnectionSpec['password'],
                    'dbname' => (isset($dbConnectionSpec['dbname']) && $dbConnectionSpec['dbname'] != '') ? $dbConnectionSpec['dbname'] : $dbConnectionSpec['user']
                ]
            );
        }

        $results['data'] = $finalData;
        return $results;
    }

    /**
     * @param string[][] $instanceList
     * @param array[] $dbSpecList key: db name (as used to identify configured databases), value: array('user': mandatory, 'dbname': optional, if unspecified assumed same as user)
     * @return array 'succeeded': int, 'failed': int, 'results': string[]
     */
    protected function dropDatabases($instanceList, $dbSpecList)
    {
        return $this->executeSqlAction(
            $instanceList,
            'Dropping of new database & user',
            function ($schemaManager, $instanceName) use ($dbSpecList) {
                $dbConnectionSpec = $dbSpecList[$instanceName];
                /** @var DatabaseSchemaManager $schemaManager */
                return $schemaManager->getDropDatabaseSqlAction(
                    $dbConnectionSpec['user'],
                    (isset($dbConnectionSpec['dbname']) && $dbConnectionSpec['dbname'] != '') ? $dbConnectionSpec['dbname'] : null
                );
            }
        );
    }
}
