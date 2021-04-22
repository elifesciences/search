<?php

namespace eLife\Search\Workflow;

use Assert\Assertion;
use eLife\ApiSdk\Model\Interview;
use eLife\Search\Annotation\GearmanTask;
use eLife\Search\Api\ApiValidator;
use eLife\Search\Api\Elasticsearch\MappedElasticsearchClient;
use eLife\Search\Api\Elasticsearch\Response\DocumentResponse;
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
        unset($interviewObject->content);
        $interviewObject->snippet = ['format' => 'json', 'value' => json_encode($this->snippet($interview))];
        // Add publish date to sort on.
        $this->addSortDate($interviewObject, $interview->getPublishedDate());

        return [
            'json' => json_encode($interviewObject),
            'id' => 'interview-'.$interview->getId(),
        ];
    }

    /**
     * @GearmanTask(name="interview_insert", next="interview_post_validate", parameters={"json", "id"})
     */
    public function insert(string $json, string $id)
    {
        // Insert the document.
        $this->logger->debug('Interview<'.$id.'> importing into Elasticsearch.');
        $this->client->indexJsonDocument($id, $json);

        return [
            'id' => $id,
        ];
    }

    /**
     * @GearmanTask(name="interview_post_validate", parameters={"id"})
     */
    public function postValidate(string $id)
    {
        try {
            // Post-validation, we got a document.
            $document = $this->client->getDocumentById($id);
            Assertion::isInstanceOf($document, DocumentResponse::class);
            $result = $document->unwrap();
            // That interview is valid JSON.
            $this->validator->validateSearchResult($result, true);
        } catch (Throwable $e) {
            $this->logger->error('Interview<'.$id.'> rolling back', [
                'exception' => $e,
            ]);
            $this->client->deleteDocument($id);

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
