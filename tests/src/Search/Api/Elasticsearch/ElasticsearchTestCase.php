<?php

namespace tests\eLife\Search\Api\Elasticsearch;

use Doctrine\Common\Annotations\AnnotationRegistry;
use eLife\Search\Api\Elasticsearch\ElasticsearchDiscriminator;
use eLife\Search\Api\Elasticsearch\Response\ElasticResponse;
use eLife\Search\Api\Elasticsearch\Response\SearchResponse;
use eLife\Search\Api\Response\SearchResult;
use eLife\Search\Api\SearchResultDiscriminator;
use JMS\Serializer\EventDispatcher\EventDispatcher;
use JMS\Serializer\Serializer;
use JMS\Serializer\SerializerBuilder;
use PHPUnit_Framework_TestCase;

abstract class ElasticsearchTestCase extends PHPUnit_Framework_TestCase
{
    /** @var Serializer */
    private $serializer;

    abstract public function jsonProvider() : array;

    /**
     * @dataProvider jsonProvider
     * @test
     */
    final public function testDeserialization($json)
    {
        $this->assertValidSearchResults(
            $this->deserialize(
                $this->makeJsonQuery(
                    $this->wrapEsJson($json)
                )
            )
        );
    }

    public function setUp()
    {
        // Annotations.
        AnnotationRegistry::registerAutoloadNamespace(
            'JMS\Serializer\Annotation', __DIR__.'/../../../../../vendor/jms/serializer/src'
        );
        // Serializer.
        $this->serializer = SerializerBuilder::create()
            ->configureListeners(function (EventDispatcher $dispatcher) {
                $dispatcher->addSubscriber(new ElasticsearchDiscriminator());
                $dispatcher->addSubscriber(new SearchResultDiscriminator());
            })
            ->build();
    }

    protected function assertValidSearchResults(SearchResponse $model, $expected_count = 1)
    {
        // Make sure total is correct.
        $this->assertEquals($expected_count, $model->getTotalResults());
        $i = 0;
        // Make sure we can iterate through.
        foreach ($model as $item) {
            ++$i;
            $this->assertInstanceOf(SearchResult::class, $item);
        }
        $this->assertFalse($i === 0, 'Result set must be iterable.');
        // Make sure we didn't just iterate
        $this->assertNotEmpty($model->getResults());
    }

    protected function deserialize($json) : ElasticResponse
    {
        $model = $this->serializer->deserialize($json, ElasticResponse::class, 'json');
        $this->assertInstanceOf(ElasticResponse::class, $model);

        return $model;
    }

    protected function makeJsonMultiQuery($hits = []) : string
    {
        return $this->makeJsonQuery(implode(', ', array_map([$this, 'wrapEsJson'], $hits)), count($hits));
    }

    protected function wrapEsJson($json) : string
    {
        return '{"_source":'.$json.'}';
    }

    protected function makeJsonQuery(string $hit, $count = 1) : string
    {
        $json = '
            {
                "took": 1,
                "timed_out": false,
                "_shards": {
                    "total": 5,
                    "successful": 5,
                    "failed": 0
                },
                "hits": {
                    "total": ' .$count.',
                    "max_score": 0.30685282,
                    "hits": [
                        ' .$hit.'
                    ]
                }
            }
        ';

        return $json;
    }
}
