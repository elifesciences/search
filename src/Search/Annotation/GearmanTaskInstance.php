<?php

namespace eLife\Search\Annotation;

use eLife\Search\Workflow\Workflow;

final class GearmanTaskInstance
{
    public $instance;
    public $method;
    public $name;
    public $parameters;
    public $serialize;
    public $deserialize;
    public $next;
    public $priority;

    public function __construct(
        Workflow $instance,
        string $method,
        string $name,
        array $parameters = [],
        callable $serialize = null,
        callable $deserialize = null,
        string $next = null,
        string $priority = null
    ) {
        $this->instance = $instance;
        $this->method = $method;
        $this->name = $name;
        $this->parameters = $parameters;
        $this->serialize = $serialize;
        $this->deserialize = $deserialize;
        $this->next = $next;
        $this->priority = $priority;
    }

    public function serialize($data)
    {
        $serialize = $this->serialize;
        if ($serialize === null) {
            $serialize = function ($data) {
                return serialize($data);
            };
        }

        return $serialize($data);
    }

    public function deserialize($data)
    {
        $deserialize = $this->deserialize;
        if ($deserialize === null) {
            $deserialize = function ($data) {
                return unserialize($data);
            };
        }

        return $deserialize($data);
    }

    public function getSdkClass() : string
    {
        return $this->instance->getSdkClass();
    }

    public function dump() : array
    {
        return [
            'instance' => $this->instance,
            'method' => $this->method,
            'name' => $this->name,
            'parameters' => $this->parameters,
        ];
    }
}
