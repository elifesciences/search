<?php

namespace eLife\Search;

use Closure;
use eLife\Search\Annotation\Register;
use eLife\Search\Workflow\CliLogger;
use Exception;
use LogicException;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Process\Process;

final class Console
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
        'cache:clear' => ['description' => 'Clears cache'],
        'debug:params' => ['description' => 'Lists current parameters'],
        'spawn' => [
            'description' => 'WARNING: Experimental, may create child processes.',
            'args' => [
                ['name' => 'cmd', 'default' => 'list'],
                ['name' => 'amount', 'default' => 1],
                ['name' => 'memory', 'default' => 40],
            ],
        ],
    ];

    public function __construct(Application $console, Kernel $app)
    {
        $this->console = $console;
        $this->app = $app;
        $this->root = __DIR__.'/../..';

        // Some annotations
        Register::registerLoader();

        $this->console->getDefinition()->addOption(new InputOption('--env', '-e', InputOption::VALUE_REQUIRED, 'The Environment name.', 'dev'));

        // Add commands from the DI container. (for more complex commands.)
        $this->console->addCommands([
            $app->get('console.gearman.worker'),
        ]);
    }

    private function path($path = '')
    {
        return $this->root.$path;
    }

    public function spawnCommand(InputInterface $input, OutputInterface $output, LoggerInterface $logger)
    {
        $command = (string) $input->getArgument('cmd');
        $amount = (int) $input->getArgument('amount');
        // $memory = ((int)$input->getArgument('memory')) * 1024 * 1024; // Not working currently.
        $processes = [];
        for ($i = 0; $i < $amount; ++$i) {
            $processes[] = new Process('exec php '.$this->path('/bin/console').' '.$command.' --ansi');
        }
        $pids = [];
        $running = true;
        while (count($processes) > 0) {
            if ($running) {
                foreach ($processes as $i => $process) {
                    /** @var $process Process */
                    if (!$process->isStarted()) {
                        $process->start();
                        $pids[$i] = $process->getPid();
                        $logger->warning('Process starts, PID:'.$process->getPid());
                    }

                    $output->write($process->getIncrementalOutput());
                    $output->write($process->getIncrementalErrorOutput());

                    if (!$process->isRunning()) {
                        $process->restart();
                        $logger->error('Process stopped (Memory: '.round(memory_get_usage() / 1024 / 1024, 2).'Mb)');
                        $logger->warning('Starting new process');
                        $processes[] = new Process('exec php '.$this->path('/bin/console').' '.$command.' --ansi');
                        unset($processes[$i]);
                    }
                }
            }
        }
        sleep(1);
    }

    public function debugParamsCommand(InputInterface $input, OutputInterface $output, LoggerInterface $logger)
    {
        foreach ($this->app->get('config') as $key => $config) {
            if (is_array($config)) {
                $logger->warning($key);
                $logger->info(json_encode($config, JSON_PRETTY_PRINT));
                $logger->debug(' ');
            } elseif (is_bool($config)) {
                $logger->warning($key);
                $logger->info($config ? 'true' : 'false');
                $logger->debug(' ');
            } else {
                $logger->warning($key);
                $logger->info($config);
                $logger->debug(' ');
            }
        }
    }

    public function cacheClearCommand(InputInterface $input, OutputInterface $output, LoggerInterface $logger)
    {
        $logger->warning('Clearing cache...');
        try {
            exec('rm -rf '.$this->root.'/cache/*');
        } catch (Exception $e) {
            $logger->error($e);
        }
        $logger->info('Cache cleared successfully.');
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
            if (strpos($name, ':')) {
                $pieces = explode(':', $name);
                $first = array_shift($pieces);
                $pieces = array_map('ucfirst', $pieces);
                array_unshift($pieces, $first);
                $fn = implode('', $pieces);
            } else {
                $fn = $name;
            }
            if (!method_exists($this, $fn.'Command')) {
                throw new LogicException('Your command does not exist: '.$fn.'Command');
            }
            // Hello
            $command = $this->console
                ->register($name)
                ->setDescription($cmd['description'] ?? $name.' command')
                ->setCode(Closure::bind(function (InputInterface $input, OutputInterface $output) use ($fn, $name) {
                    $logger = new CliLogger($input, $output);
                    $this->{$fn.'Command'}($input, $output, $logger);
                }, $this));

            if (isset($cmd['args'])) {
                foreach ($cmd['args'] as $arg) {
                    $command->addArgument($arg['name'], $arg['mode'] ?? null, $arg['description'] ?? '', $arg['default'] ?? null);
                }
            }
        }
        $this->console->run();
    }
}
