<?php

namespace eLife\Search\Workflow;

use eLife\ApiSdk\Model\Interview;
use eLife\Search\Annotation\GearmanTask;
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

    public function __construct(Serializer $serializer, LoggerInterface $logger)
    {
        $this->serializer = $serializer;
        $this->logger = $logger;
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
        $index = ['testing' => 'cheese'];

        return ['json' => $this->serializeInterview($interview), 'index' => $index];
    }

    /**
     * @GearmanTask(name="interview_insert", parameters={"json", "index"})
     */
    public function insert(string $json, array $index)
    {
        $this->logger->debug('inserting '.$json);
        $this->logger->debug('with index '.json_encode($index));
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
