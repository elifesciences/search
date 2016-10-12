<?php

namespace eLife\Search\Api\Response;

use eLife\Search\Api\Response\ArticleResponse\PoaArticle;
use eLife\Search\Api\Response\ArticleResponse\VorArticle;
use JMS\Serializer\Annotation\Discriminator;

/**
 * @Discriminator(field = "internal_type", map = {
 *    "blog-article": BlogArticleResponse::class,
 *    "labs-experiment": LabsExperimentResponse::class,
 *    "podcast-episode": PodcastEpisodeResponse::class,
 *    "interview": InterviewResponse::class,
 *    "event": EventResponse::class,
 *    "research-article--poa": PoaArticle::class,
 *    "research-article--vor": VorArticle::class,
 *    "collection": CollectionResponse::class
 * })
 *
 * @property $internal_type
 * @property $status
 */
interface SearchResult
{
    public function getType() : string;
}
