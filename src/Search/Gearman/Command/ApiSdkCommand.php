<?php

namespace eLife\Search\Gearman\Command;

use eLife\ApiSdk\ApiSdk;
use eLife\Search\Monitoring;
use eLife\Search\Queue\InternalSqsMessage;
use eLife\Search\Queue\WatchableQueue;
use eLife\Search\Signals;
use Error;
use Iterator;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Throwable;

final class ApiSdkCommand extends Command
{
    private static $supports = ['all', 'BlogArticles', 'Events', 'Interviews', 'LabsExperiments', 'PodcastEpisodes', 'Collections', 'ResearchArticles'];

    private $sdk;
    private $serializer;
    private $output;
    private $logger;
    private $monitoring;
    private $queue;
    private $limit;

    public function __construct(
        ApiSdk $sdk,
        WatchableQueue $queue,
        LoggerInterface $logger,
        Monitoring $monitoring,
        callable $limit
    ) {
        $this->serializer = $sdk->getSerializer();
        $this->sdk = $sdk;
        $this->queue = $queue;
        $this->logger = $logger;
        $this->monitoring = $monitoring;
        $this->limit = $limit;
        // Signals
        Signals::register();

        parent::__construct(null);
    }

    protected function configure()
    {
        $this
            ->setName('queue:import')
            ->setDescription('Import items from API.')
            ->setHelp('Lists entities from API and enqueues them')
            ->addArgument('entity', InputArgument::REQUIRED, 'Must be one of the following <comment>['.implode(', ', self::$supports).']</comment>');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->output = $output;
        $entity = $input->getArgument('entity');
        // Only the configured.
        if (!in_array($entity, self::$supports)) {
            $this->logger->error('Entity with name '.$entity.' not supported.');

            return;
        }
        $this->monitoring->nameTransaction('queue:import');
        $this->monitoring->startTransaction();
        if ($entity === 'all') {
            foreach (self::$supports as $e) {
                if ($e !== 'all') {
                    // Run the item.
                    $this->{'import'.$e}();
                }
            }
        } else {
            // Run the item.
            $this->{'import'.$entity}();
        }
        // Reporting.
        $this->logger->info("\nAll entities queued.");
        $this->monitoring->endTransaction();
    }

    public function importPodcastEpisodes()
    {
        $this->logger->info('Importing PodcastEpisodes');
        $episodes = $this->sdk->podcastEpisodes();
        $this->iterateSerializeTask($episodes, 'podcast-episode', 'getNumber', $episodes->count());
    }

    public function importCollections()
    {
        $this->logger->info('Importing Collections');
        $collections = $this->sdk->collections();
        $this->iterateSerializeTask($collections, 'collection', 'getId', $collections->count());
    }

    public function importLabsExperiments()
    {
        $this->logger->info('Importing Labs Experiments');
        $events = $this->sdk->labsExperiments();
        $this->iterateSerializeTask($events, 'labs-experiment', 'getNumber', $events->count());
    }

    public function importResearchArticles()
    {
        $this->logger->info('Importing Research Articles');
        $events = $this->sdk->articles();
        $this->iterateSerializeTask($events, 'article', 'getId', $events->count(), $skipInvalid = true);
    }

    public function importInterviews()
    {
        $this->logger->info('Importing Interviews');
        $events = $this->sdk->interviews();
        $this->iterateSerializeTask($events, 'interview', 'getId', $events->count());
    }

    public function importEvents()
    {
        $this->logger->info('Importing Events');
        $events = $this->sdk->events();
        $this->iterateSerializeTask($events, 'event', 'getId', $events->count());
    }

    public function importBlogArticles()
    {
        $this->logger->info('Importing Blog Articles');
        $articles = $this->sdk->blogArticles();
        $this->iterateSerializeTask($articles, 'blog-article', 'getId', $articles->count());
    }

    private function iterateSerializeTask(Iterator $items, string $type, $method = 'getId', int $count = 0, $skipInvalid = false)
    {
        $progress = new ProgressBar($this->output, $count);
        $limit = $this->limit;

        $items->rewind();
        while ($items->valid() && $limit()) {
            $progress->advance();
            try {
                $item = $items->current();
                if ($item === null) {
                    $items->next();
                    continue;
                }
                $this->enqueue($type, $item->$method());
            } catch (Throwable $e) {
                $item = $item ?? null;
                $this->logger->error('Skipping import on a '.get_class($item), ['exception' => $e]);
                $this->monitoring->recordException($e, 'Skipping import on a '.get_class($item));
            }
            $items->next();
        }
        $progress->finish();
        $progress->clear();
    }

    private function enqueue($type, $identifier)
    {
        $item = new InternalSqsMessage($type, $identifier);
        $this->queue->enqueue($item);
        $this->logger->info("Item ($type, $identifier) added successfully");
    }
}
