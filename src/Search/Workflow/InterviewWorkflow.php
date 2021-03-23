<?php

namespace eLife\Search\Workflow;

use Assert\Assertion;
use eLife\ApiSdk\Model\Interview;
use eLife\Search\Annotation\GearmanTask;
use eLife\Search\Api\ApiValidator;
use eLife\Search\Api\Elasticsearch\MappedElasticsearchClient;
use eLife\Search\Api\Elasticsearch\Response\DocumentResponse;
use eLife\Search\Api\Response\InterviewResponse;
use eLife\Search\Gearman\InvalidWorkflow;
use Psr\Log\LoggerInterface;
use Symfony\Component\Serializer\Serializer;
use Throwable;

final class InterviewWorkflow implements Workflow
{
    use Blocks;
    use JsonSerializeTransport;
    use SortDate;

    const WORKFLOW_SUCCESS = 1;
    const WORKFLOW_FAILURE = -1;

    /**
     * @var Serializer
     */
    private $serializer;
    private $logger;
    private $client;
    private $validator;

    public function __construct(Serializer $serializer, LoggerInterface $logger, MappedElasticsearchClient $client, ApiValidator $validator)
    {
        $this->serializer = $serializer;
        $this->logger = $logger;
        $this->client = $client;
        $this->validator = $validator;
    }

    /**
     * @GearmanTask(
     *     name="interview_validate",
     *     next="interview_index",
     *     deserialize="deserialize",
     *     serialize="serialize"
     * )
     */
    public function validate(Interview $interview) : Interview
    {
        // Create response to validate
        $searchInterview = $this->validator->deserialize($this->serialize($interview), InterviewResponse::class);
        // Validate that response.
        $isValid = $this->validator->validateSearchResult($searchInterview);
        if (false === $isValid) {
            $this->logger->error(
                'Interview<'.$interview->getId().'> cannot be transformed into a valid search result',
                [
                    'input' => [
                        'type' => 'interview',
                        'id' => $interview->getId(),
                    ],
                    'search_result' => $this->validator->serialize($searchInterview),
                    'validation_error' => $this->validator->getLastError()->getMessage(),
                ]
            );
            throw new InvalidWorkflow('Interview<'.$interview->getId().'> cannot be trasformed into a valid search result.');
        }
        // Log results.
        $this->logger->info('Interview<'.$interview->getId().'> validated against current schema.');

        // Pass it on.
        return $interview;
    }

    /**
     * @GearmanTask(
     *     name="interview_index",
     *     next="interview_insert",
     *     deserialize="deserialize"
     * )
     */
    public function index(Interview $interview) : array
    {
        $this->logger->debug('Interview<'.$interview->getId().'> Indexing '.$interview->getTitle());

        // Normalized fields.
        $interviewObject = json_decode($this->serialize($interview));
        $interviewObject->body = $this->flattenBlocks($interviewObject->content ?? []);
        $interviewObject->snippet = $this->snippet($interview);
        unset($interviewObject->content);
        // Add publish date to sort on.
        $this->addSortDate($interviewObject, $interview->getPublishedDate());

        return [
            'json' => json_encode($interviewObject),
            'type' => 'interview',
            'id' => $interview->getId(),
        ];
    }

    /**
     * @GearmanTask(name="interview_insert", next="interview_post_validate", parameters={"json", "type", "id"})
     */
    public function insert(string $json, string $type, string $id)
    {
        // Insert the document.
        $this->logger->debug('Interview<'.$id.'> importing into Elasticsearch.');
        $this->client->indexJsonDocument($type, $id, $json);

        return [
            'type' => $type,
            'id' => $id,
        ];
    }

    /**
     * @GearmanTask(name="interview_post_validate", parameters={"type", "id"})
     */
    public function postValidate(string $type, string $id)
    {
        try {
            // Post-validation, we got a document.
            $document = $this->client->getDocumentById($type, $id);
            Assertion::isInstanceOf($document, DocumentResponse::class);
            $result = $document->unwrap();
            // That document contains a blog article.
            Assertion::isInstanceOf($result, InterviewResponse::class);
            // That blog article is valid JSON.
            $this->validator->validateSearchResult($result, true);
        } catch (Throwable $e) {
            $this->logger->error('Interview<'.$id.'> rolling back', [
                'exception' => $e,
            ]);
            $this->client->deleteDocument($type, $id);

            // We failed.
            return self::WORKFLOW_FAILURE;
        }

        $this->logger->info('Interview<'.$id.'> successfully imported.');

        return self::WORKFLOW_SUCCESS;
    }

    public function deserialize(string $json) : Interview
    {
        return $this->serializer->deserialize($json, Interview::class, 'json');
    }

    public function serialize(Interview $interview) : string
    {
        return $this->serializer->serialize($interview, 'json');
    }

    public function getSdkClass() : string
    {
        return Interview::class;
    }
}
