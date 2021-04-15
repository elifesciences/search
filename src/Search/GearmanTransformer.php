<?php

namespace eLife\Search;

use eLife\Bus\Queue\QueueItem;
use eLife\Bus\Queue\QueueItemTransformer;
use LogicException;

class GearmanTransformer implements QueueItemTransformer
{
    public function transform(QueueItem $item, bool $serialized = true)
    {
        $type = self::$typeMap[$item->getType()] ?? null;
        if (null === $type) {
            throw new LogicException('Workflow does not exist for that type.');
        }

        return $type;
    }

    private static $typeMap = [
        'blog-article' => 'blog_article_index',
        'interview' => 'interview_index',
        'labs-post' => 'labs_post_index',
        'podcast-episode' => 'podcast_episode_index',
        'collection' => 'collection_index',
        'article' => 'research_article_index',
    ];
}
