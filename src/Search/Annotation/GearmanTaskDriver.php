<?php

namespace eLife\Search\Annotation;

use Closure;
use Doctrine\Common\Annotations\Reader;
use eLife\Logging\Monitoring;
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
    private $monitoring;
    private $limit;

    public function __construct(Reader $reader, GearmanWorker $worker, GearmanClient $client, LoggerInterface $logger, Monitoring $monitoring, callable $limit)
    {
        $this->reader = $reader;
        $this->worker = $worker;
        $this->client = $client;
        $this->logger = $logger;
        $this->monitoring = $monitoring;
        $this->limit = $limit;
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
            $this->logger->debug('GearmanTaskDriver task started', ['task' => $task->name]);
            $this->monitoring->nameTransaction('gearman:worker '.$task->name);
            $this->monitoring->startTransaction();

            try {
                $data = $task->deserialize($job->workload());
            } catch (Throwable $e) {
                $this->logger->error(
                    'Cannot deserialize a job workload',
                    [
                        'workload' => $job->workload(),
                        'sdk_class' => $task->getSdkClass(),
                        'task_name' => $task->name,
                        'exception' => $e,
                    ]
                );
                $this->monitoring->recordException($e, "Deserialization problem in $task->name");
                throw new InvalidWorkflow(
                    "Cannot deserialize a {$task->getSdkClass()}",
                    0,
                    $e
                );
            }
            try {
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
            } catch (Throwable $e) {
                $this->logger->error(
                    'Cannot perform Gearman task',
                    [
                        'exception' => $e,
                        'task' => $task->dump(),
                    ]
                );
                throw $e;
            }
            $this->logger->debug('GearmanTaskDriver task completed', ['task' => $task->name]);
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

            $this->monitoring->endTransaction();

            return GEARMAN_SUCCESS;
        }, $this));
    }

    public function work()
    {
        $this->logger->info('gearman:worker: Started listening.');
        $this->addTasksToWorker($this->worker);
        $limit = $this->limit;
        while (!$limit->hasBeenReached()) {
            try {
                $this->logger->debug('gearman:worker: Loop start, listening for jobs');
                $result = $this->worker->work();
                if (GEARMAN_TIMEOUT === $this->worker->returnCode()) {
                    $this->logger->debug('gearman:worker: No job after timeout, looping');
                    continue;
                }
                if (!$result) {
                    $this->logger->critical('gearman:worker: Uncaught failure, stopping', ['worker_error' => $this->worker->error()]);

                    return;
                }
            } catch (InvalidWorkflow $e) {
                $this->logger->warning('gearman:worker: Invalid workflow', ['exception' => $e]);
                $this->monitoring->recordException($e, 'gearman:worker: Invalid workflow');
            } catch (Throwable $e) {
                $this->logger->critical('gearman:worker: Unrecoverable error, stopping', ['exception' => $e]);
                $this->monitoring->recordException($e, 'gearman:worker: Unrecoverable error');

                return;
            }
        }
        $this->logger->info('gearman:worker: Stopped because of limits reached.');
    }

    public function map(callable $fn)
    {
        return array_map($fn, $this->tasks);
    }
}
