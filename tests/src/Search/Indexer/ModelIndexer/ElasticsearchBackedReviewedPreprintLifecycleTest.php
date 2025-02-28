<?php

namespace tests\eLife\Search\Indexer\ModelIndexer;

use eLife\Search\Api\Elasticsearch\MappedElasticsearchClient;
use eLife\Search\Indexer\ModelIndexer\ElasticsearchBackedReviewedPreprintLifecycle;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class ElasticsearchBackedReviewedPreprintLifecycleTest extends TestCase
{
    #[Test]
    public function givenAReviewedPreprintIdWhenNoSupportedArticleTypeWithThatIdIsFoundThenIsNotSuperseded()
    {
        $this->markTestSkipped();
        // @phpstan-ignore deadCode.unreachable
        $client = $this->createStub(MappedElasticsearchClient::class);
        $idOfReviewedPreprintThatIsNotSuperseded = '12345';
        $result = (new ElasticsearchBackedReviewedPreprintLifecycle($client))->isSuperseded($idOfReviewedPreprintThatIsNotSuperseded);
        $this->assertFalse($result);
    }
}
