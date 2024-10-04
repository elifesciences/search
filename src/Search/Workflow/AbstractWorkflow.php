<?php

namespace eLife\Search\Workflow;

use eLife\ApiSdk\Model\Model;
use Exception;
use Psr\Log\LoggerInterface;

abstract class AbstractWorkflow
{
    protected LoggerInterface $logger;

    abstract public function getSdkClass();

    abstract public function index(Model $entity);

    abstract public function insert(string $json, string $id, bool $skipInsert);

    abstract public function postValidate(string $id, bool $skipValidate) : int;

    public function run($entity) {
        $result = $this->index($entity);
        $skipInsert = $result['skipInsert'] ?? false;
        $result = $this->insert($result['json'], $result['id'], $skipInsert);
        $skipValidate = $result['skipValidate'] ?? false;
        if (-1 === $this->postValidate($result['id'], $skipValidate)) {
            throw new Exception('post validate failed. Retrying...');
        }
    }
}
