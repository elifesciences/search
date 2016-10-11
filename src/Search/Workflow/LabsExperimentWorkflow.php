<?php

namespace eLife\Search\Workflow;

use eLife\ApiSdk\Model\LabsExperiment;
use eLife\Search\Annotation\GearmanTask;
use Psr\Log\LoggerInterface;
use Symfony\Component\Serializer\Serializer;

class LabsExperimentWorkflow
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
     *     name="labs_experiment_validate",
     *     next="labs_experiment_index",
     *     deserialize="deserializeLabsExperiment",
     *     serialize="serializeLabsExperiment"
     * )
     */
    public function validate(LabsExperiment $labs_experiment) : LabsExperiment
    {
        $this->logger->debug('validating '.$labs_experiment->getTitle());

        return $labs_experiment;
    }

    /**
     * @GearmanTask(
     *     name="labs_experiment_index",
     *     next="labs_experiment_insert",
     *     deserialize="deserializeLabsExperiment"
     * )
     */
    public function index(LabsExperiment $labs_experiment) : array
    {
        $this->logger->debug('indexing '.$labs_experiment->getTitle());
        $index = ['testing' => 'cheese'];

        return ['json' => $this->serializeLabsExperiment($labs_experiment), 'index' => $index];
    }

    /**
     * @GearmanTask(name="labs_experiment_insert", parameters={"json", "index"})
     */
    public function insert(string $json, array $index)
    {
        $this->logger->debug('inserting '.$json);
        $this->logger->debug('with index '.json_encode($index));
        $this->logger->debug('==========================================================================');

        return self::WORKFLOW_SUCCESS;
    }

    public function deserializeLabsExperiment(string $json) : LabsExperiment
    {
        return $this->serializer->deserialize($json, LabsExperiment::class, 'json');
    }

    public function serializeLabsExperiment(LabsExperiment $labs_experiment) : string
    {
        return $this->serializer->serialize($labs_experiment, 'json');
    }
}
