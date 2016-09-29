<?php

namespace eLife\Search\Api\Elasticsearch\ResponsePartials;

use eLife\Search\Api\Response\SearchResult;

use JMS\Serializer\Annotation\SerializedName;
use JMS\Serializer\Annotation\Type;

class HitItem
{

    /**
     * @Type(SearchResult::class)
     * @SerializedName("_source")
     */
    public $_source;

    public function unwrap() : SearchResult
    {
        return $this->_source;
    }

}
