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

final class ApiSdkCommand extends Command
{
    private static $supports = ['BlogArticles'];

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
            ->addArgument('entity', InputArgument::REQUIRED, 'Must be one of the following <comment>['.implode(', ', self::$supports).']</comment>')
        ;
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
        // Run the item.
        $this->{'import'.$entity}($logger);
        // Reporting.
        $logger->notice('All entities queued.');
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

    private function task($item, ...$data)
    {
        $this->client->doLow($item, ...$data);
    }
}
