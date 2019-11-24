<?php

namespace Db3v4l\Core\SqlExecutor\Forked;

use Db3v4l\API\Interfaces\ForkedCommandExecutor;
use Db3v4l\API\Interfaces\ForkedFileExecutor;
use Db3v4l\Util\Process;

/**
 * @todo allow setting a PATH for each tool
 */
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

        // pass on _all_ env vars, including PATH. Not doing so is deprecated...
        $env = null;

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
               // $env = [
                    // problematic when wrapping the process in a call to `time`...
                    //'MYSQL_PWD' => $this->databaseConfiguration['password'],
                //];
                break;
            // case 'oracle':
            //    $command = 'sqlplus';
            //    // pass on _all_ env vars, including PATH
            //    $env = null;
            //    break;
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
                //$env = [
                    // problematic when wrapping the process in a call to `time`...
                    //'PGPASSWORD' => $this->databaseConfiguration['password'],
                //];
                break;
            //case 'sqlite':
            //    break;
            case 'sqlsrv':
                $command = 'sqlcmd';
                $options = [
                    '-S' . $this->databaseConfiguration['host'] . ($this->databaseConfiguration['port'] != '' ?  ',' . $this->databaseConfiguration['port'] : ''),
                    '-U' . $this->databaseConfiguration['user'],
                    '-P' . $this->databaseConfiguration['password'],
                    //'-u', // unicode output, but only for files...
                ];
                if (isset($this->databaseConfiguration['dbname'])) {
                    $options[] = '-d' . $this->databaseConfiguration['dbname'];
                }
                if ($isFile) {
                    $options[] = '-i' . $sqlOrFilename;
                } else {
                    $options[] = '-Q' . $sqlOrFilename;
                }
                // pass on _all_ env vars, including PATH
                $env = null;
                break;
            default:
                throw new \OutOfBoundsException("Unsupported db client '$clientType'");
        }

        $commandLine = $this->buildCommandLine($command, $options);

        /// @todo for psql this is probably better done via --file
        if ($isFile && $clientType != 'sqlsrv') {
            $commandLine .= ' < ' . escapeshellarg($sqlOrFilename);
        }

        return new Process($commandLine, null, $env);
    }

    /**
     * @see https://www.doctrine-project.org/projects/doctrine-dbal/en/2.9/reference/configuration.html for supported aliases
     * @param string $driver
     * @return string
     */
    protected function getDbClientTypeFromDriver($driver)
    {
        return str_replace(
            array('pdo_', 'mssql', 'mysql2', 'postgres', 'postgresql', 'sqlite3'),
            array('', 'sqlsrv', 'mysql', 'pgsql', 'pgsql', 'sqlite'),
            $driver
        );
    }

    protected function getEnv()
    {
        return array();
    }
}
