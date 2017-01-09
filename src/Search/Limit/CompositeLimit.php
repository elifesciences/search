<?php

namespace eLife\Search\Limit;

class CompositeLimit implements Limit
{
    public $reasons = [];
    public $functions = [];

    public function __construct(Limit ...$args)
    {
        $this->functions = $args;
    }

    public function __invoke(): bool
    {
        $limitReached = false;
        foreach ($this->functions as $fn) {
            $failure = $fn();
            if ($failure) {
                $this->reasons = array_merge($this->reasons, $fn->getReasons());
                $limitReached = true;
            }
        }

        return $limitReached;
    }

    public function getReasons(): array
    {
        return $this->reasons;
    }
}
