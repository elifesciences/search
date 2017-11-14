<?php

namespace test\eLife\Search\KeyValueStore;

use tests\eLife\Search\Web\ElasticTestCase;

class ElasticSearchKeyValueStoreTest extends ElasticTestCase
{
    /**
     * @test
     */
    public function stores_and_load_a_json_document()
    {
        $store = $this->kernel->getApp()['keyvaluestore'];
        $store->setup();
        $store->store('my-id', $document = ['field' => 'value']);
        $this->assertEquals(
            $document,
            $store->load('my-id')
        );
    }
}
