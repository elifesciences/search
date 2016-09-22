<?php

namespace eLife\Search\Annotation;

/**
 * @Annotation
 */
final class GearmanTask
{
    public $parameters = [];
    public $name;
    public $next;
    public $serialize;
    public $deserialize;
}
