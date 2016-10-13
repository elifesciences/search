<?php

namespace eLife\Search\Workflow;

use eLife\ApiSdk\Model\Interview;
use eLife\Search\Annotation\GearmanTask;
use eLife\Search\Api\Elasticsearch\ElasticsearchClient;
use Psr\Log\LoggerInterface;
use Symfony\Component\Serializer\Serializer;

final class InterviewWorkflow implements Workflow
{
    const WORKFLOW_SUCCESS = 1;
    const WORKFLOW_FAILURE = -1;

    /**
     * @var Serializer
     */
    private $serializer;
    private $logger;
    private $client;

    public function __construct(Serializer $serializer, LoggerInterface $logger, ElasticsearchClient $client)
    {
        $this->serializer = $serializer;
        $this->logger = $logger;
        $this->client = $client;
    }

    /**
     * @GearmanTask(
     *     name="interview_validate",
     *     next="interview_index",
     *     deserialize="deserializeInterview",
     *     serialize="serializeInterview"
     * )
     */
    public function validate(Interview $interview) : Interview
    {
        $this->logger->debug('validating '.$interview->getTitle());

        return $interview;
    }

    /**
     * @GearmanTask(
     *     name="interview_index",
     *     next="interview_insert",
     *     deserialize="deserializeInterview"
     * )
     */
    public function index(Interview $interview) : array
    {
        $this->logger->debug('indexing '.$interview->getTitle());

        return [
            'json' => $this->serializeInterview($interview),
            'type' => 'interview',
            'id' => $interview->getId(),
        ];
    }

    /**
     * @GearmanTask(name="interview_insert", parameters={"json", "type", "id"})
     */
    public function insert(string $json, string $type, string $id)
    {
        $this->logger->debug('inserting '.$json);
        $this->logger->debug('with type: `'.$type.'` and id: `'.$id.'`');
        $this->client->indexJsonDocument($type, $id, $json);
        $this->logger->debug('==========================================================================');

        return self::WORKFLOW_SUCCESS;
    }

    public function deserializeInterview(string $json) : Interview
    {
        return $this->serializer->deserialize($json, Interview::class, 'json');
    }

    public function serializeInterview(Interview $interview) : string
    {
        return $this->serializer->serialize($interview, 'json');
    }
}
