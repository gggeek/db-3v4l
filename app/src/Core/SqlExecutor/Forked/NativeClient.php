<?php

namespace Db3v4l\Core\SqlExecutor\Forked;

use Db3v4l\API\Interfaces\SqlExecutor\Forked\CommandExecutor;
use Db3v4l\API\Interfaces\SqlExecutor\Forked\FileExecutor;
use Db3v4l\Util\Process;

/**
 * @todo allow to inject path of db clients via setter/constructor
 */
class NativeClient extends ForkedExecutor implements CommandExecutor, FileExecutor
{
    /**
     * @param string $sql
     * @return Process
     */
    public function getExecuteStatementProcess($sql)
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
     */
    public function getProcess($sqlOrFilename, $isFile = false)
    {
        $clientType = $this->getDbClientType($this->databaseConfiguration);

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
            case 'psql':
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
            case 'sqlcmd':
                $command = 'sqlcmd';
                $options = [
                    '-S' . $this->databaseConfiguration['host'] . ($this->databaseConfiguration['port'] != '' ?  ',' . $this->databaseConfiguration['port'] : ''),
                    '-U' . $this->databaseConfiguration['user'],
                    '-P' . $this->databaseConfiguration['password'],
                    '-r1',
                    '-b',
                ];
                if (isset($this->databaseConfiguration['dbname'])) {
                    $options[] = '-d' . $this->databaseConfiguration['dbname'];
                }
                if ($isFile) {
                    $options[] = '-i' . $sqlOrFilename;
                } else {
                    $options[] = '-Q' . $sqlOrFilename;
                }
                break;
            case 'sqlite':
                $command = 'sqlite3';
                // 'path' is the full path to the 'master' db (for Doctrine compatibility).
                //  non-master dbs are supposed to reside in the same directory
                if (isset($this->databaseConfiguration['dbname'])) {
                    $options[] = dirname($this->databaseConfiguration['path']) . '/' . $this->databaseConfiguration['dbname'] . '.sqlite';
                } else {
                    $options[] = $this->databaseConfiguration['path'];
                }

                if (!$isFile) {
                    $options[] = $sqlOrFilename;
                }
                break;
            // case 'sqlplus':
            //    $command = 'sqlplus';
            //    // pass on _all_ env vars, including PATH
            //    $env = null;
            //    break;
            default:
                throw new \OutOfBoundsException("Unsupported db client '$clientType'");
        }

        $commandLine = $this->buildCommandLine($command, $options);

        /// @todo investigate: for psql is this better done via --file ?
        if ($isFile && $clientType != 'sqlsrv') {
            $commandLine .= ' < ' . escapeshellarg($sqlOrFilename);
        }

        return new Process($commandLine, null, $env);
    }

    /**
     * @see https://www.doctrine-project.org/projects/doctrine-dbal/en/2.10/reference/configuration.html for supported aliases
     * @param array $connectionConfiguration
     * @return string
     */
    protected function getDbClientType(array $connectionConfiguration)
    {
        $vendor = $connectionConfiguration['vendor'];
        return str_replace(
            array('mariadb', 'mssql', 'oracle', 'postgresql'),
            array('mysql', 'sqlcmd', 'sqlplus', 'psql'),
            $vendor
        );
    }

    protected function getEnv()
    {
        return array();
    }
}
