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

    private $dateType;

    public function __construct(string $index)
    {
        $this->query['index'] = $index;
        $this->query['body']['aggs']['type_agg']['terms'] = [
            'field' => 'type',
        ];
        $this->query['body']['aggs']['subject_agg'] = [
            'nested' => [
                'path' => 'subjects',
            ],
            'aggs' => [
                'name' => [
                    'terms' => [
                        'field' => 'subjects.id',
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

    private function postQuery(string $key, $value)
    {
        if (isset($this->query['body']['post_filter']['terms'])) {
            $firstFilter = $this->query['body']['post_filter']['terms'];
            $secondFilter = [];
            $secondFilter[$key] = $value;
            unset($this->query['body']['post_filter']['terms']);
            $this->query['body']['post_filter']['query']['bool']['must'] = [
                ['terms' => $firstFilter],
                ['terms' => $secondFilter],
            ];
        } elseif (isset($this->query['body']['post_filter']['query']['bool']['must'])) {
            $nthFilter = [];
            $nthFilter[$key] = $value;
            $this->query['body']['post_filter']['query']['bool']['must'][] = ['terms' => $nthFilter];
        } else {
            $this->query['body']['post_filter'] = $this->query['body']['post_filter'] ?? [];
            $this->query['body']['post_filter']['terms'][$key] = $value;
        }
    }

    private function query($key, array $body)
    {
        $this->query['body']['query'] = $this->query['body']['query'] ?? [];
        $this->query['body']['query'][$key] = $body;
    }

    private function setBoostings(array $query = [])
    {
        /* Boost results based on 'type' */
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
            $this->query['body']['query']['bool']['should'][] = ['constant_score' => ['boost' => 10, 'query' => ['match' => ['authorLine' => $query]]]];
            $this->query['body']['query']['bool']['should'][] = ['constant_score' => ['boost' => 10, 'query' => ['match' => ['authors.affiliations.name' => $query]]]];
            $this->query['body']['query']['bool']['should'][] = ['constant_score' => ['boost' => 10, 'query' => ['match' => ['authors.name.preferred' => $query]]]];
            $this->query['body']['query']['bool']['should'][] = ['constant_score' => ['boost' => 10, 'query' => ['match' => ['authors.name.value' => $query]]]];
            $this->query['body']['query']['bool']['should'][] = ['constant_score' => ['boost' => 10, 'query' => ['match' => ['authors.onBehalfOf' => $query]]]];
            $this->query['body']['query']['bool']['should'][] = ['constant_score' => ['boost' => 10, 'query' => ['match' => ['authors.people.name.preferred' => $query]]]];

            $this->query['body']['query']['bool']['should'][] = ['constant_score' => ['boost' => 9, 'query' => ['match' => ['title' => $query]]]];

            $this->query['body']['query']['bool']['should'][] = ['constant_score' => ['boost' => 8, 'query' => ['match' => ['curators.name.preferred' => $query]]]];
            $this->query['body']['query']['bool']['should'][] = ['constant_score' => ['boost' => 8, 'query' => ['match' => ['curators.orcid' => $query]]]];
            $this->query['body']['query']['bool']['should'][] = ['constant_score' => ['boost' => 8, 'query' => ['match' => ['dataSets.value' => $query]]]];
            $this->query['body']['query']['bool']['should'][] = ['constant_score' => ['boost' => 8, 'query' => ['match' => ['reviewers.name.preferred' => $query]]]];

            $this->query['body']['query']['bool']['should'][] = ['constant_score' => ['boost' => 6, 'query' => ['match' => ['funding.value' => $query]]]];

            $this->query['body']['query']['bool']['should'][] = ['constant_score' => ['boost' => 4, 'query' => ['match' => ['keywords' => $query]]]];
            $this->query['body']['query']['bool']['should'][] = ['constant_score' => ['boost' => 4, 'query' => ['match' => ['researchOrganisms' => $query]]]];
            $this->query['body']['query']['bool']['should'][] = ['constant_score' => ['boost' => 4, 'query' => ['match' => ['subjects.name' => $query]]]];

            $this->query['body']['query']['bool']['should'][] = ['constant_score' => ['boost' => 3, 'query' => ['match' => ['abstract.content.text' => $query]]]];
            $this->query['body']['query']['bool']['should'][] = ['constant_score' => ['boost' => 3, 'query' => ['match' => ['digest.content.text' => $query]]]];
            $this->query['body']['query']['bool']['should'][] = ['constant_score' => ['boost' => 3, 'query' => ['match' => ['impactStatement' => $query]]]];

            $this->query['body']['query']['bool']['should'][] = ['constant_score' => ['boost' => 2, 'query' => ['match' => ['appendices.content' => $query]]]];
            $this->query['body']['query']['bool']['should'][] = ['constant_score' => ['boost' => 2, 'query' => ['match' => ['authorResponse' => $query]]]];
            $this->query['body']['query']['bool']['should'][] = ['constant_score' => ['boost' => 2, 'query' => ['match' => ['authors.orcid' => $query]]]];
            $this->query['body']['query']['bool']['should'][] = ['constant_score' => ['boost' => 2, 'query' => ['match' => ['body' => $query]]]];
            $this->query['body']['query']['bool']['should'][] = ['constant_score' => ['boost' => 2, 'query' => ['match' => ['decisionLetter' => $query]]]];
            $this->query['body']['query']['bool']['should'][] = ['constant_score' => ['boost' => 2, 'query' => ['match' => ['doi' => $query]]]];
            $this->query['body']['query']['bool']['should'][] = ['constant_score' => ['boost' => 2, 'query' => ['match' => ['elocationId' => $query]]]];
            $this->query['body']['query']['bool']['should'][] = ['constant_score' => ['boost' => 2, 'query' => ['match' => ['id' => $query]]]];
        }
    }

    public function searchFor(string $string) : QueryBuilder
    {
        if ('' !== $string) {
            /* Query all fields for the actual query term*/
            $query = [
                'query' => $string,
                'operator' => 'and',
                'fuzziness' => 'auto',
            ];

            $this->query['body']['query']['bool']['must'][] = ['query' => ['match' => ['_all' => $query]]];

            $this->setBoostings($query);
        }

        return $this;
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
        $this->postQuery('subjects.id', $subjects);

        return $this;
    }

    public function whereType(array $types = []) : QueryBuilder
    {
        $this->postQuery('_type', $types);

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

        $this->query['body']['query']['bool']['must'][] = ['query' => ['range' => $this->dateQuery($query)]];

        return $this;
    }
}
