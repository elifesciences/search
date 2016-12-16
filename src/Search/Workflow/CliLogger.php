<?php

namespace eLife\Search\Workflow;

use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

final class CliLogger implements LoggerInterface
{
    private $input;
    private $output;
    private $logger;

    public function __construct(InputInterface $input, OutputInterface $output, LoggerInterface $logger = null)
    {
        $this->input = $input;
        $this->output = $output;
        $this->logger = $logger ? $logger : new NullLogger();
    }

    public function emergency($message, array $context = array())
    {
        $this->output->writeln('<error>EMERGENCY: '.$message.'</error>');
        $this->logger->emergency($message, $context);
    }

    public function alert($message, array $context = array())
    {
        $this->output->writeln('<error>ALERT: '.$message.'</error>');
        $this->dumpExceptionIfPresent($context);
        $this->logger->alert($message, $context);
    }

    public function critical($message, array $context = array())
    {
        $this->output->writeln('<error>CRITICAL: '.$message.'</error>');
        $this->logger->critical($message, $context);
    }

    public function error($message, array $context = array())
    {
        $this->output->writeln('<error>'.$message.'</error>');
        $this->logger->error($message, $context);
    }

    public function warning($message, array $context = array())
    {
        $this->output->writeln('<comment>'.$message.'</comment>');
        $this->logger->warning($message, $context);
    }

    public function notice($message, array $context = array())
    {
        $this->output->writeln('<comment>NOTICE: '.$message.'</comment>');
        $this->logger->notice($message, $context);
    }

    public function info($message, array $context = array())
    {
        $this->output->writeln('<info>'.$message.'</info>');
        $this->logger->info($message, $context);
    }

    public function debug($message, array $context = array())
    {
        $this->output->writeln($message);
        $this->logger->debug($message, $context);
    }

    public function log($level, $message, array $context = array())
    {
        $this->output->writeln($message);
        $this->logger->log($level, $message, $context);
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
