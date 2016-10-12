<?php

namespace eLife\Search\Api\Elasticsearch\Response;

use JMS\Serializer\Annotation\Type;

final class DocumentResponse implements ElasticResponse
{
    /**
     * @Type("eLife\Search\Api\Response\SearchResult")
     */
    public $_source;

    public function unwrap()
    {
        return $this->_source;
    }
}
