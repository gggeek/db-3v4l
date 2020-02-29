<?php

namespace Db3v4l\Core\SqlExecutor\Forked;

use Db3v4l\API\Interfaces\SqlExecutor\Forked\CommandExecutor;
use Db3v4l\API\Interfaces\SqlExecutor\Forked\FileExecutor;
use Db3v4l\API\Interfaces\SqlExecutor\Forked\ShellExecutor;
use Db3v4l\Util\Process;

/**
 * @todo allow to inject path of db clients via setter/constructor
 */
class NativeClient extends ForkedExecutor implements CommandExecutor, FileExecutor, ShellExecutor
{
    const EXECUTION_STRATEGY = 'NativeClient';
    const EXECUTE_SHELL = 2;

    /**
     * @param string $sql
     * @return Process
     */
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

    public function getExecuteShellProcess()
    {
        return $this->getProcess(null, self::EXECUTE_SHELL);
    }

    /**
     * @param string $sqlOrFilename
     * @param int $action
     * @return Process
     */
    protected function getProcess($sqlOrFilename, $action = self::EXECUTE_COMMAND)
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
                    '-t',
                ];
                if (isset($this->databaseConfiguration['dbname'])) {
                    $options[] = $this->databaseConfiguration['dbname'];
                }
                if ($action == self::EXECUTE_COMMAND) {
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
                    $connectString,
                    '-Pfooter=off'
                ];
                // NB: this triggers a different behaviour that piping multiple commands to stdin, namely
                // it wraps all of the commands in a transaction and allows either sql commands or a single meta-command
                if ($action == self::EXECUTE_COMMAND) {
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
                if ($action == self::EXECUTE_FILE) {
                    $options[] = '-i' . $sqlOrFilename;
                } elseif ($action == self::EXECUTE_COMMAND) {
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

                if ($action == self::EXECUTE_COMMAND) {
                    $options[] = $sqlOrFilename;
                }
                break;

            case 'sqlplus':
                /// @todo disable execution of dangerous (or all) SQLPLUS commands.
                ///       See the list at https://docs.oracle.com/en/database/oracle/oracle-database/18/sqpug/SQL-Plus-command-reference.html#GUID-177F24B7-D154-4F8B-A05B-7568079800C6
                $command = 'sqlplus';
                $options = [
                    '-L', // 'attempts to log in just once, instead of reprompting on error'
                    '-NOLOGINTIME',
                    '-S',
                    $this->databaseConfiguration['user'] . '/' . $this->databaseConfiguration['password'] .
                    '@//' . $this->databaseConfiguration['host'] .
                        ($this->databaseConfiguration['port'] != '' ?  ':' . $this->databaseConfiguration['port'] : '') .
                        ($this->databaseConfiguration['servicename'] != '' ?  '/' . $this->databaseConfiguration['servicename'] : ''),
                ];
                // nb: for oracle, we use schemas (instead of pdbs) to map 'databases'...
                //if (isset($this->databaseConfiguration['dbname'])) {
                //}
                if ($action == self::EXECUTE_FILE) {
                    /// @todo add to the existing file the pagesize and feedback settings
                    $options[] = '@' . $sqlOrFilename;
                } else {
                    $sqlOrFilename = "set pagesize 50000;\nset feedback off;\n" . $sqlOrFilename;
                }

                break;

                default:
                throw new \OutOfBoundsException("Unsupported db client '$clientType'");
        }

        $commandLine = $this->buildCommandLine($command, $options);

        /// @todo investigate: for psql is this better done via --file ?
        if ($action == self::EXECUTE_FILE && $clientType != 'sqlsrv' && $clientType != 'sqlplus') {
            $commandLine .= ' < ' . escapeshellarg($sqlOrFilename);
        }

        if ($action == self::EXECUTE_COMMAND && $clientType == 'sqlplus') {
            $commandLine .= " << 'SQLEOF'\n" . $sqlOrFilename . "\nSQLEOF";
        }

        $process = Process::fromShellCommandline($commandLine, null, $env);

        if ($action == self::EXECUTE_COMMAND && $clientType == 'sqlplus') {
            // The way Symfony Process deals with sighchildEnabled breaks EOF handling. We disable it
            $process->forceSigchildEnabledIndividually(false);
        }

        return $process;
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
            array('mariadb', 'mssql', 'oracle', 'percona', 'postgresql'),
            array('mysql', 'sqlcmd', 'sqlplus', 'mysql', 'psql'),
            $vendor
        );
    }

    /**
     * Transforms a resultSet string, formatted as per the default way of the db client, into an array
     * @todo tested on single-column SELECTs so far
     * @param $string
     * @return string[]
     */
    public function resultSetToArray($string)
    {
        switch ($this->databaseConfiguration['vendor']) {
            case 'mariadb':
            case 'mysql':
            case 'percona':
                // 'table format', triggered by using the -t option for the client
                // NB: both mariadb and mysql output no headers line when resultset has 0 rows
                $output = explode("\n", $string);
                array_shift($output); // '+--+'
                array_shift($output); // headers
                array_shift($output); // '+--+'
                array_pop($output); // '+--+'
                foreach($output as &$line) {
                    $line = trim($line, '|');
                    $line = trim($line);
                }
                return $output;
            case 'oracle':
                $output = explode("\n", $string);
                array_shift($output); // empty line
                array_shift($output); // headers
                array_shift($output); // '---'
                foreach($output as &$line) {
                    $line = trim($line);
                }
                return $output;
            case 'postgresql':
                $output = explode("\n", $string);
                array_shift($output); // headers
                array_shift($output); // '---'
                //array_pop($output); // '(N rows)'
                foreach($output as &$line) {
                    $line = trim($line);
                }
                return $output;
            case 'sqlite':
                $output = explode("\n", $string);
                return $output;
            case 'mssql':
                $output = explode("\n", $string);
                array_shift($output);
                array_shift($output); // '---'
                array_pop($output); // blank line
                array_pop($output); // '(N rows affected)'
                foreach($output as &$line) {
                    $line = trim($line);
                }
                return $output;
            default:
                throw new \OutOfBoundsException("Unsupported database type '{$this->databaseConfiguration['vendor']}'");
        }
    }
}
