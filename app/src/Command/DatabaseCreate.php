<?php

namespace Db3v4l\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class DatabaseCreate extends DatabaseManagingCommand
{
    protected static $defaultName = 'db3v4l:database:create';

    protected function configure()
    {
        $this
            ->setDescription('Creates a database & associated user in parallel on all configured database instances')
            ->addOption('database', 'd', InputOption::VALUE_REQUIRED, 'The name of the database to create')
            ->addOption('user', 'u', InputOption::VALUE_REQUIRED, 'The name of a user to create with r/w access to the new database. If omitted, no user will be created')
            ->addOption('password', 'p', InputOption::VALUE_REQUIRED, 'The password. If omitted, a random one will be generated and echoed as part of results')
            ->addOption('charset', 'c', InputOption::VALUE_REQUIRED, 'The collation/character-set to use for the database. If omitted, the default collation for the instance will be used')
            ->addCommonOptions()
        ;
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int
     * @throws \Exception
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $start = microtime(true);

        // as per https://www.php.net/manual/en/function.ignore-user-abort.php: for cli scripts, it is probably a good idea
        // to use ignore_user_abort
        ignore_user_abort(true);

        $this->setOutput($output);
        $this->setVerbosity($output->getVerbosity());

        $instanceList = $this->parseCommonOptions($input);

        $dbName = $input->getOption('database');
        $userName = $input->getOption('user');
        $password = $input->getOption('password');
        $charset = $input->getOption('charset');

        // BC api
        if ($dbName == null && $userName != null) {
            $dbName = $userName;
        }

        if ($dbName == null) {
            throw new \Exception("Please provide a database name");
        }

        if ($userName == null) {
            if ($password != null) {
                throw new \Exception("Option 'password' is only valid together with option 'user'");
            }
        } else {
            if ($password == null) {
                // 30 chars = max length for Oracle (at least up to 11g)
                /// @todo move to a constant
                $password = bin2hex(random_bytes(15));

                // Should we warn the user always? To avoid breaking non-text-format, we can send it to stderr...
                // Otoh we give the password in the structured output that we produce
                //$this->writeErrorln("<info>Assigned password to the user: $password</info>");
            }
        }

        if ($this->outputFormat === 'text') {
            $this->writeln('<info>Creating databases...</info>');
        }

        $newDbSpecs = [];
        foreach($instanceList as $instanceName => $instanceSpecs) {
            $newDbSpecs[$instanceName] = [
                'dbname' => $dbName
            ];
            if ($userName != null) {
                $newDbSpecs[$instanceName]['user'] = $userName;
                $newDbSpecs[$instanceName]['password'] = $password;
            }
            if ($charset != '') {
                $newDbSpecs[$instanceName]['charset'] = $charset;
            }
        }

        $results = $this->createDatabases($instanceList, $newDbSpecs);

        $time = microtime(true) - $start;

        $this->writeResults($results, $time);

        return (int)$results['failed'];
    }
}
