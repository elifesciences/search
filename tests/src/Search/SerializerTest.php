<?php

namespace tests\eLife\Search;

use Doctrine\Common\Annotations\AnnotationRegistry;
use eLife\Search\Api\SearchResultDiscriminator;
use JMS\Serializer\EventDispatcher\EventDispatcher;
use JMS\Serializer\SerializationContext;
use JMS\Serializer\SerializerBuilder;
use PHPUnit_Framework_TestCase;
use Throwable;

abstract class SerializerTest extends PHPUnit_Framework_TestCase
{
    abstract public function getResponseClass() : string;

    abstract public function jsonProvider() : array;

    /**
     * @dataProvider jsonProvider
     */
    public function testSerialization($actual_json, $expected)
    {
        try {
            $event = $this->serializer->deserialize($actual_json, $this->getResponseClass(), 'json');
        } catch (Throwable $e) {
            $this->fail('Serialization failed: '.$e->getMessage());

            return null;
        }
        $actual = $this->serialize($event, 1);
        $this->assertJsonStringEqualsJsonString($expected, $actual);
    }

    private $serializer;
    private $context;

    public function __construct($name = null, $data = [], $dataName = '')
    {
        // Annotations.
        AnnotationRegistry::registerAutoloadNamespace(
            'JMS\Serializer\Annotation', __DIR__.'/../../../vendor/jms/serializer/src'
        );
        // Serializer.
        $this->serializer = SerializerBuilder::create()
            ->configureListeners(function (EventDispatcher $dispatcher) {
                $dispatcher->addSubscriber(new SearchResultDiscriminator());
            })
            ->build();
        $this->context = SerializationContext::create();

        parent::__construct($name, $data, $dataName);
    }

    protected function responseFromArray($className, $data)
    {
        return $this->serializer->deserialize(json_encode($data), $className, 'json');
    }

    protected function assertEqualJson($actual, $expected)
    {
        $this->assertEquals(json_decode($actual), json_decode($expected));
    }

    protected function serialize($data, int $version = null, $group = null)
    {
        $context = $this->context;
        if ($version) {
            $context->setVersion($version);
        }
        if ($group) {
            $context->setGroups([$group]);
        }

        return $this->serializer->serialize($data, 'json', $context);
    }
}
