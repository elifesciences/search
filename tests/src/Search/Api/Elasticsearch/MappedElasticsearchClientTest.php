<?php

namespace test\eLife\Search\Api\Elasticsearch;

use Elasticsearch\Client;
use Elasticsearch\Common\Exceptions\Missing404Exception;
use eLife\Search\Api\Elasticsearch\MappedElasticsearchClient;
use Mockery;
use PHPUnit\Framework\TestCase;

class MappedElasticsearchClientTest extends TestCase
{
    /** @var MappedElasticsearchClient */
    private $elasticsearchClient;
    private $client;

    public function setUp(): void
    {
        $this->client = Mockery::mock(Client::class);
        $this->elasticsearchClient = new MappedElasticsearchClient($this->client, 'index');
    }

    /**
     * @test
     */
    public function testGetDocumentById()
    {
        $this->client->shouldReceive('get')
            ->with([
                'index' => 'index',
                'id' => 'id',
                'client' => [],
            ])
            ->andReturn(['payload' => 'found']);

        $this->assertSame('found', $this->elasticsearchClient->getDocumentById('id'));

        $this->client->shouldReceive('get')
            ->with([
                'index' => 'override-index',
                'id' => 'id',
                'client' => [],
            ])
            ->andReturn(['payload' => 'found']);

        $this->assertSame('found', $this->elasticsearchClient->getDocumentById('id', 'override-index'));

        $this->client->shouldReceive('get')
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
