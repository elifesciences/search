<?php

namespace eLife\Search\Queue\Mock;

use eLife\ApiSdk\ApiSdk;
use eLife\Search\Queue\QueueItem;
use eLife\Search\Queue\QueueItemTransformer;
use LogicException;

final class QueueItemTransformerMock implements QueueItemTransformer
{
    private $sdk;
    private $serializer;

    public function __construct(
        ApiSdk $sdk
    ) {
        $this->serializer = $sdk->getSerializer();
        $this->sdk = $sdk;
    }

    public function getSdk(QueueItem $item)
    {
        switch ($item->getType()) {
            case 'blog-article':
                return $this->sdk->blogArticles();
                break;

            // ...
            default:
                throw new LogicException('Wat');
        }
    }

    public function transform(QueueItem $item)
    {
        $sdk = $this->getSdk($item);
        $entity = $sdk->get($item->getId());

        return $this->serializer->serialize($entity->wait(true), 'json');
    }

    public function getGearmanTask(QueueItem $item) : string
    {
        switch ($item->getType()) {
            case 'blog-article':
                return 'blog_article_validate';

            // ...
            default:
                throw new LogicException('Wat');
        }
    }
}
