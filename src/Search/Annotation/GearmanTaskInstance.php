<?php

namespace eLife\Search\Annotation;

use eLife\Search\Workflow\Workflow;

final class GearmanTaskInstance
{
    public $instance;
    public $method;
    public $name;
    public $parameters;

    public function __construct(Workflow $instance, string $method, string $name, array $parameters)
    {
        $this->instance = $instance;
        $this->method = $method;
        $this->name = $name;
        $this->parameters = $parameters;
    }
}
