<?php

namespace eLife\Search\Gearman;

use eLife\Search\Signals;

class SignalsLimit
{
    public function __construct()
    {
        Signals::register();
    }

    public static function sigterm() : self
    {
        return new static();
    }

    public function __invoke() : bool
    {
        return !Signals::isValid();
    }
}
