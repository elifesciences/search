<?php

namespace eLife\Search\Workflow;

use eLife\ApiSdk\Model\Model;
use eLife\ApiSdk\Model\ReviewedPreprint;
use eLife\Search\Api\ApiValidator;
use eLife\Search\Api\Elasticsearch\MappedElasticsearchClient;
use Psr\Log\LoggerInterface;
use Symfony\Component\Serializer\Serializer;

final class ReviewedPreprintWorkflow extends AbstractWorkflow
{
    use Blocks;
    use JsonSerializeTransport;
    use SortDate;

    /**
     * @param ReviewedPreprint $reviewedPreprint
     */
    public function prepare(Model $reviewedPreprint) : array
    {
        // Don't index if article with same id present in index.
        foreach ([
            'research-article',
            'tools-resources',
            'short-report',
            'research-advance',
        ] as $type) {
            if ($this->client->getDocumentById($type.'-'. $reviewedPreprint->getId(), null, true) !== null) {
                return ['json' => '', 'id' => $reviewedPreprint->getId(), 'skipInsert' => true];
            }
        }

        $reviewedPreprintObject = json_decode($this->serialize($reviewedPreprint));
        $reviewedPreprintObject->type = 'reviewed-preprint';
        $reviewedPreprintObject->body = $reviewedPreprint->getIndexContent() ?? '';
        $reviewedPreprintObject->snippet = ['format' => 'json', 'value' => json_encode($this->snippet($reviewedPreprint))];

        $this->addSortDate($reviewedPreprintObject, $reviewedPreprint->getStatusDate());

        return [
            'json' => json_encode($reviewedPreprintObject),
            'id' => $reviewedPreprintObject->type.'-'.$reviewedPreprint->getId(),
            'skipInsert' => false,
        ];
    }

    public function getSdkClass() : string
    {
        return ReviewedPreprint::class;
    }
}
