<?php

namespace tests\eLife\Search;

use Closure;
use LogicException;
use function GuzzleHttp\Promise\all;

trait AsyncAssert
{
    public function asyncAssertEqual($expected, $actual)
    {
        if (!method_exists($this, 'assertEquals')) {
            throw new LogicException('This should only be run from test cases.');
        }
        all([$expected, $actual])->then(Closure::bind(function ($expected, $actual) {
            $this->assertEquals($expected, $actual);
        }, $this));
    }
}
