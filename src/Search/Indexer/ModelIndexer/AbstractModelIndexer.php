<?php

namespace eLife\Search\Indexer\ModelIndexer;

use eLife\Search\Indexer\ModelIndexer;
use Symfony\Component\Serializer\Serializer;

abstract class AbstractModelIndexer implements ModelIndexer
{
    use Helper\Blocks;
    use Helper\JsonSerializerHelper;
    use Helper\SortDate;

    protected $serializer;

    public function __construct(Serializer $serializer)
    {
        $this->serializer = $serializer;
    }

    protected function getSerializer(): Serializer
    {
        return $this->serializer;
    }

    abstract protected function getSdkClass() : string;
}
