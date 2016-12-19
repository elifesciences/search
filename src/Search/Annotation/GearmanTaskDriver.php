<?php

namespace eLife\Search\Annotation;

use Closure;
use Doctrine\Common\Annotations\Reader;
use eLife\Search\Gearman\InvalidWorkflow;
use eLife\Search\Workflow\Workflow;
use GearmanClient;
use GearmanJob;
use GearmanWorker;
use Psr\Log\LoggerInterface;
use ReflectionClass;
use Throwable;

final class GearmanTaskDriver
{
    public $tasks = [];
    private $reader;
    private $worker;
    private $logger;
    private $autoRestart;

    public function __construct(Reader $reader, GearmanWorker $worker, GearmanClient $client, LoggerInterface $logger, bool $autoRestart)
    {
        $this->reader = $reader;
        $this->worker = $worker;
        $this->client = $client;
        $this->logger = $logger;
        $this->autoRestart = $autoRestart;
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
                        $annotation->next,
                        $annotation->priority
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
                switch ($task->priority) {
                    case 'low':
                        $this->client->doLowBackground($task->next, $task->serialize($value));
                        break;
                    case 'medium':
                        $this->client->doBackground($task->next, $task->serialize($value));
                        break;
                    default:
                    case 'high':
                        $this->client->doHighBackground($task->next, $task->serialize($value));
                        break;
                }
            }

            return GEARMAN_SUCCESS;
        }, $this));
    }

    public function work(bool $restart = false)
    {
        if ($restart === false) {
            $this->logger->info('Worker started.');
        }
        $this->addTasksToWorker($this->worker);
        try {
            while ($this->worker->work());
        } catch (InvalidWorkflow $e) {
            $this->logger->warning('Recoverable error...', ['exception' => $e]);
            $this->work(true);
        } catch (Throwable $e) {
            $this->logger->critical($e->getMessage());
            if ($this->autoRestart) {
                $this->logger->warning('> Restarting worker to avoid downtime.', ['exception' => $e]);
                $this->work(true);
            }
        }
    }

    public function map(callable $fn)
    {
        return array_map($fn, $this->tasks);
    }
}
