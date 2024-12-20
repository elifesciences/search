<?php

namespace eLife\Search\Api\Elasticsearch\ResponsePartials;

use JMS\Serializer\Annotation\SerializedName;
use JMS\Serializer\Annotation\Type;

final class Hits
{
    /**
     * @Type("integer")
     */
    public int $total;

    /**
     * @Type("float")
     * @SerializedName("max_score")
     */
    public float $maxScore;

    /**
     * @Type("array<eLife\Search\Api\Elasticsearch\ResponsePartials\HitItem>")
     * @var array<\eLife\Search\Api\Elasticsearch\ResponsePartials\HitItem> $hits
     */
    public array $hits;

    public function getHitItem() : array
    {
        return $this->hits;
    }
}
