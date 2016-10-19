<?php

namespace eLife\Search\Workflow;

use eLife\ApiSdk\Model\LabsExperiment;
use eLife\Search\Annotation\GearmanTask;
use eLife\Search\Api\ApiValidator;
use eLife\Search\Api\Elasticsearch\ElasticsearchClient;
use Psr\Log\LoggerInterface;
use Symfony\Component\Serializer\Serializer;

final class LabsExperimentWorkflow implements Workflow
{
    const WORKFLOW_SUCCESS = 1;
    const WORKFLOW_FAILURE = -1;

    use JsonSerializeTransport;

    /**
     * @var Serializer
     */
    private $serializer;
    private $logger;
    private $client;
    private $validator;

    public function __construct(Serializer $serializer, LoggerInterface $logger, ElasticsearchClient $client, ApiValidator $validator)
    {
        $this->serializer = $serializer;
        $this->client = $client;
        $this->logger = $logger;
        $this->validator = $validator;
    }

    /**
     * @GearmanTask(
     *     name="labs_experiment_validate",
     *     next="labs_experiment_index",
     *     deserialize="deserialize",
     *     serialize="serialize"
     * )
     */
    public function validate(LabsExperiment $labsExperiment) : LabsExperiment
    {
        $this->logger->debug('validating '.$labsExperiment->getTitle());

        return $labsExperiment;
    }

    /**
     * @GearmanTask(
     *     name="labs_experiment_index",
     *     next="labs_experiment_insert",
     *     deserialize="deserialize"
     * )
     */
    public function index(LabsExperiment $labsExperiment) : array
    {
        $this->logger->debug('indexing '.$labsExperiment->getTitle());

        return [
            'json' => $this->serialize($labsExperiment),
            'type' => 'labs-experiment',
            'id' => $labsExperiment->getNumber(),
        ];
    }

    /**
     * @GearmanTask(name="labs_experiment_insert", parameters={"json", "type", "id"})
     */
    public function insert(string $json, string $type, string  $id)
    {
        $this->client->indexJsonDocument($type, $id, $json);

        return self::WORKFLOW_SUCCESS;
    }

    public function getSdkClass() : string
    {
        return LabsExperiment::class;
    }
}
