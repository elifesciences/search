<?php

namespace tests\eLife\Search;

use eLife\Search\Limit\CompositeLimit;
use eLife\Search\Limit\Limit;
use PHPUnit_Framework_TestCase;

class LimitTest extends PHPUnit_Framework_TestCase
{
    public function test_composite_limit_failure()
    {
        $fail = new BasicLimitMock(true);
        $pass = new BasicLimitMock();
        $limit = new CompositeLimit($fail, $pass);

        $this->assertTrue($limit());

        $this->assertEquals(['This is the reason it failed'], $limit->getReasons());
    }

    public function test_composite_limit_multiple_failures()
    {
        $fail = new BasicLimitMock(true, ['failure 1']);
        $fail2 = new BasicLimitMock(true, ['failure 2']);
        $fail3 = new BasicLimitMock(true, ['failure 3', 'failure 4']);
        $pass = new BasicLimitMock();
        $limit = new CompositeLimit($fail, $fail2, $fail3, $pass);

        $this->assertTrue($limit());

        $this->assertEquals([
            'failure 1',
            'failure 2',
            'failure 3',
            'failure 4',
        ], $limit->getReasons());
    }

    public function test_composite_limit_pass()
    {
        $pass = new BasicLimitMock();
        $pass2 = new BasicLimitMock();
        $limit = new CompositeLimit($pass, $pass2);

        $this->assertFalse($limit());

        $this->assertEquals([], $limit->getReasons());
    }
}

class BasicLimitMock implements Limit
{
    private $fail;
    private $messages;

    public function fail()
    {
        $this->fail = true;
    }

    public function __construct($fail = false, $messages = ['This is the reason it failed'])
    {
        $this->fail = $fail;
        $this->messages = $messages;
    }

    public function __invoke(): bool
    {
        return $this->fail;
    }

    public function getReasons(): array
    {
        return $this->messages;
    }
}
