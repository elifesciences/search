<?php

namespace eLife\Search\Indexer\ModelIndexer\Helper;

use Symfony\Component\Serializer\Serializer;

trait JsonSerializerHelper
{
    abstract protected function getSdkClass() : string;
    abstract protected function getSerializer() : Serializer;

    protected function deserialize(string $json)
    {
        return $this->getSerializer()->deserialize($json, $this->getSdkClass(), 'json');
    }

    protected function serialize($item) : string
    {
        return $this->getSerializer()->serialize($item, 'json');
    }

    protected function snippet($item) : array
    {
        return $this->getSerializer()->normalize(
            $item,
            null,
            ['snippet' => true, 'type' => true]
        );
    }
}
