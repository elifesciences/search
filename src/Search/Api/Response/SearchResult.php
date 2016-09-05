<?php

namespace eLife\Search\Api\Response;

use JMS\Serializer\Annotation\Discriminator;
use eLife\Search\Api\Response\ArticleResponse\PoaArticle;
use eLife\Search\Api\Response\ArticleResponse\VorArticle;

/**
 * @Discriminator(field = "internal_type", map = {
 *    "blog-article": BlogArticleResponse::class,
 *    "labs-experiment": LabExperimentResponse::class,
 *    "podcast-episode": PodcastEpisodeResponse::class,
 *    "interview": InterviewResponse::class,
 *    "event": EventResponse::class,
 *    "research-article--poa": PoaArticle::class,
 *    "research-article--vor": VorArticle::class,
 *    "collection": CollectionResponse::class
 * })
 */
interface SearchResult
{
    public function getType() : string;
}
