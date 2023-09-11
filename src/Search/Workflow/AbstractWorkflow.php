<?php

namespace eLife\Search\Workflow;

use eLife\ApiSdk\Model\Model;

abstract class AbstractWorkflow
{
    abstract public function getSdkClass();

    abstract public function index(Model $entity);

    abstract public function insert(string $json, string $id, bool $skipInsert);

    abstract public function postValidate(string $id, bool $skipValidate) : int;

    public function run($entity) {
        $skipInsert = false;
        $skipValidate = false;

        $result = $this->index($entity);
        if (isset($result['skipInsert'])) {
            $skipInsert = $result['skipInsert'];
        }
        $result = $this->insert($result['json'], $result['id'], $skipInsert);
        if (isset($result['skipValidate'])) {
            $skipValidate = $result['skipValidate'];
        }
        return $this->postValidate($result['id'], $skipValidate);
    }
}
