<?php

namespace tests\eLife\Search\Api\Response\Elasticsearch;

use Doctrine\Common\Annotations\AnnotationRegistry;
use eLife\Search\Api\Elasticsearch\ElasticSearchResponse;
use eLife\Search\Api\Response\SearchResult;
use eLife\Search\Api\SearchResultDiscriminator;
use JMS\Serializer\EventDispatcher\EventDispatcher;
use JMS\Serializer\SerializerBuilder;
use PHPUnit_Framework_TestCase;

final class ElasticsearchResponseTest extends PHPUnit_Framework_TestCase
{
    private $serializer;

    public function setUp()
    {
        // Annotations.
        AnnotationRegistry::registerAutoloadNamespace(
            'JMS\Serializer\Annotation', __DIR__.'/../../../../../vendor/jms/serializer/src'
        );
        // Serializer.
        $this->serializer = SerializerBuilder::create()
            ->configureListeners(function (EventDispatcher $dispatcher) {
                $dispatcher->addSubscriber(new SearchResultDiscriminator());
            })
            ->build();
    }

    /**
     * @test
     */
    public function testDeserialization()
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
                    "total": 1,
                    "max_score": 0.30685282,
                    "hits": [
                        {
                            "_source": {
                                "id": "12456",
                                "type": "blog-article",
                                "title": "some blog article",
                                "impactStatement": "Something impacting in a statement like fashion.",
                                "published": "2016-06-09T15:15:10+00:00"
                            }
                        }
                    ]
                }
            }
        ';

        $model = $this->serializer->deserialize($json, ElasticSearchResponse::class, 'json');
        $this->assertInstanceOf(ElasticSearchResponse::class, $model);
        // IDE.
        if ($model instanceof ElasticSearchResponse) {
            // Make sure total is correct.
            $this->assertEquals(5, $model->getTotalResults());
            // Make sure we can iterate through.
            foreach ($model as $item) {
                $this->assertInstanceOf(SearchResult::class, $item);
            }
            // Make sure we didn't just iterate
            $this->assertNotEmpty($model->getResults());
        }
    }
}
