<?php

namespace eLife\Search\Gearman\Command;

use eLife\ApiSdk\ApiSdk;
use eLife\ApiSdk\Model\BlogArticle;
use eLife\Search\Workflow\CliLogger;
use Error;
use GearmanClient;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Traversable;

final class ApiSdkCommand extends Command
{
    private static $supports = ['all', 'BlogArticles', 'Events', 'Interviews', 'LabsExperiments', 'PodcastEpisodes', 'Collections', 'ResearchArticles'];

    private $client;
    private $sdk;
    private $serializer;

    public function __construct(
        ApiSdk $sdk,
        GearmanClient $client
    ) {
        $this->serializer = $sdk->getSerializer();
        $this->sdk = $sdk;
        $this->client = $client;
        parent::__construct(null);
    }

    protected function configure()
    {
        $this
            ->setName('gearman:import')
            ->setDescription('Import items from API.')
            ->setHelp('Creates new Gearman client and imports entities from API')
            ->addArgument('entity', InputArgument::REQUIRED, 'Must be one of the following <comment>['.implode(', ', self::$supports).']</comment>');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $logger = new CliLogger($input, $output);
        $entity = $input->getArgument('entity');
        // Only the configured.
        if (!in_array($entity, self::$supports)) {
            $logger->error('Entity with name '.$entity.' not supported.');

            return;
        }
        if ($entity === 'all') {
            foreach (self::$supports as $e) {
                if ($e !== 'all') {
                    // Run the item.
                    $this->{'import'.$e}($logger);
                }
            }
        } else {
            // Run the item.
            $this->{'import'.$entity}($logger);
        }
        // Reporting.
        $logger->notice('All entities queued.');
    }

    public function importPodcastEpisodes(LoggerInterface $logger)
    {
        // Waiting for API SDK models.
        $logger->error('You cannot currently import PodcastEpisodes');
    }

    public function importCollections(LoggerInterface $logger)
    {
        // Waiting for API SDK models.
        $logger->error('You cannot currently import Collections');
    }

    public function importLabsExperiments(LoggerInterface $logger)
    {
        $events = $this->sdk->labsExperiments();
        $this->iterateSerializeTask($events, $logger, 'labs_experiment_validate');
    }

    public function importResearchArticles(LoggerInterface $logger)
    {
        $logger->error('You cannot currently import Articles — Validation not implemented');
        if (null) {
            $events = $this->sdk->articles();
            $this->iterateSerializeTask($events, $logger, 'research_article_validate');
        }
    }

    public function importInterviews(LoggerInterface $logger)
    {
        $events = $this->sdk->interviews();
        $this->iterateSerializeTask($events, $logger, 'interview_validate');
    }

    public function importEvents(LoggerInterface $logger)
    {
        $events = $this->sdk->events();
        $this->iterateSerializeTask($events, $logger, 'event_validate');
    }

    public function importBlogArticles(LoggerInterface $logger)
    {
        $articles = $this->sdk->blogArticles();
        // Loop all articles.
        // @todo ask Chris about garbage collection for large collections. ($articles->flush() that removed entities from memory.)
        foreach ($articles as $article) {
            if ($article instanceof BlogArticle) {
                try {
                    $normalized = $this->serializer->serialize($article, 'json');
                    $logger->info('Starting... '.$article->getTitle());
                    $this->task('blog_article_validate', $normalized);
                } catch (Error $e) {
                    $logger->critical($e->getMessage());
                }
            }
        }
    }

    private function iterateSerializeTask(Traversable $items, LoggerInterface $logger, string $task)
    {
        foreach ($items as $item) {
            try {
                $normalized = $this->serializer->serialize($item, 'json');
                $title = method_exists($item, 'getTitle') ? $item->getTitle() : ' a new '.get_class($item);
                $logger->info('Starting... '.$title);
                $this->task($task, $normalized);
            } catch (Error $e) {
                $logger->critical($e->getMessage());
            }
        }
    }

    private function task($item, ...$data)
    {
        $this->client->doLow($item, ...$data);
    }
}
