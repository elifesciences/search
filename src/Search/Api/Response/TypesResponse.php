<?php

namespace eLife\Search\Api\Response;

use Assert\Assertion;
use JMS\Serializer\Annotation\SerializedName;
use JMS\Serializer\Annotation\Since;
use JMS\Serializer\Annotation\Type;

final class TypesResponse
{
    /**
     * @Type("integer")
     * @Since(version="1")
     */
    public int $correction;

    /**
     * @Type("integer")
     * @Since(version="1")
     */
    public int $editorial;

    /**
     * @SerializedName("expression-concern")
     * @Type("integer")
     * @Since(version="1")
     */
    public int $expressionConcern;

    /**
     * @Type("integer")
     * @Since(version="1")
     */
    public int $feature;

    /**
     * @Type("integer")
     * @Since(version="1")
     */
    public int $insight;

    /**
     * @SerializedName("research-advance")
     * @Type("integer")
     * @Since(version="1")
     */
    public int $researchAdvance;

    /**
     * @SerializedName("research-article")
     * @Type("integer")
     * @Since(version="1")
     */
    public int $researchArticle;

    /**
     * @SerializedName("research-communication")
     * @Type("integer")
     * @Since(version="1")
     */
    public int $researchCommunication;

    /**
     * @Type("integer")
     * @Since(version="1")
     */
    public int $retraction;

    /**
     * @SerializedName("registered-report")
     * @Type("integer")
     * @Since(version="1")
     */
    public int $registeredReport;

    /**
     * @SerializedName("replication-study")
     * @Type("integer")
     * @Since(version="1")
     */
    public int $replicationStudy;

    /**
     * @SerializedName("review-article")
     * @Type("integer")
     * @Since(version="1")
     */
    public int $reviewArticle;

    /**
     * @SerializedName("scientific-correspondence")
     * @Type("integer")
     * @Since(version="1")
     */
    public int $scientificCorrespondence;

    /**
     * @SerializedName("short-report")
     * @Type("integer")
     * @Since(version="1")
     */
    public int $shortReport;

    /**
     * @SerializedName("tools-resources")
     * @Type("integer")
     * @Since(version="1")
     */
    public int $toolsResources;

    /**
     * @SerializedName("blog-article")
     * @Type("integer")
     * @Since(version="1")
     */
    public int $blogArticle;

    /**
     * @Type("integer")
     * @Since(version="1")
     */
    public int $collection;

    /**
     * @Type("integer")
     * @Since(version="1")
     */
    public int $interview;

    /**
     * @SerializedName("labs-post")
     * @Type("integer")
     * @Since(version="1")
     */
    public int $labsPost;

    /**
     * @SerializedName("podcast-episode")
     * @Type("integer")
     * @Since(version="1")
     */
    public int $podcastEpisode;

    /**
     * @SerializedName("reviewed-preprint")
     * @Type("integer")
     * @Since(version="1")
     */
    public int $reviewedPreprint;

    private function __construct(
        int $correction = 0,
        int $editorial = 0,
        int $feature = 0,
        int $insight = 0,
        int $researchAdvance = 0,
        int $researchArticle = 0,
        int $researchCommunication = 0,
        int $retraction = 0,
        int $registeredReport = 0,
        int $replicationStudy = 0,
        int $reviewArticle = 0,
        int $scientificCorrespondence = 0,
        int $shortReport = 0,
        int $toolsResources = 0,
        int $blogArticle = 0,
        int $collection = 0,
        int $interview = 0,
        int $labsPost = 0,
        int $podcastEpisode = 0,
        int $reviewedPreprint = 0,
        int $expressionConcern = 0
    ) {
        $this->correction = $correction;
        $this->editorial = $editorial;
        $this->feature = $feature;
        $this->insight = $insight;
        $this->researchAdvance = $researchAdvance;
        $this->researchArticle = $researchArticle;
        $this->researchCommunication = $researchCommunication;
        $this->retraction = $retraction;
        $this->registeredReport = $registeredReport;
        $this->replicationStudy = $replicationStudy;
        $this->reviewArticle = $reviewArticle;
        $this->scientificCorrespondence = $scientificCorrespondence;
        $this->shortReport = $shortReport;
        $this->toolsResources = $toolsResources;
        $this->blogArticle = $blogArticle;
        $this->collection = $collection;
        $this->interview = $interview;
        $this->labsPost = $labsPost;
        $this->podcastEpisode = $podcastEpisode;
        $this->reviewedPreprint = $reviewedPreprint;
        $this->expressionConcern = $expressionConcern;
    }

    /** @param array<string, int> $type_totals */
    public static function fromArray(array $type_totals): self
    {
        return new static (
            $type_totals['correction'] ?? 0,
            $type_totals['editorial'] ?? 0,
            $type_totals['feature'] ?? 0,
            $type_totals['insight'] ?? 0,
            $type_totals['research-advance'] ?? 0,
            $type_totals['research-article'] ?? 0,
            $type_totals['research-communication'] ?? 0,
            $type_totals['retraction'] ?? 0,
            $type_totals['registered-report'] ?? 0,
            $type_totals['replication-study'] ?? 0,
            $type_totals['review-article'] ?? 0,
            $type_totals['scientific-correspondence'] ?? 0,
            $type_totals['short-report'] ?? 0,
            $type_totals['tools-resources'] ?? 0,
            $type_totals['blog-article'] ?? 0,
            $type_totals['collection'] ?? 0,
            $type_totals['interview'] ?? 0,
            $type_totals['labs-post'] ?? 0,
            $type_totals['podcast-episode'] ?? 0,
            $type_totals['reviewed-preprint'] ?? 0,
            $type_totals['expression-concern'] ?? 0
        );
    }

    /** @param array<SearchResult> $list */
    public static function fromList(array $list): self
    {
        Assertion::allIsInstanceOf($list, SearchResult::class);

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
