<?php

namespace eLife\Search;

use Aws\Sqs\Exception\SqsException;
use Aws\Sqs\SqsClient;
use Closure;
use eLife\ApiValidator\Exception\InvalidMessage;
use eLife\Bus\Queue\InternalSqsMessage;
use eLife\Bus\Queue\WatchableQueue;
use eLife\Search\Annotation\Register;
use eLife\Search\KeyValueStore\ElasticsearchKeyValueStore;
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
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

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
        'index:total:read' => [
            'description' => 'The total number of items on the read index',
        ],
        'index:total:write' => [
            'description' => 'The total number of items on the write index',
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
        'index:delete:unused' => [
            'description' => 'Delete all unused indexes',
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
        'rds:reindex' => [
            'description' => 'Reindex RDS articles to correctly place them in listings',
        ],
        'reviewedPreprint:reindex' => [
            'description' => 'Reindex reviewed preprints to correctly place them in listings',
        ],
        'gateway:total' => [
            'description' => 'Get the total number of items that could potentially be indexed from the API gateway',
        ],
        'search:total' => [
            'description' => 'Get the search results total',
        ],
        'search:validate' => [
            'description' => 'Validate all of the search results',
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

    public function indexReadCommand(InputInterface $input, OutputInterface $output)
    {
        $metadata = $this->kernel->indexMetadata();
        $output->writeln($metadata->read());
    }

    public function indexTotalReadCommand(InputInterface $input, OutputInterface $output)
    {
        $metadata = $this->kernel->indexMetadata();
        $client = $this->kernel->get('elastic.client.plain');
        $output->writeln($client->indexCount($metadata->read()));
    }

    public function indexTotalWriteCommand(InputInterface $input, OutputInterface $output)
    {
        $metadata = $this->kernel->indexMetadata();
        $client = $this->kernel->get('elastic.client.plain');
        $output->writeln($client->indexCount($metadata->write()));
    }

    public function indexDeleteCommand(InputInterface $input, OutputInterface $output)
    {
        $client = $this->kernel->get('elastic.client.plain');
        $indexName = $input->getArgument('index_name');
        $this->logger->info("Deleting index {$indexName}");
        $client->deleteIndex($indexName);
    }

    public function indexDeleteUnusedCommand(InputInterface $input, OutputInterface $output)
    {
        $client = $this->kernel->get('elastic.client.plain');
        foreach ($client->allIndexes() as $indexName) {
            if (in_array(
                $indexName,
                [
                    ElasticsearchKeyValueStore::INDEX_NAME,
                    $this->kernel->indexMetadata()->write(),
                    $this->kernel->indexMetadata()->read(),
                ]
            )) {
                $this->logger->info("Preserving index {$indexName}");
                continue;
            }
            $this->logger->info("Deleting index {$indexName}");
            $client->deleteIndex($indexName);
        }
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
        $ids = [];
        foreach (array_keys($this->config['rds_articles']) as $id) {
            $this->logger->info("Queuing RDS article $id");
            $this->enqueue('article', $id);
            $ids[] = $id;
        }
        $output->writeln('Queued: '.implode(', ', $ids));
        $this->logger->info('RDS articles added to indexing queue.');
    }

    public function reviewedPreprintReindexCommand(InputInterface $input, OutputInterface $output)
    {
        $this->logger->info('Reindex reviewed preprints...');
        $ids = [];
        foreach (array_keys($this->config['reviewed_preprints']) as $id) {
            $this->logger->info("Queuing reviewed preprint $id");
            $this->enqueue('reviewed-preprint', $id);
            $ids[] = $id;
        }
        $output->writeln('Queued: '.implode(', ', $ids));
        $this->logger->info('Reviewed preprints added to indexing queue.');
    }

    public function gatewayTotalCommand(InputInterface $input, OutputInterface $output)
    {
        $sdk = $this->kernel->get('api.sdk');
        $total = $sdk->articles()->count();
        $total += $sdk->blogArticles()->count();
        $total += $sdk->collections()->count();
        $total += $sdk->interviews()->count();
        $total += $sdk->labsPosts()->count();
        $total += $sdk->podcastEpisodes()->count();
        $output->writeln($total);
    }

    public function searchTotalCommand(InputInterface $input, OutputInterface $output)
    {
        $output->writeln($this->searchTotal());
    }

    public function searchValidateCommand(InputInterface $input, OutputInterface $output)
    {
        $perPage = 100;
        $total = $this->searchTotal();

        for ($page = 1; $page <= ceil($total / $perPage); $page++) {
            $request = $this->searchRequest($perPage, $page);
            $output->writeln('Validating: '.$request->getRequestUri());
            $response = $this->kernel->getApp()->handle($request);

            if (!$this->kernel->get('validator')->validate($response)) {
                $e = new InvalidMessage('Invalid search response for: '.$request->getRequestUri());
                $this->logger->error(
                    'Invalid search response',
                    ['exception' => $e, 'responseBody' => $response->getContent()]
                );
                throw $e;
            }
        }

        $output->writeln('Valid!');
    }

    private function searchTotal() {
        $response = $this->kernel->getApp()->handle($this->searchRequest(1));
        $json = json_decode($response->getContent());
        return $json->total;
    }

    private function searchRequest(int $perPage = null, int $page = null) : Request
    {
        return Request::create('/search', 'GET', array_filter([
            'per-page' => $perPage,
            'page' => $page,
        ]));
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
                ->setCode(Closure::bind(function (InputInterface $input, OutputInterface $output) use ($fn) {
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
