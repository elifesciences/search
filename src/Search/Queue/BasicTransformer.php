<?php

namespace eLife\Search\Queue;

use MongoDB\Driver\Exception\LogicException;

trait BasicTransformer
{
    private $sdk;
    private $serializer;

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
