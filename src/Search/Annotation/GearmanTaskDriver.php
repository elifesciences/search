<?php

namespace eLife\Search\Annotation;

use Closure;
use Doctrine\Common\Annotations\Reader;
use eLife\Search\Workflow\Workflow;
use GearmanClient;
use GearmanJob;
use GearmanWorker;
use ReflectionClass;

final class GearmanTaskDriver
{
    public $tasks = [];
    private $reader;
    private $worker;

    public function __construct(Reader $reader, GearmanWorker $worker = null, GearmanClient $client)
    {
        $this->reader = $reader;
        $this->worker = $worker;
        $this->client = $client;
    }

    public function registerWorkflow(Workflow $workflow)
    {
        $blog = new ReflectionClass(get_class($workflow));
        foreach ($blog->getMethods() as $method) {
            foreach ($this->reader->getMethodAnnotations($method) as $annotation) {
                if ($annotation instanceof GearmanTask) {
                    $this->tasks[] = new GearmanTaskInstance(
                        $workflow,
                        $method->getName(),
                        $annotation->name,
                        $annotation->parameters,
                        $annotation->serialize ? [$workflow, $annotation->serialize] : null,
                        $annotation->deserialize ? [$workflow, $annotation->deserialize] : null,
                        $annotation->next
                    );
                }
            }
        }
        if (method_exists($workflow, 'getTasks')) {
            foreach ($workflow->getTasks() as $name => $task) {
                $this->tasks[] = new GearmanTaskInstance(
                    $workflow,
                    $task,
                    $name
                );
            }
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
            $data = $task->deserialize($job->workload());
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
            if ($task->next) {
                $this->client->doHighBackground($task->next, $task->serialize($value));
            }

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
