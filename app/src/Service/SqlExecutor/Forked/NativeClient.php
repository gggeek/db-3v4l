<?php

namespace Db3v4l\Service\SqlExecutor\Forked;

use Db3v4l\API\Interfaces\ForkedSqlExecutor;
use Db3v4l\Util\Process;

class NativeClient extends ForkedExecutor implements ForkedSqlExecutor
{
    /**
     * @param string $sql
     * @return Process
     * @todo allow to inject location of db clients via setter/constructor
     */
    public function getProcess($sql)
    {
        $clientType = $this->getDbClientFromDriver($this->databaseConfiguration['driver']);

        switch ($clientType) {
            case 'mysql':
                $command = 'mysql';
                $options = [
                    '--host=' . $this->databaseConfiguration['host'],
                    '--port=' . $this->databaseConfiguration['port'] ?? '3306',
                    '--user=' . $this->databaseConfiguration['user'],
                    '-p' . $this->databaseConfiguration['password'],
                    '--binary-mode', // 'It also disables all mysql commands except charset and delimiter in non-interactive mode (for input piped to mysql or loaded using the source command)'
                    '--execute=' . $sql,
                    // $dbname
                ];
                $env = [
                    // problematic when wrapping the process in a call to `time`...
                    //'MYSQL_PWD' => $this->databaseConfiguration['password'],
                ];
                break;
            case 'pgsql':
                $command = 'psql';
                $options = [
                    "postgresql://".$this->databaseConfiguration['user'].":".$this->databaseConfiguration['password'].
                    "@{$this->databaseConfiguration['host']}:".($this->databaseConfiguration['port'] ?? '5432').'/',
                    //'--host=' . $this->databaseConfiguration['host'],
                    //'--port=' . $this->databaseConfiguration['port'] ?? '5432',
                    //'--username=' . $this->databaseConfiguration['user'],
                    '--command=' . $sql,
                    //'--dbname=' . $dbname
                ];
                $env = [
                    // problematic when wrapping the process in a call to `time`...
                    //'PGPASSWORD' => $this->databaseConfiguration['password'],
                ];
                break;
            default:
                throw new \OutOfBoundsException("Unsupported db client '$clientType'");
        }

        return new Process($this->buildCommandLine($command, $options), null, $env);
    }

    /**
     * @param string $driver
     * @return string
     */
    protected function getDbClientFromDriver($driver)
    {
        return str_replace('pdo_', '', $driver);
    }
}
