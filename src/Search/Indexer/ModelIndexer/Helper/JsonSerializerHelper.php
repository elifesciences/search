<?php

namespace eLife\Search\Indexer\ModelIndexer\Helper;

use Symfony\Component\Serializer\Serializer;

trait JsonSerializerHelper
{
    private static $cache = [];

    abstract protected function getSdkClass() : string;
    abstract protected function getSerializer() : Serializer;

    protected function deserialize(string $json)
    {
        return $this->getSerializer()->deserialize($json, $this->getSdkClass(), 'json');

        //todo: the following code causes a conflict when reading from $cache
        $key = sha1($json);
        if (!isset(self::$cache[$key])) {
            self::$cache[$key] = $this->getSerializer()->deserialize($json, $this->getSdkClass(), 'json');
        }

        return self::$cache[$key];
    }

    protected function serialize($item) : string
    {
        return $this->getSerializer()->serialize($item, 'json');

        //todo: the following code causes a conflict when reading from $cache
        $key = spl_object_hash($item);
        if (!isset(self::$cache[$key])) {
            self::$cache[$key] = $this->getSerializer()->serialize($item, 'json');
        }

        return self::$cache[$key];
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
