<?php

namespace eLife\Search\Api\Elasticsearch\Response;

use JMS\Serializer\Annotation\Discriminator;

/**
 * @Discriminator(field = "internal_search_type", map = {
 *    "document": DocumentResponse::class,
 *    "search": SearchResponse::class,
 *    "error": ErrorResponse::class,
 *    "success": SuccessResponse::class,
 *    "unknown": UnknownResponse::class
 * })
 */
interface ElasticResponse
{
}
