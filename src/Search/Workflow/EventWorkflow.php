<?php

namespace eLife\Search\Workflow;

use eLife\ApiSdk\Model\Event;
use eLife\Search\Annotation\GearmanTask;
use Psr\Log\LoggerInterface;
use Symfony\Component\Serializer\Serializer;

final class EventWorkflow implements Workflow
{
    const WORKFLOW_SUCCESS = 1;
    const WORKFLOW_FAILURE = -1;

    /**
     * @var Serializer
     */
    private $serializer;
    private $logger;

    public function __construct(Serializer $serializer, LoggerInterface $logger)
    {
        $this->serializer = $serializer;
        $this->logger = $logger;
    }

    /**
     * @GearmanTask(
     *     name="event_validate",
     *     next="event_index",
     *     deserialize="deserializeEvent",
     *     serialize="serializeEvent"
     * )
     */
    public function validate(Event $event) : Event
    {
        $this->logger->debug('validating '.$event->getTitle());

        return $event;
    }

    /**
     * @GearmanTask(
     *     name="event_index",
     *     next="event_insert",
     *     deserialize="deserializeEvent"
     * )
     */
    public function index(Event $event) : array
    {
        $this->logger->debug('indexing '.$event->getTitle());
        $index = ['testing' => 'cheese'];

        return ['json' => $this->serializeEvent($event), 'index' => $index];
    }

    /**
     * @GearmanTask(name="event_insert", parameters={"json", "index"})
     */
    public function insert(string $json, array $index)
    {
        $this->logger->debug('inserting '.$json);
        $this->logger->debug('with index '.json_encode($index));
        $this->logger->debug('==========================================================================');

        return self::WORKFLOW_SUCCESS;
    }

    public function deserializeEvent(string $json) : Event
    {
        return $this->serializer->deserialize($json, Event::class, 'json');
    }

    public function serializeEvent(Event $event) : string
    {
        return $this->serializer->serialize($event, 'json');
    }
}
