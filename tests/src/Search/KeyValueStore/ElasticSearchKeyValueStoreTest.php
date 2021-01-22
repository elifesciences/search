<?php

namespace test\eLife\Search\KeyValueStore;

use tests\eLife\Search\Web\ElasticTestCase;

class ElasticSearchKeyValueStoreTest extends ElasticTestCase
{
    /**
     * @test
     */
    public function storesAndLoadAJsonDocument()
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
    public function allowsDefaultsToBeLoaded()
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
