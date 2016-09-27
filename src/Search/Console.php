<?php

namespace eLife\Search;

use LogicException;
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

    public function echoCommand(InputInterface $input, OutputInterface $output)
    {
        $question = new Question('Are we there yet? ');
        $helper = new QuestionHelper();
        while (true) {
            $name = $helper->ask($input, $output, $question);
            if ($name === 'yes') {
                break;
            }
            $output->writeln($name);
        }
    }

    public function helloCommand(InputInterface $input, OutputInterface $output)
    {
        $output->writeln('Hello from the outside (of the global scope)');
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
                ->setCode([$this, $name.'Command']);
        }
        $this->console->run();
    }
}
