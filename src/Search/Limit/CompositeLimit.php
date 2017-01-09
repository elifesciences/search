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
        $failures = array_filter($this->functions, function (Limit $fn) {
            $failure = $fn();
            if ($failure) {
                $this->reasons = array_merge($this->reasons, $fn->getReasons());
            }

            return $failure === true;
        });

        return empty($failures) === false;
    }

    public function getReasons(): array
    {
        return $this->reasons;
    }
}
