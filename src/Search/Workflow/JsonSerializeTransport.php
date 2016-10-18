<?php

namespace eLife\Search\Workflow;

use LogicException;
use Symfony\Component\Serializer\Serializer;

trait JsonSerializeTransport
{
    private static $cache = [];

    abstract public function getSdkClass() : string;

    public function deserialize(string $json)
    {
        if (
            !isset($this->serializer) ||
            $this->serializer === null ||
            !$this->serializer instanceof Serializer
        ) {
            throw new LogicException('You must inject API SDK serializer for this to work (property: $serializer missing.)');
        }
        $key = sha1($json);
        if (!isset(self::$cache[$key])) {
            self::$cache[$key] = $this->serializer->deserialize($json, $this->getSdkClass(), 'json');
        }

        return self::$cache[$key];
    }

    public function serialize($article) : string
    {
        if (
            !isset($this->serializer) ||
            $this->serializer === null ||
            !$this->serializer instanceof Serializer
        ) {
            throw new LogicException('You must inject API SDK serializer for this to work (property: $serializer missing.)');
        }
        $key = spl_object_hash($article);
        if (!isset(self::$cache[$key])) {
            self::$cache[$key] = $this->serializer->serialize($article, 'json');
        }

        return self::$cache[$key];
    }
}
