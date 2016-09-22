<?php

namespace eLife\Search\Workflow;

use eLife\ApiSdk\ApiSdk;
use eLife\ApiSdk\Client\BlogArticles;
use eLife\Search\Annotation\GearmanTask;
use GearmanClient;
use Iterator;
use JMS\Serializer\Serializer;

class ApiWorkflow implements Workflow
{
    private $core;
    private $sdk;
    private $counter = 0;
    private $cursor;
    private $serializer;
    private $context;
    private $client;
    private $count;

    public function __construct(string $context, ApiSdk $sdk, Serializer $serializer, GearmanClient $client)
    {
        $this->core = $sdk;
        $this->serializer = $serializer;
        $this->context = $context;
        $this->initialize();
        $this->useContext($context);
        $this->client = $client;
        $this->count = 0;
    }

    public function getTasks() : array
    {
        return [];
    }

    /**
     * @GearmanTask(name="chaining_test")
     */
    public function chainingTest($input)
    {
        echo 'STEP 1 -- DONE:'.$input.PHP_EOL;
        ++$this->count;
        $this->client->doBackground('chaining_test_2', serialize($input));
    }

    /**
     * @GearmanTask(name="chaining_test_2")
     */
    public function chainingTest2($input)
    {
        usleep(50000);
        ++$this->count;
        echo 'STEP 2 -- DONE:'.$input.PHP_EOL;
        $this->client->doHighBackground('chaining_test_3', serialize($input));
    }

    /**
     * @GearmanTask(name="chaining_test_3")
     */
    public function chainingTest3($input)
    {
        usleep(100000);
        echo 'STEP 3 -- DONE:'.$input.' — '.($this->count++).PHP_EOL;
    }

    /**
     * @GearmanTask(name="reverse")
     */
    public function reverse($s)
    {
        return strrev($s);
    }

    /**
     * @GearmanTask(name="api_init")
     */
    public function initialize()
    {
        $this->sdk = $this->cloneCore();
    }

    /**
     * @GearmanTask(name="api_close")
     */
    public function tearDown()
    {
        $this->sdk = null;
    }

    public function cloneCore() : ApiSdk
    {
        return clone $this->core;
    }

    /**
     * @GearmanTask(name="api_get_nth")
     */
    public function getNth($n)
    {
        /** @var BlogArticles $cursor */
        $cursor = $this->getCursor();
        $items = $cursor->slice($n, 1)->toArray();
        $item = current($items);

        return $this->serializer->serialize($item, 'json');
    }

    /**
     * @GearmanTask(name="api_fetch_by_id", parameters={"type", "id"})
     */
    public function getById($id)
    {
        $cursor = $this->getCursor();
        $item = $cursor->get($id)->toArray();
        // @todo DTO / Serialization
        return $this->serializer->serialize($item, 'json');
    }

    public function useContext($type)
    {
        $sdk = $this->getSdk();
        switch ($type) {
            case 'blog-articles':
            default:
                $this->cursor = $sdk->blogArticles();

                return [
                    'count' => $this->cursor->count(),
                ];
        }
    }

    public function getSdk() : ApiSdk
    {
        return $this->sdk;
    }

    /**
     * @GearmanTask(name="api_get_next")
     */
    public function getNext()
    {
        if ($this->getCursor()->valid()) {
            $current = $this->getCursor()->current();
            $this->getCursor()->next();
            $snippet = $this->getSdk()->getSerializer()->normalize($current);

            return $this->serializer->serialize($snippet, 'json');
            // @todo some sort of DTO.
//            return $current;
        }

        return false;
    }

    public function getCursor() : Iterator
    {
        return $this->cursor;
    }

    /**
     * @GearmanTask(name="counter")
     */
    public function counter()
    {
        ++$this->counter;

        return $this->counter;
    }

    /**
     * @GearmanTask(name="reset_counter")
     */
    public function resetCounter()
    {
        $this->counter = 0;

        return $this->counter;
    }
}
