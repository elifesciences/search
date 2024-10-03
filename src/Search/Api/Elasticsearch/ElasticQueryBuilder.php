<?php

namespace eLife\Search\Api\Elasticsearch;

use DateTimeImmutable;
use eLife\Search\Api\Query\QueryBuilder;

final class ElasticQueryBuilder implements QueryBuilder
{
    private $order;

    const PHP_DATETIME_FORMAT = 'yyyy/MM/dd HH:mm:ss';
    const ELASTIC_DATETIME_FORMAT = 'Y/m/d H:i:s';

    const DATE_DEFAULT = 'sortDate';
    const DATE_PUBLISHED = 'published';

    const MAXIMUM_SUBJECTS = 100;
    const MAXIMUM_TYPES = 18;

    const WORD_LIMIT = 32;

    private $dateType;

    public function __construct(string $index)
    {
        $this->query['index'] = $index;
        $this->query['body']['track_total_hits'] = true;
        $this->query['body']['aggs']['type_agg']['terms'] = [
            'field' => 'type',
            'min_doc_count' => 0,
            'size' => self::MAXIMUM_TYPES,
        ];
        $this->query['body']['aggs']['subject_agg'] = [
            'nested' => [
                'path' => 'subjects',
            ],
            'aggs' => [
                'name' => [
                    'terms' => [
                        'field' => 'subjects.id',
                        'size' => self::MAXIMUM_SUBJECTS,
                        'min_doc_count' => 0,
                    ],
                    'aggs' => [
                        'name' => [
                            'terms' => [
                                'field' => 'subjects.name.keyword',
                            ],
                        ],
                    ],
                ],
            ],
        ];
    }

    private $query = [];

    private function sort($sort = [])
    {
        $this->query['body']['sort'] = $this->query['body']['sort'] ?? [];
        $this->query['body']['sort'][] = $sort;
    }

    private function getSort($reverse = false)
    {
        if ($reverse) {
            return 'desc' === $this->order ? 'asc' : 'desc';
        }

        return $this->order;
    }

    public function setDateType(string $field) : QueryBuilder
    {
        // No need to do any fancy enum checks.
        if (self::DATE_PUBLISHED === $field) {
            $this->dateType = $field;

            return $this;
        }
        // Defaults to.. the default. (sortDate)
        $this->dateType = self::DATE_DEFAULT;

        return $this;
    }

    private function postFilter($filter)
    {
        $this->query['body']['post_filter']['bool']['filter'][] = $filter;
    }

    private function query($key, array $body)
    {
        $this->query['body']['query'] = $this->query['body']['query'] ?? [];
        $this->query['body']['query'][$key] = $body;
    }

