<?php

namespace Db3v4l\Command;

use Db3v4l\Core\DatabaseSchemaManager;

abstract class DatabaseManagingCommand extends SQLExecutingCommand
{
    /**
     * @param string[][] $instanceList
     * @param array[] $dbSpecList key: db name (as used to identify configured databases), value: array('user': mandatory, 'dbname': mandatory, 'password': mandatory)
     * @return array 'succeeded': int, 'failed': int, 'results': same format as dbConfigurationManager::getInstanceConfiguration
     * @throws \Exception
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
                    $dbConnectionSpec['dbname'],
                    isset($dbConnectionSpec['user']) ? $dbConnectionSpec['user'] : null,
                    isset($dbConnectionSpec['password']) ? $dbConnectionSpec['password'] : null,
                    (isset($dbConnectionSpec['charset']) && $dbConnectionSpec['charset'] != '') ? $dbConnectionSpec['charset'] : null
                );
            }
        );

        $finalData = [];
        foreach($results['data'] as $instanceName => $data) {
            $dbConnectionSpec = $dbSpecList[$instanceName];
            $finalData[$instanceName] = $instanceList[$instanceName];
            $finalData[$instanceName]['dbname'] = $dbConnectionSpec['dbname'];
            if (isset($dbConnectionSpec['user'])) {
                $finalData[$instanceName]['user'] = $dbConnectionSpec['user'];
            }
            if (isset($dbConnectionSpec['password'])) {
                $finalData[$instanceName]['password'] = $dbConnectionSpec['password'];
            }
            if (isset($dbConnectionSpec['charset'])) {
                $finalData[$instanceName]['charset'] = $dbConnectionSpec['charset'];
            }
        }

        $results['data'] = $finalData;
        return $results;
    }

    /**
     * @param string[][] $instanceList
     * @param array[] $dbSpecList key: db name (as used to identify configured databases), value: array('user': mandatory, 'dbname': mandatory, if unspecified assumed same as user)
     * @param bool $ifExists
     * @return array 'succeeded': int, 'failed': int, 'results': string[]
     * @throws \Exception
     */
    protected function dropDatabases($instanceList, $dbSpecList, $ifExists = false)
    {
        return $this->executeSqlAction(
            $instanceList,
            'Dropping of database & user',
            function ($schemaManager, $instanceName) use ($dbSpecList, $ifExists) {
                $dbConnectionSpec = $dbSpecList[$instanceName];
                /** @var DatabaseSchemaManager $schemaManager */
                return $schemaManager->getDropDatabaseSqlAction(
                    $dbConnectionSpec['dbname'],
                    isset($dbConnectionSpec['user']) ? $dbConnectionSpec['user'] : null,
                    $ifExists
                );
            }
        );
    }
}
