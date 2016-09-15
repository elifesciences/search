<?php

namespace eLife\Search\Workflow;

use eLife\ApiSdk\ApiSdk;
use eLife\Search\Annotation\GearmanTask;
use Iterator;

class ApiWorkflow implements Workflow
{
    private $core;
    private $sdk;
    private $counter = 0;
    private $cursor;

    public function __construct(ApiSdk $sdk)
    {
        $this->core = $sdk;
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
     * @GearmanTask('api_fetch_by_id', parameters={"type", "id"})
     */
    public function getById($id)
    {
        $cursor = $this->getCursor();
        $item = $cursor->get($id);
        // @todo DTO / Serialization
        return $item;
    }

    /**
     * @GearmanTask(name="api_use_context", parameters={"type"})
     */
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
            // @todo some sort of DTO.
            return $current;
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
