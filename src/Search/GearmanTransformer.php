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
        if ($type === null) {
            throw new LogicException('Workflow does not exist for that type.');
        }

        return $type;
    }

    private static $typeMap = [
        'blog-article' => 'blog_article_validate',
        'interview' => 'interview_validate',
        'labs-experiment' => 'labs_experiment_validate',
        'podcast-episode' => 'podcast_episode_validate',
        'collection' => 'collection_validate',
        'article' => 'research_article_validate',
    ];
}
