<?php

namespace tests\eLife\Search\Indexer\ModelIndexer;

use eLife\Search\Indexer\ModelIndexer;
use Symfony\Component\Serializer\Serializer;
use ReflectionMethod;

trait CallSerializer
{
    public function callSerialize(ModelIndexer $indexer, $entity)
    {
        $method = new ReflectionMethod($indexer, 'serialize');
        $method->setAccessible(true);
        $value = $method->invoke($indexer, $entity);
        $method->setAccessible(false);
        return $value;
    }

    public function callDeserialize(ModelIndexer $indexer, $entity)
    {
        $method = new ReflectionMethod($indexer, 'deserialize');
        $method->setAccessible(true);
        $value = $method->invoke($indexer, $entity);
        $method->setAccessible(false);
        return $value;
    }
}
