<?php

namespace eLife\Search\Gearman;

use Closure;
use GearmanClient;
use GearmanTask;
use Generator;
use React\EventLoop\Factory;
use function React\Promise\all;
use React\Promise\Deferred;
use React\Promise\Promise;

final class GearmanSaga
{
    public $tasks = [];
    private $client;
    private $promises = [];
    private $cli = true;
    private $queue = [];
    private $loop;

    public function __construct(GearmanClient $client, $cli = true)
    {
        $this->loop = Factory::create();
        $this->cli = $cli;
        $this->client = $client;
        $tasks = &$this->tasks;
        $promises = &$this->promises;
        $client->setCompleteCallback(Closure::bind(function (GearmanTask $e) use (&$tasks, &$promises) {
            $deferred = $tasks[$e->unique()];
            unset($promises[$e->unique()]);
            unset($tasks[$e->unique()]);
            if ($deferred && $deferred instanceof Deferred) {
                $deferred->resolve($e);
            }

            return GEARMAN_SUCCESS;
        }, $this));
    }

    public function run()
    {
        do {
            // Do Do Do Do.
            while ($generator = array_shift($this->queue)) {
                $this->runSaga($generator);
            }
            // Run.
            $this->client->runTasks();
        } while (!empty($this->queue));
    }

    public function addSaga($saga, $data = null)
    {
        if (is_callable($saga)) {
            $saga = $saga($data);
        }
        $this->runSaga($saga);
    }

    public function runSaga(Generator $next)
    {
        if ($next->valid()) {
            $items = $next->current();
            if ($items instanceof GearmanBatch) {
                $this->stepThroughAll($items, $next);
            } else {
                $task = array_shift($items);
                $data = array_shift($items);
                $this->stepThrough($task, $data, $next);
            }
        }
    }

    public function addTask($task, $data) : Promise
    {
        $deferred = new Deferred();
        $id = uniqid('task_');
        $promise = $deferred->promise();
        $this->tasks[$id] = $deferred;
        $this->promises[$id] = $promise;
        $this->client->addTask($task, serialize($data), $task, $id);

        return $promise;
    }

    public function stepThroughAll(GearmanBatch $jobs, Generator &$next)
    {
        $items = [];
        foreach ($jobs->getCommands() as $job) {
            $task = array_shift($job);
            $data = array_shift($job);
            $items[] = $this->addTask($task, $data);
        }
        all($items)->then(Closure::bind(
            function ($data) use (&$next) {
                if ($next->valid()) {
                    $next->send(
                        array_map(
                            function (GearmanTask $task) {
                                return unserialize($task->data());
                            },
                            $data
                        )
                    );
                    $this->enqueue($next);
                }

                return $data;
            },
            $this)
        );
    }

    public function enqueue($saga)
    {
        $this->queue[] = $saga;
    }

    public function stepThrough(string $task, $data, Generator &$current)
    {
        $t = $this->addTask($task, $data);
        $t->then(
            Closure::bind(
                function (GearmanTask $data) use (&$current) {
                    if ($current->valid()) {
                        $current->send(unserialize($data->data()));
                        $this->enqueue($current);
                    }

                    return $data;
                },
                $this
            )
        );
    }
}
