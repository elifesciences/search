<?php

namespace eLife\Search\Workflow;

use LogicException;
use Symfony\Component\Serializer\Serializer;

trait JsonSerializeTransport
{
    private static $cache = [];

    abstract public function getSdkClass() : string;

    private function checkSerializer()
    {
        if (
            !isset($this->serializer) ||
            null === $this->serializer ||
            !$this->serializer instanceof Serializer
        ) {
            throw new LogicException('You must inject API SDK serializer for this to work (property: $serializer missing.)');
        }
    }

    public function deserialize(string $json)
    {
        $this->checkSerializer();

        $key = sha1($json);
        if (!isset(self::$cache[$key])) {
            self::$cache[$key] = $this->serializer->deserialize($json, $this->getSdkClass(), 'json');
        }

        return self::$cache[$key];
    }

    public function serialize($item) : string
    {
        $this->checkSerializer();

        $key = spl_object_hash($item);
        if (!isset(self::$cache[$key])) {
            self::$cache[$key] = $this->serializer->serialize($item, 'json');
        }

        return self::$cache[$key];
    }

    public function snippet($item) : array
    {
        $this->checkSerializer();

        return $this->serializer->normalize(
            $item,
            null,
            ['snippet' => true, 'type' => true]
        );
    }
}
