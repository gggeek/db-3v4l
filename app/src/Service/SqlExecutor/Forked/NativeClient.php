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
        $clientType = $this->getDbClientTypeFromDriver($this->databaseConfiguration['driver']);

        switch ($clientType) {
            case 'mysql':
                $command = 'mysql';
                $options = [
                    '--host=' . $this->databaseConfiguration['host'],
                    '--port=' . $this->databaseConfiguration['port'] ?? '3306',
                    '--user=' . $this->databaseConfiguration['user'],
                    '-p' . $this->databaseConfiguration['password'],
                    '--binary-mode', // 'It also disables all mysql commands except charset and delimiter in non-interactive mode (for input piped to mysql or loaded using the source command)'
                ];
                if (isset($this->databaseConfiguration['dbname'])) {
                    $options[] = $this->databaseConfiguration['dbname'];
                }
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
                $connectString = "postgresql://".$this->databaseConfiguration['user'].":".$this->databaseConfiguration['password'].
                    "@{$this->databaseConfiguration['host']}:".($this->databaseConfiguration['port'] ?? '5432').'/';
                if (isset($this->databaseConfiguration['dbname'])) {
                    $connectString .= $this->databaseConfiguration['dbname'];
                }
                $options = [
                    $connectString
                ];
                // NB: this triggers a different behaviour that piping multiple commands to stdin, namely
                // it wraps all of the commands in a transaction and allows either sql commands or a single meta-command
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
    protected function getDbClientTypeFromDriver($driver)
    {
        return str_replace('pdo_', '', $driver);
    }
}
