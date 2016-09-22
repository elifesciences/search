<?php

namespace eLife\Search\Annotation;

use Closure;
use Doctrine\Common\Annotations\Reader;
use eLife\Search\Workflow\Workflow;
use GearmanJob;
use GearmanWorker;
use ReflectionClass;

final class GearmanTaskDriver
{
    public $tasks = [];
    private $reader;
    private $worker;

    public function __construct(Reader $reader, GearmanWorker $worker = null)
    {
        $this->reader = $reader;
        $this->worker = $worker;
    }

    public function registerWorkflow(Workflow $workflow)
    {
        $blog = new ReflectionClass(get_class($workflow));
        foreach ($blog->getMethods() as $method) {
            foreach ($this->reader->getMethodAnnotations($method) as $annotation) {
                if ($annotation instanceof GearmanTask) {
                    $this->tasks[] = new GearmanTaskInstance($workflow, $method->getName(), $annotation->name, $annotation->parameters);
                }
            }
        }
        foreach ($workflow->getTasks() as $name => $task) {
            $this->tasks[] = new GearmanTaskInstance($workflow, $task, $name, []);
        }
    }

    public function addTasksToWorker(GearmanWorker $worker)
    {
        foreach ($this->tasks as $task) {
            $this->addTaskToWorker($task, $worker);
        }
    }

    public function addTaskToWorker(GearmanTaskInstance $task, GearmanWorker $worker)
    {
        $worker->addFunction($task->name, Closure::bind(function (GearmanJob $job) use ($task) {
            $data = unserialize($job->workload());
            $object = $task->instance;
            $method = $task->method;
            $params = [];
            if ($task->parameters) {
                foreach ($task->parameters as $param) {
                    $params[] = $data[$param] ?? null;
                }
                $value = $object->{$method}(...$params);
            } else {
                $value = $object->{$method}($data);
            }

            $job->sendStatus(10, 10);

            return GEARMAN_SUCCESS;
        }, $this));
    }

    public function work()
    {
        echo 'Worked started V3: '.PHP_EOL;
        $this->addTasksToWorker($this->worker);
        while ($this->worker->work());
    }

    public function map(callable $fn)
    {
        return array_map($fn, $this->tasks);
    }
}
