<?php

namespace eLife\Search\Api\Response;

use JMS\Serializer\Annotation\SerializedName;
use JMS\Serializer\Annotation\Since;
use JMS\Serializer\Annotation\Type;
use Webmozart\Assert\Assert;

final class TypesResponse
{
    /**
     * @Type("integer")
     * @Since(version="1")
     */
    public $correction;

    /**
     * @Type("integer")
     * @Since(version="1")
     */
    public $editorial;

    /**
     * @Type("integer")
     * @Since(version="1")
     */
    public $feature;

    /**
     * @Type("integer")
     * @Since(version="1")
     */
    public $insight;

    /**
     * @SerializedName("research-advance")
     * @Type("integer")
     * @Since(version="1")
     */
    public $researchAdvance;

    /**
     * @SerializedName("research-article")
     * @Type("integer")
     * @Since(version="1")
     */
    public $researchArticle;

    /**
     * @SerializedName("research-exchange")
     * @Type("integer")
     * @Since(version="1")
     */
    public $researchExchange;

    /**
     * @Type("integer")
     * @Since(version="1")
     */
    public $retraction;

    /**
     * @SerializedName("registered-report")
     * @Type("integer")
     * @Since(version="1")
     */
    public $registeredReport;

    /**
     * @SerializedName("replication-study")
     * @Type("integer")
     * @Since(version="1")
     */
    public $replicationStudy;

    /**
     * @SerializedName("short-report")
     * @Type("integer")
     * @Since(version="1")
     */
    public $shortReport;

    /**
     * @SerializedName("tools-resources")
     * @Type("integer")
     * @Since(version="1")
     */
    public $toolsResources;

    /**
     * @SerializedName("blog-article")
     * @Type("integer")
     * @Since(version="1")
     */
    public $blogArticle;

    /**
     * @Type("integer")
     * @Since(version="1")
     */
    public $collection;

    /**
     * @Type("integer")
     * @Since(version="1")
     */
    public $interview;

    /**
     * @SerializedName("labs-experiment")
     * @Type("integer")
     * @Since(version="1")
     */
    public $labsExperiment;

    /**
     * @SerializedName("podcast-episode")
     * @Type("integer")
     * @Since(version="1")
     */
    public $podcastEpisode;

    private function __construct(
        $correction = 0,
        $editorial = 0,
        $feature = 0,
        $insight = 0,
        $researchAdvance = 0,
        $researchArticle = 0,
        $researchExchange = 0,
        $retraction = 0,
        $registeredReport = 0,
        $replicationStudy = 0,
        $shortReport = 0,
        $toolsResources = 0,
        $blogArticle = 0,
        $collection = 0,
        $interview = 0,
        $labsExperiment = 0,
        $podcastEpisode = 0
    ) {
        $this->correction = $correction;
        $this->editorial = $editorial;
        $this->feature = $feature;
        $this->insight = $insight;
        $this->researchAdvance = $researchAdvance;
        $this->researchArticle = $researchArticle;
        $this->researchExchange = $researchExchange;
        $this->retraction = $retraction;
        $this->registeredReport = $registeredReport;
        $this->replicationStudy = $replicationStudy;
        $this->shortReport = $shortReport;
        $this->toolsResources = $toolsResources;
        $this->blogArticle = $blogArticle;
        $this->collection = $collection;
        $this->interview = $interview;
        $this->labsExperiment = $labsExperiment;
        $this->podcastEpisode = $podcastEpisode;
    }

    public static function fromArray(array $type_totals)
    {
        return new static (
            $type_totals['correction'] ?? 0,
            $type_totals['editorial'] ?? 0,
            $type_totals['feature'] ?? 0,
            $type_totals['insight'] ?? 0,
            $type_totals['research-advance'] ?? 0,
            $type_totals['research-article'] ?? 0,
            $type_totals['research-exchange'] ?? 0,
            $type_totals['retraction'] ?? 0,
            $type_totals['registered-report'] ?? 0,
            $type_totals['replication-study'] ?? 0,
            $type_totals['short-report'] ?? 0,
            $type_totals['tools-resources'] ?? 0,
            $type_totals['blog-article'] ?? 0,
            $type_totals['collection'] ?? 0,
            $type_totals['interview'] ?? 0,
            $type_totals['labs-experiment'] ?? 0,
            $type_totals['podcast-episode'] ?? 0
        );
    }

    public static function fromList(array $list)
    {
        Assert::allIsInstanceOf($list, SearchResult::class);

        $type_totals = array_count_values(
            array_map('strtolower',
                array_map(function (SearchResult $result) {
                    return $result->getType();
                }, $list)
            )
        );

        return self::fromArray($type_totals);
    }
}
