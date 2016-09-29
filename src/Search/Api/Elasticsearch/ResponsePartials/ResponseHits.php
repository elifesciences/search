<?php

namespace eLife\Search\Api\Elasticsearch\ResponsePartials;

use JMS\Serializer\Annotation\SerializedName;
use JMS\Serializer\Annotation\Type;

trait ResponseHits
{
    /**
     * @Type("integer")
     */
    public $took;

    /**
     * @Type("boolean")
     */
    public $timed_out;

    /**
     * @Type("array")
     * @SerializedName("_shards")
     */
    public $_shards;

    /**
     * @Type("eLife\Search\Api\Elasticsearch\ResponsePartials\Hits")
     *
     * @var Hits
     */
    public $hits;

    public function getHits() : Hits
    {
        return $this->hits;
    }

    public function getTotal() : int
    {
        return $this->_shards['total'] ?? 0;
    }

    public function getTotalResults() : int
    {
        return $this->_shards['successful'] ?? 0;
    }
}
