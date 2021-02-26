<?php

namespace eLife\Search\Gearman\Command;

use Elasticsearch\Common\Exceptions\ElasticsearchException;
use eLife\ApiSdk\ApiSdk;
use eLife\ApiSdk\Model\ArticleVersion;
use eLife\ApiSdk\Model\BlogArticle;
use eLife\ApiSdk\Model\Collection;
use eLife\ApiSdk\Model\Interview;
use eLife\ApiSdk\Model\LabsPost;
use eLife\ApiSdk\Model\PodcastEpisode;
use eLife\Bus\Command\QueueCommand;
use eLife\Bus\Queue\QueueItem;
use eLife\Bus\Queue\QueueItemTransformer;
use eLife\Bus\Queue\WatchableQueue;
use eLife\Logging\Monitoring;
use eLife\Search\Api\Elasticsearch\MappedElasticsearchClient;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;

class QueueWatchCommand extends QueueCommand
{
    const ITEM_TYPES = [
        'article' => ArticleVersion::class,
        'blog-article' => BlogArticle::class,
        'collection' => Collection::class,
        'interview' => Interview::class,
        'labs-post' => LabsPost::class,
        'podcast-episode' => PodcastEpisode::class,
    ];

    private $sdk;
    private $client;
    private $isMock;

    public function __construct(
        WatchableQueue $queue,
        QueueItemTransformer $transformer,
        ApiSdk $sdk,
        MappedElasticsearchClient $client,
        bool $isMock,
        LoggerInterface $logger,
        Monitoring $monitoring,
        callable $limit
    ) {
        parent::__construct($logger, $queue, $transformer, $monitoring, $limit);
        $this->sdk = $sdk;
        $this->client = $client;
        $this->isMock = $isMock;
    }

    protected function configure()
    {
        $this
            ->setName('queue:watch')
            ->setDescription('Create queue watcher')
            ->setHelp('Creates process that will watch for incoming items on a queue')
            ->addArgument('id', InputArgument::OPTIONAL, 'Identifier to distinguish workers from each other');
    }

    protected function process(InputInterface $input, QueueItem $item, $entity = null)
    {
        $entity = $this->transform($item);

        if ($entity) {
            $object = json_decode($entity, true);
            $model = $this->sdk->getSerializer()->denormalize($object, self::ITEM_TYPES[$item->getType()]);
            $document = $this->sdk->getSerializer()->normalize($model, null, ['snippet' => false, 'type' => true]);
            $document['snippet'] = $this->sdk->getSerializer()->normalize($model, null, ['snippet' => true, 'type' => true]);
            try {
                $this->client->indexJsonDocument($item->getType(), $item->getId(), $document);
            } catch (ElasticsearchException $exception) {
                $this->logger->error('Error indexing', [
                    'type' => $item->getType(),
                    'id' => $item->getId(),
                    'message' => $exception->getMessage(),
                ]);
            }
        }
    }
}
