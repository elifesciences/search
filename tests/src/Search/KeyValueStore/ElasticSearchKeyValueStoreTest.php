<?php

namespace test\eLife\Search\KeyValueStore;

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use tests\eLife\Search\Web\ElasticTestCase;

#[Group('slow')]
class ElasticSearchKeyValueStoreTest extends ElasticTestCase
{
    #[Test]
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

    #[Test]
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
