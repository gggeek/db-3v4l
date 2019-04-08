<?php

namespace Db3v4l\Service\SqlExecutor\Forked;

use Symfony\Component\Process\Process;

use Db3v4l\API\Interfaces\ForkedSqlExecutor;

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
                    '--execute=' . $sql,
                    // $dbname
                ];
                $env = [
                    'MYSQL_PWD' => $this->databaseConfiguration['password'],
                ];
                break;
            case 'pgsql':
                $command = 'psql';
                $options = [
                    '--host=' . $this->databaseConfiguration['host'],
                    '--port=' . $this->databaseConfiguration['port'] ?? '5432',
                    '--username=' . $this->databaseConfiguration['user'],
                    '--command=' . $sql,
                    //'--dbname=' . $dbname
                ];
                $env = [
                    'PGPASSWORD' => $this->databaseConfiguration['password'],
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