    private function setBoostings(array $query = [])
    {
        /* Boost results based on 'type' */
        $this->query['body']['query']['bool']['should'][] = ['constant_score' => ['boost' => 2, 'filter' => ['query_string' => ['query' => 'expression-concern', 'fields' => ['type']]]]];
        $this->query['body']['query']['bool']['should'][] = ['constant_score' => ['boost' => 2, 'filter' => ['query_string' => ['query' => 'correction', 'fields' => ['type']]]]];
        $this->query['body']['query']['bool']['should'][] = ['constant_score' => ['boost' => 2, 'filter' => ['query_string' => ['query' => 'podcast-episode', 'fields' => ['type']]]]];
        $this->query['body']['query']['bool']['should'][] = ['constant_score' => ['boost' => 2, 'filter' => ['query_string' => ['query' => 'retraction', 'fields' => ['type']]]]];

        $this->query['body']['query']['bool']['should'][] = ['constant_score' => ['boost' => 1, 'filter' => ['query_string' => ['query' => 'collection', 'fields' => ['type']]]]];
        $this->query['body']['query']['bool']['should'][] = ['constant_score' => ['boost' => 1, 'filter' => ['query_string' => ['query' => 'editorial', 'fields' => ['type']]]]];
        $this->query['body']['query']['bool']['should'][] = ['constant_score' => ['boost' => 1, 'filter' => ['query_string' => ['query' => 'insight', 'fields' => ['type']]]]];
        $this->query['body']['query']['bool']['should'][] = ['constant_score' => ['boost' => 1, 'filter' => ['query_string' => ['query' => 'interview', 'fields' => ['type']]]]];
        $this->query['body']['query']['bool']['should'][] = ['constant_score' => ['boost' => 1, 'filter' => ['query_string' => ['query' => 'feature', 'fields' => ['type']]]]];
        $this->query['body']['query']['bool']['should'][] = ['constant_score' => ['boost' => 1, 'filter' => ['query_string' => ['query' => 'labs-post', 'fields' => ['type']]]]];

        $this->query['body']['query']['bool']['should'][] = ['constant_score' => ['boost' => 0, 'filter' => ['query_string' => ['query' => 'blog-article', 'fields' => ['type']]]]];
        $this->query['body']['query']['bool']['should'][] = ['constant_score' => ['boost' => 0, 'filter' => ['query_string' => ['query' => 'registered-report', 'fields' => ['type']]]]];
        $this->query['body']['query']['bool']['should'][] = ['constant_score' => ['boost' => 0, 'filter' => ['query_string' => ['query' => 'replication-study', 'fields' => ['type']]]]];
        $this->query['body']['query']['bool']['should'][] = ['constant_score' => ['boost' => 0, 'filter' => ['query_string' => ['query' => 'research-advance', 'fields' => ['type']]]]];
        $this->query['body']['query']['bool']['should'][] = ['constant_score' => ['boost' => 0, 'filter' => ['query_string' => ['query' => 'research-article', 'fields' => ['type']]]]];
        $this->query['body']['query']['bool']['should'][] = ['constant_score' => ['boost' => 0, 'filter' => ['query_string' => ['query' => 'research-communication', 'fields' => ['type']]]]];
        $this->query['body']['query']['bool']['should'][] = ['constant_score' => ['boost' => 0, 'filter' => ['query_string' => ['query' => 'review-article', 'fields' => ['type']]]]];
        $this->query['body']['query']['bool']['should'][] = ['constant_score' => ['boost' => 0, 'filter' => ['query_string' => ['query' => 'scientific-correspondence', 'fields' => ['type']]]]];
        $this->query['body']['query']['bool']['should'][] = ['constant_score' => ['boost' => 0, 'filter' => ['query_string' => ['query' => 'short-report', 'fields' => ['type']]]]];
        $this->query['body']['query']['bool']['should'][] = ['constant_score' => ['boost' => 0, 'filter' => ['query_string' => ['query' => 'tools-resources', 'fields' => ['type']]]]];

        if (!(empty($query))) {
            /* Boost results based on which field(s) match the query term */
            // Add to mappings: authorLine, authors, curators, reviewers, digest, doi & elocationId

            // Consider removing from mappings: additionalFiles, chapters, content, ethics, figuresPdf, image,
            // interviewee, issue, number, pdf, podcastEpisodes, references, relatedContent.sources, stage, status,
            // titlePrefix, version, versionsDate, volume & xml
            $this->query['body']['query']['bool']['should'][] = ['constant_score' => ['boost' => 10, 'filter' => ['query_string' => $query + ['fields' => ['authorLine']]]]];
            $this->query['body']['query']['bool']['should'][] = ['constant_score' => ['boost' => 10, 'filter' => ['query_string' => $query + ['fields' => ['authors.affiliations.name']]]]];
            $this->query['body']['query']['bool']['should'][] = ['constant_score' => ['boost' => 10, 'filter' => ['query_string' => $query + ['fields' => ['authors.name.preferred']]]]];
            $this->query['body']['query']['bool']['should'][] = ['constant_score' => ['boost' => 10, 'filter' => ['query_string' => $query + ['fields' => ['authors.name.value']]]]];
            $this->query['body']['query']['bool']['should'][] = ['constant_score' => ['boost' => 10, 'filter' => ['query_string' => $query + ['fields' => ['authors.onBehalfOf']]]]];
            $this->query['body']['query']['bool']['should'][] = ['constant_score' => ['boost' => 10, 'filter' => ['query_string' => $query + ['fields' => ['authors.people.name.preferred']]]]];

            $this->query['body']['query']['bool']['should'][] = ['constant_score' => ['boost' => 9, 'filter' => ['query_string' => $query + ['fields' => ['title']]]]];

            $this->query['body']['query']['bool']['should'][] = ['constant_score' => ['boost' => 8, 'filter' => ['query_string' => $query + ['fields' => ['curators.name.preferred']]]]];
            $this->query['body']['query']['bool']['should'][] = ['constant_score' => ['boost' => 8, 'filter' => ['query_string' => $query + ['fields' => ['curators.orcid']]]]];
            $this->query['body']['query']['bool']['should'][] = ['constant_score' => ['boost' => 8, 'filter' => ['query_string' => $query + ['fields' => ['dataSets.value']]]]];
            $this->query['body']['query']['bool']['should'][] = ['constant_score' => ['boost' => 8, 'filter' => ['query_string' => $query + ['fields' => ['reviewers.name.preferred']]]]];

            $this->query['body']['query']['bool']['should'][] = ['constant_score' => ['boost' => 6, 'filter' => ['query_string' => $query + ['fields' => ['funding.value']]]]];

            $this->query['body']['query']['bool']['should'][] = ['constant_score' => ['boost' => 4, 'filter' => ['query_string' => $query + ['fields' => ['keywords']]]]];
            $this->query['body']['query']['bool']['should'][] = ['constant_score' => ['boost' => 4, 'filter' => ['query_string' => $query + ['fields' => ['researchOrganisms']]]]];
            $this->query['body']['query']['bool']['should'][] = ['constant_score' => ['boost' => 4, 'filter' => ['query_string' => $query + ['fields' => ['subjects.name']]]]];

            $this->query['body']['query']['bool']['should'][] = ['constant_score' => ['boost' => 3, 'filter' => ['query_string' => $query + ['fields' => ['abstract']]]]];
            $this->query['body']['query']['bool']['should'][] = ['constant_score' => ['boost' => 3, 'filter' => ['query_string' => $query + ['fields' => ['digest']]]]];
            $this->query['body']['query']['bool']['should'][] = ['constant_score' => ['boost' => 3, 'filter' => ['query_string' => $query + ['fields' => ['impactStatement']]]]];

            $this->query['body']['query']['bool']['should'][] = ['constant_score' => ['boost' => 2, 'filter' => ['query_string' => $query + ['fields' => ['appendices']]]]];
            $this->query['body']['query']['bool']['should'][] = ['constant_score' => ['boost' => 2, 'filter' => ['query_string' => $query + ['fields' => ['authorResponse']]]]];
            $this->query['body']['query']['bool']['should'][] = ['constant_score' => ['boost' => 2, 'filter' => ['query_string' => $query + ['fields' => ['authors.orcid']]]]];
            $this->query['body']['query']['bool']['should'][] = ['constant_score' => ['boost' => 2, 'filter' => ['query_string' => $query + ['fields' => ['body']]]]];
            $this->query['body']['query']['bool']['should'][] = ['constant_score' => ['boost' => 2, 'filter' => ['query_string' => $query + ['fields' => ['decisionLetter']]]]];
            $this->query['body']['query']['bool']['should'][] = ['constant_score' => ['boost' => 2, 'filter' => ['query_string' => $query + ['fields' => ['doi']]]]];
            $this->query['body']['query']['bool']['should'][] = ['constant_score' => ['boost' => 2, 'filter' => ['query_string' => $query + ['fields' => ['elocationId']]]]];
            $this->query['body']['query']['bool']['should'][] = ['constant_score' => ['boost' => 2, 'filter' => ['query_string' => $query + ['fields' => ['id']]]]];
        }
    }

