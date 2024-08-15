<?php

namespace eLife\Search\Workflow;

use Assert\Assertion;
use eLife\ApiSdk\Model\Interview;
use eLife\ApiSdk\Model\Model;
use eLife\Search\Api\ApiValidator;
use eLife\Search\Api\Elasticsearch\MappedElasticsearchClient;
use eLife\Search\Api\Elasticsearch\Response\DocumentResponse;
use Psr\Log\LoggerInterface;
use Symfony\Component\Serializer\Serializer;
use Throwable;

final class InterviewWorkflow extends AbstractWorkflow
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
     * @param Interview $interview
     * @return array
     */
    public function index(Model $interview) : array
    {
        $this->logger->debug('Interview<'.$interview->getId().'> Indexing '.$interview->getTitle());

        // Normalized fields.
        $interviewObject = json_decode($this->serialize($interview));
        $interviewObject->type = 'interview';
        $interviewObject->body = $this->flattenBlocks($interviewObject->content ?? []);
        unset($interviewObject->content);
        $interviewObject->snippet = ['format' => 'json', 'value' => json_encode($this->snippet($interview))];
        // Add publish date to sort on.
        $this->addSortDate($interviewObject, $interview->getPublishedDate());

        return [
            'json' => json_encode($interviewObject),
            'id' => $interviewObject->type.'-'.$interview->getId(),
        ];
    }

    public function insert(string $json, string $id, bool $skipInsert = false)
    {
        // Insert the document.
        $this->logger->debug('Interview<'.$id.'> importing into Elasticsearch.');
        $this->client->indexJsonDocument($id, $json);

        return [
            'id' => $id,
        ];
    }

    public function postValidate(string $id, bool $skipValidate = false) : int
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
