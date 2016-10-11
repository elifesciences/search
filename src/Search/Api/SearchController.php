<?php

namespace eLife\Search\Api;

use Doctrine\Common\Cache\Cache;
use eLife\ApiSdk\Model\Subject;
use eLife\Search\Api\Elasticsearch\ElasticSearchResponse;
use eLife\Search\Api\Query\MockQueryBuilder;
use eLife\Search\Api\Query\QueryResponse;
use eLife\Search\Api\Response\SearchResponse;
use eLife\Search\Api\Response\SearchResult;
use eLife\Search\Api\Response\TypesResponse;
use JMS\Serializer\SerializationContext;
use JMS\Serializer\Serializer;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\ServiceUnavailableHttpException;

final class SearchController
{
    private $serializer;
    private $apiUrl;

    public function __construct(
        Serializer $serializer,
        SerializationContext $context,
        Cache $cache,
        string $apiUrl,
        SubjectStore $subjects
    ) {
        $this->serializer = $serializer;
        $this->context = $context;
        $this->cache = $cache;
        $this->apiUrl = $apiUrl;
        $this->subjects = $subjects;
    }

    public function blogApiAction()
    {
        $es = [
            'took' => 1,
            'timed_out' => false,
            '_shards' => [
                'total' => 5,
                'successful' => 5,
                'failed' => 0,
            ],
            'hits' => [
                'total' => 1,
                'max_score' => 0.30685282,
                'hits' => [
                    [
                        '_source' => [
                            'id' => '12456',
                            'type' => 'blog-article',
                            'title' => 'some blog article',
                            'impactStatement' => 'Something impacting in a statement like fashion.',
                            'published' => '2016-06-09T15:15:10+00:00',
                        ],
                    ],
                ],
            ],
        ];
        $data = $this->serializer->deserialize(json_encode($es), ElasticSearchResponse::class, 'json');

        $result = new SearchResponse(
            $data->toArray(),
            $data->getTotalResults(),
            $data->getSubjects(),
            TypesResponse::fromArray($data->getTypeTotals())
        );

        return $this->serialize($result);
    }

    public function searchTestAction(Request $request)
    {
        $for = $request->query->get('for', '');
        $order = $request->query->get('order', 'desc');
        $page = $request->query->get('page', 1);
        $perPage = $request->query->get('per-page', 10);
        // $sort = $request->query->get('sort');
        $subjects = $request->query->get('subject');
        $types = $request->query->get('type');

        $query = new MockQueryBuilder();

        $query = $query->searchFor($for);

        if ($subjects) {
            $query->whereSubjects($subjects);
        }
        if ($types) {
            $query->whereType($types);
        }

        $query = $query
            ->paginate($page, $perPage)
            ->order($order);

        $data = $query->getQuery()->execute();

        if ($data instanceof QueryResponse) {
            $result = new SearchResponse(
                $data->map([$this, 'responseFromJson']),
                $data->getTotalResults(),
                $data->getSubjects(),
                TypesResponse::fromArray($data->getTypeTotals())
            );
            // The new code.
            if (null) {
                $result = new SearchResponse(
                    $data->toArray(),
                    $data->getTotalResults(),
                    $data->getSubjects(),
                    TypesResponse::fromArray($data->getTypeTotals())
                );
            }

            return $this->serialize($result);
        }

        throw new ServiceUnavailableHttpException(10);
    }

    public function responseFromJson($json)
    {
        return $this->serializer->deserialize($json, SearchResult::class, 'json');
    }

    /**
     * @internal
     */
    private function responseFromArray($className, $data)
    {
        return $this->serializer->deserialize(json_encode($data), $className, 'json');
    }

    private function serialize($data, int $version = null, $group = null)
    {
        $context = $this->context;
        if ($version) {
            $context->setVersion($version);
        }
        if ($group) {
            $context->setGroups([$group]);
        }
        $headers = [];
        $json = $this->serializer->serialize($data, 'json', $context);
        if ($data instanceof HasHeaders) {
            $headers = $data->getHeaders();
        }

        return new Response($json, 200, $headers);
    }

    public function indexAction()
    {
        return $this->serialize(new SearchResponse([], 0, [], TypesResponse::fromArray([])), 1);
    }
}
