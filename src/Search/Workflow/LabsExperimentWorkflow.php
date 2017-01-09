<?php

namespace eLife\Search\Workflow;

use Assert\Assertion;
use eLife\ApiSdk\Model\LabsExperiment;
use eLife\Search\Annotation\GearmanTask;
use eLife\Search\Api\ApiValidator;
use eLife\Search\Api\Elasticsearch\ElasticsearchClient;
use eLife\Search\Api\Elasticsearch\Response\DocumentResponse;
use eLife\Search\Api\Response\LabsExperimentResponse;
use eLife\Search\Gearman\InvalidWorkflow;
use Psr\Log\LoggerInterface;
use Symfony\Component\Serializer\Serializer;
use Throwable;

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
        // Create response to validate.
        $searchLabsExperiment = $this->validator->deserialize($this->serialize($labsExperiment), LabsExperimentResponse::class);
        // Validate that response.
        $isValid = $this->validator->validateSearchResult($searchLabsExperiment);
        if ($isValid === false) {
            $this->logger->error(
                'LabsExperiment<'.$labsExperiment->getNumber().'> cannot be transformed into a valid search result',
                [
                    'input' => [
                        'type' => 'labs-experiment',
                        'number' => $labsExperiment->getNumber(),
                    ],
                    'search_result' => $this->validator->serialize($searchLabsExperiment),
                    'validation_error' => $this->validator->getLastError()->getMessage(),
                ]
            );
            throw new InvalidWorkflow('LabsExperiment<'.$labsExperiment->getNumber().'> cannot be trasformed into a valid search result.');
        }
        // Log results.
        $this->logger->info('LabsExperiment<'.$labsExperiment->getNumber().'> validated against current schema.');
        // Pass it on.
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
        // This step is still not used very much.
        $this->logger->debug('LabsExperiment<'.$labsExperiment->getNumber().'> Indexing '.$labsExperiment->getTitle());

        return [
            'json' => $this->serialize($labsExperiment),
            'type' => 'labs-experiment',
            'id' => $labsExperiment->getNumber(),
        ];
    }

    /**
     * @GearmanTask(name="labs_experiment_insert", next="labs_experiment_post_validate", parameters={"json", "type", "id"})
     */
    public function insert(string $json, string $type, string  $id)
    {
        // Insert the document.
        $this->logger->debug('LabsExperiment<'.$id.'> importing into Elasticsearch.');
        $this->client->indexJsonDocument($type, $id, $json);

        return [
            'type' => $type,
            'id' => $id,
        ];
    }

    /**
     * @GearmanTask(name="labs_experiment_post_validate", parameters={"type", "id"})
     */
    public function postValidate(string $type, string $id)
    {
        try {
            // Post-validation, we got a document.
            $document = $this->client->getDocumentById($type, $id);
            Assertion::isInstanceOf($document, DocumentResponse::class);
            $result = $document->unwrap();
            // That document contains a blog article.
            Assertion::isInstanceOf($result, LabsExperimentResponse::class);
            // That blog article is valid JSON.
            $this->validator->validateSearchResult($result, true);
        } catch (Throwable $e) {
            $this->logger->error('LabsExperiment<'.$id.'> rolling back', [
                'exception' => $e,
            ]);
            $this->client->deleteDocument($type, $id);
            // We failed.
            return self::WORKFLOW_FAILURE;
        }

        $this->logger->info('LabsExperiment<'.$id.'> successfully imported.');

        return self::WORKFLOW_SUCCESS;
    }

    public function getSdkClass() : string
    {
        return LabsExperiment::class;
    }
}
