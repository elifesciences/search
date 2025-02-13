<?php

namespace test\eLife\Search\Api\Elasticsearch;

use Elasticsearch\Client;
use Elasticsearch\Common\Exceptions\Missing404Exception;
use eLife\Search\Api\Elasticsearch\IndexDeterminer;
use eLife\Search\Api\Elasticsearch\MappedElasticsearchClient;
use Mockery;
use Mockery\MockInterface;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class StubbedIndexDeterminer implements IndexDeterminer
{
    public function getCurrentIndexName(): string
    {
        return 'index';
    }
}

class MappedElasticsearchClientTest extends TestCase
{
    private MappedElasticsearchClient $elasticsearchClient;
    private Client&MockInterface $client;

    public function setUp(): void
    {
        /** @var Client&MockInterface $client */
        $client = Mockery::mock(Client::class);
        $this->client = $client;
        $this->elasticsearchClient = new MappedElasticsearchClient($this->client, 'index', new StubbedIndexDeterminer);
    }

    #[Test]
    public function testGetDocumentById()
    {
        /** @var \Mockery\Expectation $getExpectation */
        $getExpectation = $this->client->shouldReceive('get');
        $getExpectation
            ->with([
                'index' => 'index',
                'id' => 'id',
                'client' => [],
            ])
            ->andReturn(['payload' => 'found']);

        $this->assertSame('found', $this->elasticsearchClient->getDocumentById('id'));

        /** @var \Mockery\Expectation $getExpectation */
        $getExpectation = $this->client->shouldReceive('get');
        $getExpectation
            ->with([
                'index' => 'override-index',
                'id' => 'id',
                'client' => [],
            ])
            ->andReturn(['payload' => 'found']);

        $this->assertSame('found', $this->elasticsearchClient->getDocumentById('id', 'override-index'));

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
