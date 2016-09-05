<?php

namespace eLife\Search\Api\Response;

use JMS\Serializer\Annotation\Discriminator;

/**
 * @Discriminator(field = "type", map = {
 *    "blog-article": BlogArticleResponse::class,
 *    "labs-experiment": LabExperimentResponse::class,
 *    "podcast-episode": PodcastEpisodeResponse::class,
 *    "interview": InterviewResponse::class,
 *    "event": EventResponse::class,
 *    "collection": CollectionResponse::class
 * })
 */
interface SearchResult
{
    public function getType() : string;
}
