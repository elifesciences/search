<?php

namespace eLife\Search\Workflow;

use Assert\Assertion;
use eLife\ApiSdk\Model\Event;
use eLife\Search\Annotation\GearmanTask;
use eLife\Search\Api\ApiValidator;
use eLife\Search\Api\Elasticsearch\ElasticsearchClient;
use eLife\Search\Api\Elasticsearch\Response\DocumentResponse;
use eLife\Search\Api\Response\EventResponse;
use eLife\Search\Gearman\InvalidWorkflow;
use Psr\Log\LoggerInterface;
use Symfony\Component\Serializer\Serializer;
use Throwable;

final class EventWorkflow implements Workflow
{
    use JsonSerializeTransport;

    const WORKFLOW_SUCCESS = 1;
    const WORKFLOW_FAILURE = -1;

    /**
     * @var Serializer
     */
    private $serializer;
    private $logger;
    private $client;
    private $validator;

    public function __construct(
        Serializer $serializer,
        LoggerInterface $logger,
        ElasticsearchClient $client,
        ApiValidator $validator
    ) {
        $this->serializer = $serializer;
        $this->logger = $logger;
        $this->client = $client;
        $this->validator = $validator;
    }

    /**
     * @GearmanTask(
     *     name="event_validate",
     *     next="event_index",
     *     deserialize="deserialize",
     *     serialize="serialize"
     * )
     */
    public function validate(Event $event) : Event
    {
        // Create response to validate.
        $searchEvent = $this->validator->deserialize($this->serialize($event), EventResponse::class);
        // Validate that response.
        if ($this->validator->validateSearchResult($searchEvent) === false) {
            $this->logger->alert($this->validator->getLastError()->getMessage());
            throw new InvalidWorkflow('Event<'.$event->getId().'> Invalid item tried to be imported.');
        }
        // Log results.
        $this->logger->info('Event<'.$event->getId().'> validated against current schema.');
        // Pass it on.
        return $event;
    }

    /**
     * @GearmanTask(
     *     name="event_index",
     *     next="event_insert",
     *     deserialize="deserialize"
     * )
     */
    public function index(Event $event) : array
    {
        $this->logger->debug('Event<'.$event->getId().'> indexing with title '.$event->getTitle());

        return [
            'json' => $this->serialize($event),
            'type' => 'event',
            'id' => $event->getId(),
        ];
    }

    /**
     * @GearmanTask(
     *     name="event_insert",
     *     next="event_post_validate",
     *     parameters={"json", "type", "id"}
     * )
     */
    public function insert(string $json, string $type, string $id)
    {
        // Insert the document.
        $this->logger->debug('Event<'.$id.'> importing into Elasticsearch.');
        $this->client->indexJsonDocument($type, $id, $json);

        return [
            'type' => $type,
            'id' => $id,
        ];
    }
    /**
     * @GearmanTask(
     *     name="event_post_validate",
     *     parameters={"type", "id"}
     * )
     */
    public function postValidate(string $type, string $id)
    {
        try {
            // Post-validation, we got a document.
            $document = $this->client->getDocumentById($type, $id);
            Assertion::isInstanceOf($document, DocumentResponse::class);
            $result = $document->unwrap();
            // That document contains an event response.
            Assertion::isInstanceOf($result, EventResponse::class);
            // That blog article is valid JSON.
            $this->validator->validateSearchResult($result, true);
        } catch (Throwable $e) {
            $this->logger->alert('Event<'.$id.'> Rolling back...', [
                'message' => $e->getMessage(),
                'exception' => $e,
            ]);
            $this->client->deleteDocument($type, $id);

            return self::WORKFLOW_FAILURE;
        }
        $this->logger->info('Event<'.$id.'> successfully imported.');

        return self::WORKFLOW_SUCCESS;
    }

    public function getSdkClass() : string
    {
        return Event::class;
    }
}
