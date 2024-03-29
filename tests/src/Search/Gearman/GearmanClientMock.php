<?php

namespace tests\eLife\Search\Gearman;

use GearmanClient;
use GearmanTask;
use Mockery;

final class GearmanClientMock extends GearmanClient
{
    private $callback;
    private $tasks = [];
    public $jobs = [];

    public function addJob(string $name, callable $fn)
    {
        $this->jobs[$name] = function ($data) use ($fn) {
            return $fn(unserialize($data));
        };
    }

    public function setCompleteCallback($callback): bool
    {
        $this->callback = $callback;
        return true;
    }

    public function addTask($function_name, $workload, $context = null, $unique = null)
    {
        $job = $this->jobs[$function_name] ?? function ($_) {
            return $_;
        };
        $task = Mockery::mock(GearmanTask::class);
        $task->shouldReceive('unique')->andReturn($unique);
        $task->shouldReceive('data')->andReturn(serialize($job($workload)));
        $this->tasks[] = $task;
    }

    public function runTasks($data = []): bool
    {
        $fn = $this->callback;
        while (!empty($this->tasks)) {
            $task = array_shift($this->tasks);
            $fn($task);
        }
        return true;
    }
}
