<?php

namespace eLife\Search\Indexer\ModelIndexer;

use eLife\ApiSdk\Model\ReviewedPreprint;
use eLife\ApiSdk\Model\Model;
use eLife\Search\Api\Elasticsearch\MappedElasticsearchClient;
use eLife\Search\Indexer\ChangeSet;
use Symfony\Component\Serializer\Serializer;

final class ReviewedPreprintIndexer extends AbstractModelIndexer
{
    use Helper\TermsIndex;
    private MappedElasticsearchClient $client;

    public function __construct(Serializer $serializer, MappedElasticsearchClient $client)
    {
        parent::__construct($serializer);
        $this->client = $client;
    }

    protected function getSdkClass(): string
    {
        return ReviewedPreprint::class;
    }

    /**
     * @param ReviewedPreprint $reviewedPreprint
     * @return ChangeSet
     */
    public function prepareChangeSet(Model $reviewedPreprint) : ChangeSet
    {
        $changeSet = new ChangeSet();

        // Don't index if article with same id present in index.
        foreach ([
            'research-article',
            'tools-resources',
            'short-report',
            'research-advance',
        ] as $type) {
            if ($this->client->getDocumentById($type.'-'. $reviewedPreprint->getId(), null, true) !== null) {
                return $changeSet;
            }
        }

        $reviewedPreprintObject = json_decode($this->serialize($reviewedPreprint));
        $reviewedPreprintObject->type = 'reviewed-preprint';
        $reviewedPreprintObject->body = $reviewedPreprint->getIndexContent() ?? '';
        $reviewedPreprintObject->snippet = ['format' => 'json', 'value' => json_encode($this->snippet($reviewedPreprint))];
        
        $reviewedPreprintObject->terms = $this->termsIndexValues($reviewedPreprint);

        $this->addSortDate($reviewedPreprintObject, $reviewedPreprint->getStatusDate());

        $changeSet->addInsert(
            $reviewedPreprintObject->type.'-'.$reviewedPreprint->getId(),
            json_encode($reviewedPreprintObject),
        );
        return $changeSet;
    }
}
