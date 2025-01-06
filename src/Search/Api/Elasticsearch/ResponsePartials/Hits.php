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
    public float|null $maxScore;

    /**
     * @Type("array<eLife\Search\Api\Elasticsearch\ResponsePartials\HitItem>")
     * @var array<HitItem> $hits
     */
    public array $hits;

    /** @return array<HitItem> */
    public function getHitItem() : array
    {
        return $this->hits;
    }
}
