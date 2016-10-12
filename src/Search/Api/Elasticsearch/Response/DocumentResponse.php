<?php

namespace eLife\Search\Api\Elasticsearch\Response;

use JMS\Serializer\Annotation\Type;

class DocumentResponse implements ElasticResponse
{
    /**
     * @Type("eLife\Search\Api\Response\SearchResult")
     */
    public $_source;
}
