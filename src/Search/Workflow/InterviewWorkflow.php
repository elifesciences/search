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
