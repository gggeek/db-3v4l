<?php

namespace Db3v4l\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Output\ConsoleOutputInterface;

abstract class BaseCommand extends Command
{
    /** @var OutputInterface $output */
    protected $output;
    /** @var OutputInterface $errorOutput */
    protected $errorOutput;
    protected $verbosity = OutputInterface::VERBOSITY_NORMAL;

    /**
     * Small tricks to allow us to lower verbosity between NORMAL and QUIET and have a decent writeln API, even with old SF versions
     * @param $message
     * @param int $verbosity
     * @param int $type
     */
    protected function writeln($message, $verbosity = OutputInterface::VERBOSITY_NORMAL, $type = OutputInterface::OUTPUT_NORMAL)
    {
        if ($this->verbosity >= $verbosity) {
            $this->output->writeln($message, $type);
        }
    }
    /**
     * @param string|array $message The message as an array of lines or a single string
     * @param int $verbosity
     * @param int $type
     */
    protected function writeErrorln($message, $verbosity = OutputInterface::VERBOSITY_QUIET, $type = OutputInterface::OUTPUT_NORMAL)
    {
        if ($this->verbosity >= $verbosity) {

            // When verbosity is set to quiet, SF swallows the error message in the writeln call
            // (unlike for other verbosity levels, which are left for us to handle...)
            // We resort to a hackish workaround to _always_ print errors to stderr, even in quiet mode.
            // If the end user does not want any error echoed, she can just 2>/dev/null
            if ($this->errorOutput->getVerbosity() == OutputInterface::VERBOSITY_QUIET) {
                $this->errorOutput->setVerbosity(OutputInterface::VERBOSITY_NORMAL);
                $this->errorOutput->writeln($message, $type);
                $this->errorOutput->setVerbosity(OutputInterface::VERBOSITY_QUIET);
            }
            else
            {
                $this->errorOutput->writeln($message, $type);
            }
        }
    }

    protected function setOutput(OutputInterface $output)
    {
        $this->output = $output;
        $this->errorOutput = $output instanceof ConsoleOutputInterface ? $output->getErrorOutput() : $output;
    }

    protected function setVerbosity($verbosity)
    {
        $this->verbosity = $verbosity;
    }
}
