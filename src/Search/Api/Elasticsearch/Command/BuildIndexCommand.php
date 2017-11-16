<?php

namespace eLife\Search\Api\Elasticsearch\Command;

use eLife\Search\Api\Elasticsearch\ElasticsearchClient;
use eLife\Search\Api\Elasticsearch\Response\SuccessResponse;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Yaml\Yaml;
use Throwable;

class BuildIndexCommand extends Command
{
    private $client;
    private $logger;

    public function __construct(ElasticsearchClient $client, LoggerInterface $logger)
    {
        $this->client = $client;
        $this->logger = $logger;

        parent::__construct(null);
    }

    protected function configure()
    {
        $this
            ->setName('search:setup')
            ->setDescription('Ensure Elasticsearch has been setup <comment>WARNING: DROPS CONTENT WITH -d</comment>')
            ->addOption('delete', 'd', InputOption::VALUE_NONE, 'Drop content')
            ->addOption('index', 'i', InputOption::VALUE_OPTIONAL, 'Index that should be (re)created')
            ->setHelp('Creates new Gearman client and imports entities from API');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        if (!empty($input->getOption('index'))) {
            $this->client->defaultIndex($input->getOption('index'));
        }

        $toDelete = $input->getOption('delete');

        $mapping = array_filter(
            array_merge(
                [],
                Yaml::parse(file_get_contents(__DIR__.'/resources/_default_.yaml')),
                Yaml::parse(file_get_contents(__DIR__.'/resources/article.yaml')),
                Yaml::parse(file_get_contents(__DIR__.'/resources/blog-article.yaml')),
                Yaml::parse(file_get_contents(__DIR__.'/resources/interview.yaml')),
                Yaml::parse(file_get_contents(__DIR__.'/resources/labs-post.yaml'))
            ), 'json_encode');

        $config = [
            'client' => ['ignore' => [400, 404]],
            'body' => [
                'mappings' => $mapping,
            ],
        ];

        $delete = null;
        $create = null;

        // Try removing old one.
        if ($toDelete && $this->client->indexExists()) {
            try {
                $delete = $this->client->deleteIndex();
            } catch (Throwable $e) {
                $this->logger->error("Cannot delete ElasticSearch index {$this->client->index()}", ['exception' => $e]);
            }
            if ($delete['acknowledged'] instanceof SuccessResponse) {
                $this->logger->info("Removed previous index $this->client->index()}");
            }
        }

        if (!$this->client->indexExists()) {
            // Try adding new one!
            try {
                $create = $this->client->customIndex($config);
            } catch (Throwable $e) {
                $this->logger->error(
                    "Cannot create ElasticSearch index {$this->client->index()}",
                    ['exception' => $e]
                );
                // Re throw.
                throw $e;
            }
            if ($create['acknowledged']) {
                $this->logger->info("Created new empty index {$this->client->index()}");
            } else {
                $this->logger->error('Index {$this->client->index()}:'.$create['error']['reason'].' skipping creation.');
            }
        } else {
            $this->logger->error("Index {$this->client->index()} already exists, skipping creation.");
        }
    }
}