    public function searchFor(string $string) : QueryBuilder
    {
        if ('' !== $string) {
            /* Query all fields for the actual query term*/
            $query = [
                'query' => $this->escapeReservedChars($string).'~',
                'default_operator' => 'AND',
            ];

            $this->query['body']['query']['bool']['must'][] = ['query_string' => $query];

            $this->setBoostings($query);
        }

        return $this;
    }

    private function escapeReservedChars(string $string) : string
    {
        // See: https://github.com/elastic/elasticsearch-php/issues/620#issuecomment-901727162
        return preg_replace(
            [
                '_[<>]+_',
                '_[-+=!(){}[\]^"~*?:\\/\\\\]|&(?=&)|\|(?=\|)_',
            ],
            [
                '',
                '\\\\$0',
            ],
            $string
        );
    }

    public function applyWordLimit(string $string, &$overLimit = 0) : string
    {
        $words = preg_split('/\s+/', $string);
        $limitWords = array_slice($words, 0, self::WORD_LIMIT);

        $overLimit = count($words) - count($limitWords);

        return implode(' ', $limitWords);
    }

    public function order(string $direction = 'desc') : QueryBuilder
    {
        $this->order = 'desc' === $direction ? 'desc' : 'asc';

        return $this;
    }

    public function paginate(int $page = 1, int $perPage = 10) : QueryBuilder
    {
        $this->query['from'] = ($page - 1) * $perPage;
        $this->query['size'] = $perPage;

        return $this;
    }

    public function sortByRelevance($reverse = false) : QueryBuilder
    {
        $this->sort([
            '_score' => [
                'order' => $this->getSort($reverse),
            ],
        ]);

        return $this;
    }

    public function sortByDate($reverse = false) : QueryBuilder
    {
        $this->sort($this->dateQuery([
            'order' => $this->getSort($reverse),
            'missing' => '_last',
        ]));

        return $this;
    }

    public function whereSubjects(array $subjects = []) : QueryBuilder
    {
        $this->postFilter([
            'nested' => [
                'path' => 'subjects',
                'query' => [
                    'bool' => [
                        'filter' => [
                            'terms' => [
                                'subjects.id' => $subjects,
                            ],
                        ],
                    ],
                ],
            ],
        ]);

        return $this;
    }

    public function whereType(array $types = []) : QueryBuilder
    {
        $this->postFilter([
            'terms' => [
                'type' => $types,
            ],
        ]);

        return $this;
    }

    public function getRawQuery() : array
    {
        return $this->query;
    }

    private function dateQuery($query)
    {
        $arr = [];
        $arr[$this->dateType] = $query;

        return $arr;
    }

    public function betweenDates(DateTimeImmutable $startDate = null, DateTimeImmutable $endDate = null) : QueryBuilder
    {
        $query = ['format' => self::PHP_DATETIME_FORMAT];
        if ($startDate) {
            $query['gte'] = $startDate->format(self::ELASTIC_DATETIME_FORMAT);
        }
        if ($endDate) {
            $query['lte'] = $endDate->format(self::ELASTIC_DATETIME_FORMAT);
        }

        $this->query['body']['query']['bool']['filter'][] = ['range' => $this->dateQuery($query)];

        return $this;
    }
}
