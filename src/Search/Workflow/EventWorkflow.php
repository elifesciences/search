<?php

namespace eLife\Search\Workflow;

use eLife\ApiSdk\Model\Event;
use eLife\Search\Annotation\GearmanTask;
use eLife\Search\Api\Elasticsearch\ElasticsearchClient;
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
    private $client;

    public function __construct(
        Serializer $serializer,
        LoggerInterface $logger,
        ElasticsearchClient $client
    ) {
        $this->serializer = $serializer;
        $this->logger = $logger;
        $this->client = $client;
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

        return [
            'json' => $this->serializeEvent($event),
            'type' => 'event',
            'id' => $event->getId(),
        ];
    }

    /**
     * @GearmanTask(name="event_insert", parameters={"json", "type", "id"})
     */
    public function insert(string $json, string $type, string $id)
    {
        $this->logger->debug('inserting '.$json);
        $this->logger->debug('with type: `'.$type.'` and id: `'.$id.'`');
        $this->client->indexJsonDocument($type, $id, $json);
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
