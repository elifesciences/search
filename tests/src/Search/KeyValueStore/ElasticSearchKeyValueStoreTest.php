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

    /**
     * @test
     */
    public function allows_defaults_to_be_loaded()
    {
        $store = $this->kernel->getApp()['keyvaluestore'];
        $store->setup();
        $default = ['field' => 'value'];
        $this->assertEquals(
            $default,
            $store->load('my-id2', $default)
        );
    }
}
