<?php

namespace eLife\Search\Queue\Mock;

use eLife\ApiSdk\ApiSdk;
use eLife\Search\Queue\QueueItem;
use eLife\Search\Queue\QueueItemTransformer;
use LogicException;

final  class QueueItemTransformerMock implements QueueItemTransformer
{
    private $sdk;
    private $cache;

    public function __construct(
        ApiSdk $sdk
    ) {
        $this->sdk = $sdk;
    }

    public function getSdk(QueueItem $item) : object
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

    public function transform(QueueItem $item) : object
    {
        $sdk = $this->getSdk($item);
        $entity = $sdk->get($item->getId());
        $this->cache[$item->getId()] = $entity;

        return $entity;
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
