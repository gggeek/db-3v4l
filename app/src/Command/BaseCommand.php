<?php

namespace Db3v4l\Command;

use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Command\Command;

abstract class BaseCommand extends Command
{
    /** @var OutputInterface $output */
    protected $output;
    protected $verbosity = OutputInterface::VERBOSITY_NORMAL;

    /**
     * Small tricks to allow us to lower verbosity between NORMAL and QUIET and have a decent writeln API, even with old SF versions
     * @param $message
     * @param int $verbosity
     */
    protected function writeln($message, $verbosity = OutputInterface::VERBOSITY_NORMAL)
    {
        if ($this->verbosity >= $verbosity) {
            $this->output->writeln($message);
        }
    }

    protected function setOutput(OutputInterface $output)
    {
        $this->output = $output;
    }

    protected function setVerbosity($verbosity)
    {
        $this->verbosity = $verbosity;
    }
}
