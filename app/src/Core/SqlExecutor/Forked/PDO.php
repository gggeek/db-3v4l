<?php

namespace Db3v4l\Core\SqlExecutor\Forked;

use Db3v4l\API\Interfaces\SqlExecutor\Forked\CommandExecutor;
use Db3v4l\Service\DatabaseConfigurationManager;
use Db3v4l\Util\Process;

/**
 * Executes sql queries via a separate symfony console command.
 * Relies on the 'sql:execute' dbconsole command
 */
class PDO extends ForkedExecutor implements CommandExecutor
{
    const EXECUTION_STRATEGY = 'PDO';

    protected $instanceName;
    /** @var DatabaseConfigurationManager $dbConfigurationManager */
    protected $dbConfigurationManager;

    public function setInstanceName($instanceName)
    {
        $this->instanceName = $instanceName;
    }

    public function setDbConfigurationManager(DatabaseConfigurationManager $dbConfigurationManager)
    {
        $this->dbConfigurationManager = $dbConfigurationManager;
    }

    public function getExecuteStatementProcess($sql)
    {
        return $this->getProcess($sql, self::EXECUTE_COMMAND);
    }

    /**
     * @param string $filename
     * @return Process
     */
    public function getExecuteFileProcess($filename)
    {
        return $this->getProcess($filename, self::EXECUTE_FILE);
    }

    protected function getProcess($sqlOrFilename, $action = self::EXECUTE_COMMAND)
    {
        // pass on _all_ env vars, including PATH. Not doing so is deprecated...
        $env = null;

        $command = 'php';
        $options = [
            'bin/dbconsole',
            'sql:execute',
            '--only-instances=' . $this->instanceName,
            '--execute-in-process',
            '--execution-strategy=' . static::EXECUTION_STRATEGY,
            '--output-type=json',
        ];

        switch ($action) {
            case self::EXECUTE_COMMAND:
                $options[] = '--sql=' . $sqlOrFilename;
                break;
            case self::EXECUTE_FILE:
                $options[] = '--file=' . $sqlOrFilename;
                break;
            default:
                throw new \OutOfBoundsException('Unsupported action: $action');
        }

        $instanceConfiguration = $this->dbConfigurationManager->getInstanceConfiguration($this->instanceName);

        if ($instanceConfiguration != $this->databaseConfiguration) {
            if (isset($this->databaseConfiguration['dbname'])) {
                $options[] = '--database=' . $this->databaseConfiguration['dbname'];
            }
            if (isset($this->databaseConfiguration['user'])) {
                $options[] = '--user=' . $this->databaseConfiguration['user'];
            }
            if (isset($this->databaseConfiguration['password'])) {
                $options[] = '--password=' . $this->databaseConfiguration['password'];
            }
        }

        $commandLine = $this->buildCommandLine($command, $options);

        return new Process($commandLine, null, $env);
    }

    public function resultSetToArray($data)
    {
        $data = json_encode($data);
        return $data;
    }
}
