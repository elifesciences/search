<?php

namespace eLife\Search\Api\Elasticsearch\ResponsePartials;

use JMS\Serializer\Annotation\SerializedName;
use JMS\Serializer\Annotation\Type;

final class Hits
{
    /**
     * @Type("integer")
     */
    public $total;

    /**
     * @Type("float")
     * @SerializedName("max_score")
     */
    public $maxScore;

    /**
     * @Type("array<eLife\Search\Api\Elasticsearch\ResponsePartials\HitItem>")
     */
    public $hits;

    public function getHitItem() : array
    {
        return $this->hits;
    }
}
