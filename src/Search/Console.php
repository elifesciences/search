<?php

namespace eLife\Search;

use Aws\Sqs\Exception\SqsException;
use Aws\Sqs\SqsClient;
use Closure;
use eLife\Bus\Queue\InternalSqsMessage;
use eLife\Bus\Queue\WatchableQueue;
use eLife\Search\Annotation\Register;
use Exception;
use LogicException;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;
use Symfony\Component\Console\Question\Question;

/**
 * @property LoggerInterface temp_logger
 * @property float|int       previousProgress
 * @property ProgressBar     progress
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
        'keyvalue:setup' => [
            'description' => 'Sets up a specific index in ElasticSearch to store arbitrary data as key-value',
        ],
        'keyvalue:store' => [
            'description' => 'Stores an arbitrary key-value pair',
            'args' => [
                [
                    'name' => 'key',
                    'mode' => InputArgument::REQUIRED,
                ],
                [
                    'name' => 'value',
                    'mode' => InputArgument::REQUIRED,
                ],
            ],
        ],
        'keyvalue:load' => [
            'description' => 'Loads an arbitrary key-value pair',
            'args' => [
                [
                    'name' => 'key',
                    'mode' => InputArgument::REQUIRED,
                ],
            ],
        ],
        'index:read' => [
            'description' => 'The name of the index we are reading from in the API',
        ],
        'index:delete' => [
            'description' => 'Delete an index, explicitly using its name',
            'args' => [
                [
                    'name' => 'index_name',
                    'mode' => InputArgument::REQUIRED,
                ],
            ],
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
        'index:lastimport:get' => [
            'description' => 'Returns the last import date',
        ],
        'index:lastimport:update' => [
            'description' => 'Returns the last import date',
            'args' => [
                ['name' => 'date'],
            ],
        ],
        'rds:reindex' => [
            'description' => 'Reindex RDS articles to correctly place them in listings',
        ],
    ];

    public function queueCreateCommand()
    {
        if (true !== $this->config['debug']) {
            throw new LogicException('This method should not be called outside of development');
        }
        /* @var SqsClient $queue */
        $sqs = $this->kernel->get('aws.sqs');

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
        $queue = $this->kernel->get('aws.queue');
        $queue->clean();
    }

    public function queueCountCommand(InputInterface $input, OutputInterface $output)
    {
        $queue = $this->kernel->get('aws.queue');
        $output->writeln($queue->count());
    }

    private function enqueue($type, $id)
    {
        // Create queue item.
        $item = new InternalSqsMessage($type, $id);
        /** @var $queue WatchableQueue */
        $queue = $this->kernel->get('aws.queue');
        // Queue item.
        $queue->enqueue($item);
        $this->logger->info('Item added successfully.');
    }

    public function keyvalueSetupCommand(InputInterface $input, OutputInterface $output)
    {
        $this->kernel->get('keyvaluestore')->setup();
    }

    public function keyvalueStoreCommand(InputInterface $input, OutputInterface $output)
    {
        $this->kernel->get('keyvaluestore')->store(
            $input->getArgument('key'),
            json_decode($input->getArgument('value'), true)
        );
    }

    public function keyvalueLoadCommand(InputInterface $input, OutputInterface $output)
    {
        $output->writeln(var_export(
            $this->kernel->get('keyvaluestore')->load(
                $input->getArgument('key')
            ),
            true
        ));
    }

    public function indexSwitchWriteCommand(InputInterface $input, OutputInterface $output)
    {
        $indexName = $input->getArgument('index_name');
        $metadata = $this->kernel->indexMetadata();
        $this->logger->info("Switching index writes from {$metadata->write()} to $indexName");
        $this->kernel->updateIndexMetadata($metadata->switchWrite($indexName));
    }

    public function indexSwitchReadCommand(InputInterface $input, OutputInterface $output)
    {
        $indexName = $input->getArgument('index_name');
        $metadata = $this->kernel->indexMetadata();
        $this->logger->info("Switching index reads from {$metadata->read()} to $indexName");
        $this->kernel->updateIndexMetadata($metadata->switchRead($indexName));
    }

    public function indexLastImportGetCommand(InputInterface $input, OutputInterface $output)
    {
        $metadata = $this->kernel->indexMetadata();
        $output->writeln($metadata->lastImport());
    }

    public function indexLastImportUpdateCommand(InputInterface $input, OutputInterface $output)
    {
        $newLastImport = $input->getArgument('date');
        $metadata = $this->kernel->indexMetadata();
        $this->kernel->updateIndexMetadata($metadata->updateLastImport($newLastImport));
    }

    public function indexReadCommand(InputInterface $input, OutputInterface $output)
    {
        $metadata = $this->kernel->indexMetadata();
        $output->writeln($metadata->read());
    }

    public function indexDeleteCommand(InputInterface $input, OutputInterface $output)
    {
        $client = $this->kernel->get('elastic.client.plain');
        $indexName = $input->getArgument('index_name');
        $this->logger->info("Deleting index {$indexName}");
        $client->deleteIndex($indexName);
    }

    public function __construct(Application $console, Kernel $kernel)
    {
        $this->console = $console;
        $this->kernel = $kernel;
        $this->config = $this->kernel->get('config');
        $this->logger = $this->kernel->get('logger');
        $this->root = __DIR__.'/../..';

        if (!defined('GEARMAN_INSTALLED')) {
            define('GEARMAN_INSTALLED', class_exists('GearmanClient'));
        }

        // Some annotations
        Register::registerLoader();

        // TODO: remove when it is *never* passed in by the formula or anything else
        $this->console->getDefinition()->addOption(new InputOption('--env', '-e', InputOption::VALUE_OPTIONAL, 'The Environment name. Deprecated and not used', 'dev'));

        // Add commands from the DI container. (for more complex commands.)
        if (GEARMAN_INSTALLED) {
            try {
                $this->console->addCommands([
                    $this->kernel->get('console.gearman.worker'),
                    $this->kernel->get('console.gearman.client'),
                    $this->kernel->get('console.gearman.queue'),
                    $this->kernel->get('console.build_index'),
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

    public function rdsReindexCommand(InputInterface $input, OutputInterface $output)
    {
        $this->logger->info('Reindex RDS articles...');
        if (!$this->config['feature_rds']) {
            $this->logger->warning('RDS feature is not enabled.');

            return;
        }
        $ids = [];
        foreach ($this->config['rds_articles'] as $id => $_) {
            $this->logger->info("Queuing RDS article $id");
            $this->enqueue('article', $id);
            $ids[] = $id;
        }
        $output->writeln('Queued: '.implode(', ', $ids));
        $this->logger->info('RDS articles added to indexing queue.');
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
