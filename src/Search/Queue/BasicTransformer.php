<?php

namespace eLife\Search\Queue;

use eLife\ApiSdk\ApiSdk;
use JMS\Serializer\Serializer;
use LogicException;

trait BasicTransformer
{
    /** @var ApiSdk */
    private $sdk;
    /** @var Serializer */
    private $serializer;

    public function getSdk(QueueItem $item)
    {
        switch ($item->getType()) {
            case 'blog-article':
                return $this->sdk->blogArticles();
                break;

            case 'event':
                return $this->sdk->events();
                break;

            case 'interview':
                return $this->sdk->interviews();
                break;

            case 'labs-experiment':
                return $this->sdk->labsExperiments();
                break;

            case 'podcast-episode':
                return $this->sdk->podcastEpisodes();
                break;

            case 'collection':
                return $this->sdk->collections();
                break;

            case 'article':
                return $this->sdk->articles();
                break;

            default:
                throw new LogicException("ApiSDK does not exist for the type `{$item->getType()}`.");
        }
    }

    public function transform(QueueItem $item)
    {
        $sdk = $this->getSdk($item);
        $entity = $sdk->get($item->getId());

        return $this->serializer->serialize($entity->wait(true), 'json');
    }

    private static $typeMap = [
        'blog-article' => 'blog_article_validate',
        'event' => 'event_validate',
        'interview' => 'interview_article_validate',
        'labs-experiment' => 'labs_experiment_validate',
        'podcast-episode' => 'podcast_episode_validate',
        'collection' => 'collection_validate',
        'article' => 'research_article_validate',
    ];

    public function getGearmanTask(QueueItem $item) : string
    {
        $type = self::$typeMap[$item->getType()] ?? null;
        if ($type === null) {
            throw new LogicException('Workflow does not exist for that type.');
        }

        return $type;
    }
}
