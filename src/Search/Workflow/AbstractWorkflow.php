<?php

namespace eLife\Search\Workflow;

use eLife\ApiSdk\Model\Model;
use Psr\Log\LoggerInterface;

abstract class AbstractWorkflow
{
    const MAX_RETRIES = 3;

    protected LoggerInterface $logger;

    abstract public function getSdkClass();

    abstract public function index(Model $entity);

    abstract public function insert(string $json, string $id, bool $skipInsert);

    abstract public function postValidate(string $id, bool $skipValidate) : int;

    public function run($entity) {
        $skipInsert = false;
        $skipValidate = false;

        try {
            $this->withRetries(function () use ($entity, $skipInsert, $skipValidate){

                $result = $this->index($entity);
                if (isset($result['skipInsert'])) {
                    $skipInsert = $result['skipInsert'];
                }
                $result = $this->insert($result['json'], $result['id'], $skipInsert);
                if (isset($result['skipValidate'])) {
                    $skipValidate = $result['skipValidate'];
                }
                if (-1 === $this->postValidate($result['id'], $skipValidate)) {
                    throw new \Exception("post validate failed. Retrying...");
                }
            });
        } catch (\Exception $e) {
            $this->logger->error($e->getMessage(), ['entity' => $entity]);
            throw $e;
        }
    }

    private function withRetries(callable $callable) {
        $retries = 0;
        while ($retries <= self::MAX_RETRIES) {
            try {
                return $callable();
            } catch (\Exception $e) {
                $retries++;
                if ($retries > self::MAX_RETRIES) {
                    throw $e;
                }
                // do we need to sleep here or retry immediately?
                // or sleep(pow(2, $retries)) to have exponential retry
                sleep(2);
            }
        }
    }
}
