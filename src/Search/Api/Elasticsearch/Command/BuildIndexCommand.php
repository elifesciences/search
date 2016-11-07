<?php

namespace eLife\Search\Api\Elasticsearch\Command;

use eLife\Search\Api\Elasticsearch\ElasticsearchClient;
use eLife\Search\Api\Elasticsearch\Response\SuccessResponse;
use eLife\Search\Workflow\CliLogger;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Yaml\Yaml;
use Throwable;

class BuildIndexCommand extends Command
{
    private $client;

    public function __construct(ElasticsearchClient $client)
    {
        $this->client = $client;

        parent::__construct(null);
    }

    protected function configure()
    {
        $this
            ->setName('search:reindex')
            ->setDescription('Re-index elasticsearch <comment>WARNING: DROPS CONTENT</comment>')
            ->setHelp('Creates new Gearman client and imports entities from API');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $logger = new CliLogger($input, $output);

        $mapping = array_filter(
            array_merge(
                [],
                Yaml::parse(file_get_contents(__DIR__.'/resources/_default_.yaml')),
                Yaml::parse(file_get_contents(__DIR__.'/resources/article.yaml')),
                Yaml::parse(file_get_contents(__DIR__.'/resources/blog-article.yaml')),
                Yaml::parse(file_get_contents(__DIR__.'/resources/event.yaml')),
                Yaml::parse(file_get_contents(__DIR__.'/resources/interview.yaml')),
                Yaml::parse(file_get_contents(__DIR__.'/resources/labs-experiment.yaml'))
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
        try {
            $delete = $this->client->deleteIndex();
        } catch (Throwable $e) {
            $logger->debug($e->getMessage(), $e->getTrace());
        }
        if ($delete['payload'] instanceof SuccessResponse) {
            $logger->info('Removed previous index');
        }

        // Try adding new one!
        try {
            $create = $this->client->customIndex($config);
        } catch (Throwable $e) {
            $logger->error($e->getMessage(), $e->getTrace());
        }
        if ($create['payload'] instanceof SuccessResponse) {
            $logger->info('Created new index <comment>[Don\'t forget to re-index!]</comment>');
        }
    }
}
