<?php

namespace eLife\Search;

use eLife\Search\Workflow\CliLogger;
use LogicException;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;

class Console
{
    /**
     * These commands map to [name]Command so when the command "hello" is configured
     * it will call helloCommand() on this class with InputInterface and OutputInterface
     * as parameters.
     *
     * This will hopefully cover most things.
     */
    public static $quick_commands = [
        'hello' => ['description' => 'This is a quick hello world command'],
        'echo' => ['description' => 'Example of asking a question'],
    ];

    public function __construct(Application $console, Kernel $app)
    {
        $this->console = $console;
        $this->app = $app;

        $this->console->getDefinition()->addOption(new InputOption('--env', '-e', InputOption::VALUE_REQUIRED, 'The Environment name.', 'dev'));
    }

    public function echoCommand(InputInterface $input, OutputInterface $output, LoggerInterface $logger)
    {
        $question = new Question('<question>Are we there yet?</question> ');
        $helper = new QuestionHelper();
        while (true) {
            $name = $helper->ask($input, $output, $question);
            if ($name === 'yes') {
                break;
            }
            $logger->error($name);
        }
    }

    public function helloCommand(InputInterface $input, OutputInterface $output, LoggerInterface $logger)
    {
        $logger->info('Hello from the outside (of the global scope)');
        $logger->debug('This is working');
    }

    public function run()
    {
        foreach (self::$quick_commands as $name => $cmd) {
            if (!method_exists($this, $name.'Command')) {
                throw new LogicException('Your command does not exist: '.$name.'Command');
            }
            // Hello
            $this->console
                ->register($name)
                ->setDescription($cmd['description'] ?? $name.' command')
                ->setCode(\Closure::bind(function (InputInterface $input, OutputInterface $output) use ($name) {
                    $logger = new CliLogger($input, $output);
                    $this->{$name.'Command'}($input, $output, $logger);
                }, $this));
        }
        $this->console->run();
    }
}
