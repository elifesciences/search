<?php

namespace eLife\Search\Workflow;

abstract class AbstractWorkflow
{
    abstract public function getSdkClass();

    abstract public function index($entity);

    abstract public function insert($json, $id);

    abstract public function postValidate($id);

    public function run($entity) {
        $result = $this->index($entity);
        $result = $this->insert($result['json'], $result['id']);
        return $this->postValidate($result['id']);
    }

}