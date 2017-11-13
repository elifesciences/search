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
        $store = $this->kernel->keyValueStore('test_key_value_store_index');
        $store->setup();
        $store->store('my-id', $document = ['field' => 'value']);
        $this->assertEquals(
            $document,
            $store->load('my_id')
        );
    }
}
