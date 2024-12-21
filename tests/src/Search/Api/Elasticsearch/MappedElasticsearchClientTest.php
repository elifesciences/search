<?php

namespace test\eLife\Search\Api\Elasticsearch;

use Elasticsearch\Client;
use Elasticsearch\Common\Exceptions\Missing404Exception;
use eLife\Search\Api\Elasticsearch\MappedElasticsearchClient;
use eLife\Search\Api\Elasticsearch\Response\ElasticResponse;
use Mockery;
use Mockery\MockInterface;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class MappedElasticsearchClientTest extends TestCase
{
    private MappedElasticsearchClient $elasticsearchClient;
    private Client&MockInterface $client;

    public function setUp(): void
    {
        /** @var Client&MockInterface $client */
        $client = Mockery::mock(Client::class);
        $this->client = $client;
        $this->elasticsearchClient = new MappedElasticsearchClient($this->client, 'index');
    }

    #[Test]
    public function testGetDocumentById()
    {
        $mockDocumentResponse = Mockery::mock(ElasticResponse::class);
        /** @var \Mockery\Expectation $getExpectation */
        $getExpectation = $this->client->shouldReceive('get');
        $getExpectation
            ->with([
                'index' => 'index',
                'id' => 'id',
                'client' => [],
            ])
            ->andReturn(['payload' => $mockDocumentResponse]);

        $this->assertSame($mockDocumentResponse, $this->elasticsearchClient->getDocumentById('id'));

        /** @var \Mockery\Expectation $getExpectation */
        $getExpectation = $this->client->shouldReceive('get');
        $getExpectation
            ->with([
                'index' => 'override-index',
                'id' => 'id',
                'client' => [],
            ])
            ->andReturn(['payload' => $mockDocumentResponse]);

        $this->assertSame($mockDocumentResponse, $this->elasticsearchClient->getDocumentById('id', 'override-index'));

        /** @var \Mockery\Expectation $getExpectation */
        $getExpectation = $this->client->shouldReceive('get');
        $getExpectation
            ->with([
                'index' => 'index',
                'id' => 'unknown-id',
                'client' => [],
            ])
            ->andReturnUsing(function () {
                throw new Missing404Exception('missing');
            });

        $this->assertNull($this->elasticsearchClient->getDocumentById('unknown-id', null, true));
    }
}
