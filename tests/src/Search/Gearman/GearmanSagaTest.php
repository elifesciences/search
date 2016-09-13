<?php

namespace tests\eLife\Search\Gearman;

use Closure;
use eLife\Search\Gearman\GearmanSaga;
use Exception;
use Mockery;
use PHPUnit_Framework_TestCase;

final class GearmanSagaTest extends PHPUnit_Framework_TestCase
{
    /** @var GearmanClientMock */
    private $mock_client;
    /** @var GearmanSaga */
    private $saga;
    private $messages = [];

    public function setUp()
    {
        $this->mock_client = new GearmanClientMock();
        $this->saga = new GearmanSaga($this->mock_client, false);
    }

    public function tearDown()
    {
        foreach ($this->messages as $message) {
            $this->fail($message);
        }
        Mockery::close();
        $this->messages = [];
    }

    public function asyncFail($message)
    {
        $this->messages[] = $message;
    }

    public function generator(callable $fn)
    {
        // Bind up.
        $callable = ($fn instanceof Closure) ? $fn->bindTo($this) : $fn;
        // Return scoped closure.
        return Closure::bind(function (...$args) use ($callable) {
            try {
                // Yield from original generator.
                yield from $callable($args);
            } catch (Exception $e) {
                // Fail on failure.
                $this->asyncFail($e->getMessage());
            }
        }, $this);
    }

    /**
     * @test
     */
    public function testCanRunSaga()
    {
        $this->mock_client->addJob('reverse', function ($data) {
            return strrev($data);
        });
        $this->mock_client->addJob('uppercase', function ($data) {
            return strtoupper($data);
        });

        $this->saga->addSaga(
            $this->generator(function () {
                $data = yield ['reverse', 'testing 1'];
                $this->assertEquals($data, '1 gnitset');

                $data = yield ['uppercase', $data];
                $this->assertEquals($data, '1 GNITSET');

                $data = yield ['reverse', $data];
                $this->assertEquals($data, 'TESTING 1');
            })
        );

        $this->saga->run();
    }
}
