<?php

namespace Db3v4l\Service\SqlExecutor\Forked;

use Db3v4l\API\Interfaces\ForkedCommandExecutor;
use Db3v4l\API\Interfaces\ForkedFileExecutor;
use Db3v4l\Util\Process;

class NativeClient extends ForkedExecutor implements ForkedCommandExecutor, ForkedFileExecutor
{
    /**
     * @param string $sql
     * @return Process
     */
    public function getExecuteCommandProcess($sql)
    {
        return $this->getProcess($sql);
    }

    /**
     * @param string $filename
     * @return Process
     */
    public function getExecuteFileProcess($filename)
    {
        return $this->getProcess($filename, true);
    }

    /**
     * @param string $sqlOrFilename
     * @param bool $isFile
     * @return Process
     * @todo allow to inject location of db clients via setter/constructor
     */
    public function getProcess($sqlOrFilename, $isFile = false)
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
                    // $dbname
                ];
                if (!$isFile) {
                    $options[] = '--execute=' . $sqlOrFilename;
                }
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
                    //'--dbname=' . $dbname
                ];
                if (!$isFile) {
                    $options[] = '--command=' . $sqlOrFilename;
                }
                $env = [
                    // problematic when wrapping the process in a call to `time`...
                    //'PGPASSWORD' => $this->databaseConfiguration['password'],
                ];
                break;
            default:
                throw new \OutOfBoundsException("Unsupported db client '$clientType'");
        }

        $commandLine = $this->buildCommandLine($command, $options);

        /// @todo for psql this is probably better done via --file
        if ($isFile) {
            $commandLine .= ' < ' . escapeshellarg($sqlOrFilename);
        }

        return new Process($commandLine, null, $env);
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
