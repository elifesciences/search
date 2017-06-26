<?php

namespace eLife\Search;

use Aws\Sqs\Exception\SqsException;
use Aws\Sqs\SqsClient;
use Closure;
use eLife\Bus\Queue\InternalSqsMessage;
use eLife\Bus\Queue\WatchableQueue;
use eLife\Search\Annotation\Register;
use eLife\Search\Api\Elasticsearch\ElasticsearchClient;
use Exception;
use LogicException;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;
use Symfony\Component\Console\Question\Question;

/**
 * @property LoggerInterface temp_logger
 * @property float|int previousProgress
 * @property ProgressBar progress
 */
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
        'cache:clear' => ['description' => 'Clears cache'],
        'queue:interactive' => ['description' => 'Manually enqueue item into SQS. (interactive)'],
        'queue:create' => ['description' => 'Creates queue [development-only]'],
        'queue:push' => [
            'description' => 'Manually enqueue item into SQS.',
            'args' => [
                ['name' => 'type'],
                ['name' => 'id'],
            ],
        ],
        'queue:clean' => [
            'description' => 'Manually clean the queue. Asynchronous, takes up to 60 seconds',
        ],
        'queue:count' => [
            'description' => 'Counts (approximately) how many messages are in the queue',
        ],
        'index:switch:read' => [
            'description' => 'Switches the index we are reading from in the API',
            'args' => [
                ['name' => 'index_name'],
            ],
        ],
        'index:switch:write' => [
            'description' => 'Switches the index we are writing new data to',
            'args' => [
                ['name' => 'index_name'],
            ],
        ],
    ];

    public function queueCreateCommand()
    {
        if ($this->config['debug'] !== true) {
            throw new LogicException('This method should not be called outside of development');
        }
        /* @var SqsClient $queue */
        $sqs = $this->app->get('aws.sqs');

        $sqs->createQueue([
            'Region' => $this->config['aws']['region'],
            'QueueName' => $this->config['aws']['queue_name'],
        ]);
    }

    public function queuePushCommand(InputInterface $input, OutputInterface $output)
    {
        $id = $input->getArgument('id');
        $type = $input->getArgument('type');
        // Enqueue.
        $this->enqueue($type, $id);
    }

    public function queueInteractiveCommand(InputInterface $input, OutputInterface $output)
    {
        $helper = new QuestionHelper();
        // Get the type.
        $choice = new ChoiceQuestion('<question>Which type would you like to import</question>', [
            'article',
            'blog-article',
            'interview',
            'labs-post',
            'podcast-episode',
            'collection',
        ], 0);
        $type = $helper->ask($input, $output, $choice);
        // Ge the Id.
        $choice = new Question('<question>Whats the ID of the item to import: </question>');
        $id = $helper->ask($input, $output, $choice);
        // Enqueue.
        $this->enqueue($type, $id);
    }

    public function queueCleanCommand(InputInterface $input, OutputInterface $output)
    {
        $queue = $this->app->get('aws.queue');
        $queue->clean();
    }

    public function queueCountCommand(InputInterface $input, OutputInterface $output)
    {
        $queue = $this->app->get('aws.queue');
        $output->writeln($queue->count());
    }

    private function enqueue($type, $id)
    {
        // Create queue item.
        $item = new InternalSqsMessage($type, $id);
        /** @var $queue WatchableQueue */
        $queue = $this->app->get('aws.queue');
        // Queue item.
        $queue->enqueue($item);
        $this->logger->info('Item added successfully.');
    }

    public function indexSwitchWriteCommand(InputInterface $input, OutputInterface $output)
    {
        $indexName = $input->getArgument('index_name');
        $metadata = $this->app->indexMetadata();
        $metadata->switchWrite($indexName)->toFile('index.json');
    }

    public function indexSwitchReadCommand(InputInterface $input, OutputInterface $output)
    {
        $indexName = $input->getArgument('index_name');
        $metadata = $this->app->indexMetadata();
        $metadata->switchRead($indexName)->toFile('index.json');
    }

    public function __construct(Application $console, Kernel $app)
    {
        $this->console = $console;
        $this->config = $app->get('config');
        $this->logger = $app->get('logger');
        $this->app = $app;
        $this->root = __DIR__.'/../..';

        if (!defined('GEARMAN_INSTALLED')) {
            define('GEARMAN_INSTALLED', class_exists('GearmanClient'));
        }

        // Some annotations
        Register::registerLoader();

        $this->console->getDefinition()->addOption(new InputOption('--env', '-e', InputOption::VALUE_REQUIRED, 'The Environment name.', 'dev'));

        // Add commands from the DI container. (for more complex commands.)
        if (GEARMAN_INSTALLED) {
            try {
                $this->console->addCommands([
                    $app->get('console.gearman.worker'),
                    $app->get('console.gearman.client'),
                    $app->get('console.gearman.queue'),
                    $app->get('console.build_index'),
                ]);
            } catch (SqsException $e) {
                $this->logger->debug('Cannot connect to SQS so some commands are not available', ['exception' => $e]);
            }
        }
    }

    private function path($path = '')
    {
        return $this->root.$path;
    }

    public function getElasticClient() : ElasticsearchClient
    {
        return $this->app->get('elastic.client');
    }

    public function cacheClearCommand(InputInterface $input, OutputInterface $output)
    {
        $this->logger->info('Clearing cache...');
        try {
            exec('rm -rf '.$this->root.'/var/cache/*');
        } catch (Exception $e) {
            $this->logger->error('Cannot clean var/cache/', ['exception' => $e]);
        }
        $this->logger->info('Cache cleared successfully.');
    }

    public function run($input = null, $output = null)
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
                    $this->{$fn.'Command'}($input, $output);
                }, $this));

            if (isset($cmd['args'])) {
                foreach ($cmd['args'] as $arg) {
                    $command->addArgument($arg['name'], $arg['mode'] ?? null, $arg['description'] ?? '', $arg['default'] ?? null);
                }
            }
        }
        $this->console->run($input, $output);
    }
}
