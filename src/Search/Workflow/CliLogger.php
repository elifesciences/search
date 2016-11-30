<?php

namespace eLife\Search\Workflow;

use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

final class CliLogger implements LoggerInterface
{
    private $input;
    private $output;

    public function __construct(InputInterface $input, OutputInterface $output)
    {
        $this->input = $input;
        $this->output = $output;
    }

    public function emergency($message, array $context = array())
    {
        $this->output->writeln('<error>EMERGENCY: '.$message.'</error>');
    }

    public function alert($message, array $context = array())
    {
        $this->output->writeln('<error>ALERT: '.$message.'</error>');
        $this->dumpExceptionIfPresent($context);
    }

    public function critical($message, array $context = array())
    {
        $this->output->writeln('<error>CRITICAL: '.$message.'</error>');
    }

    public function error($message, array $context = array())
    {
        $this->output->writeln('<error>'.$message.'</error>');
    }

    public function warning($message, array $context = array())
    {
        $this->output->writeln('<comment>'.$message.'</comment>');
    }

    public function notice($message, array $context = array())
    {
        $this->output->writeln('<comment>NOTICE: '.$message.'</comment>');
    }

    public function info($message, array $context = array())
    {
        $this->output->writeln('<info>'.$message.'</info>');
    }

    public function debug($message, array $context = array())
    {
        $this->output->writeln($message);
    }

    public function log($level, $message, array $context = array())
    {
        $this->output->writeln($message);
    }

    private function dumpExceptionIfPresent(array $context)
    {
        if (array_key_exists('exception', $context)) {
            $e = $context['exception'];
            $this->output->writeln($e->getMessage());
            $this->output->writeln($e->getFile().':'.$e->getLine());
            $this->output->writeln($e->getTraceAsString());
        }
    }
}
