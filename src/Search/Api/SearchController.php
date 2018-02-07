<?php

namespace eLife\Search\Api;

use DateTimeImmutable;
use DateTimeZone;
use eLife\ApiSdk\Model\Subject;
use eLife\Search\Api\Elasticsearch\ElasticQueryBuilder;
use eLife\Search\Api\Elasticsearch\ElasticQueryExecutor;
use eLife\Search\Api\Elasticsearch\MappedElasticsearchClient;
use eLife\Search\Api\Elasticsearch\Response\ErrorResponse;
use eLife\Search\Api\Query\QueryResponse;
use eLife\Search\Api\Response\SearchResponse;
use eLife\Search\Api\Response\TypesResponse;
use JMS\Serializer\SerializationContext;
use JMS\Serializer\Serializer;
use Negotiation\Accept;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\ServiceUnavailableHttpException;

final class SearchController
{
    private $serializer;
    private $apiUrl;
    private $elastic;
    private $context;
    private $elasticIndex;
    private $logger;

    public function __construct(
        Serializer $serializer,
        LoggerInterface $logger,
        SerializationContext $context,
        ElasticQueryExecutor $elastic,
        MappedElasticsearchClient $client,
        string $apiUrl,
        string $elasticIndex,
        array $clientOptions = []
    ) {
        $this->serializer = $serializer;
        $this->logger = $logger;
        $this->context = $context;
        $this->elastic = $elastic;
        $this->client = $client;
        $this->apiUrl = $apiUrl;
        $this->elasticIndex = $elasticIndex;
        $this->clientOptions = $clientOptions;
    }

    private function validateDateRange(DateTimeImmutable $startDateTime = null, DateTimeImmutable $endDateTime = null)
    {
        if ($endDateTime === false || $startDateTime === false) {
            throw new BadRequestHttpException('Invalid date provided');
        }
        if (
            ($endDateTime && $startDateTime) &&
            ($startDateTime->diff($endDateTime)->invert === 1)
        ) {
            throw new BadRequestHttpException('start-date must be the same or before end-date');
        }
    }

    private function createValidDateTime(string $format, string $time, $strict = true)
    {
        $dateTime = DateTimeImmutable::createFromFormat($format, $time, new DateTimeZone('UTC'));
        $errors = DateTimeImmutable::getLastErrors();
        if (
            ($strict && $errors['warning_count'] !== 0) ||
            $errors['error_count'] !== 0
        ) {
            throw new BadRequestHttpException("Invalid date format provided ($format)");
        }

        return $dateTime;
    }

    public function indexAction(Request $request, Accept $type)
    {
        $for = $request->query->get('for', '');
        $order = $request->query->get('order', 'desc');
        $page = $request->query->getInt('page', 1);
        $perPage = $request->query->getInt('per-page', 10);
        $useDate = $request->query->get('use-date', 'default');
        $sort = $request->query->get('sort', 'relevance');
        $subjects = $request->query->get('subject');
        $types = $request->query->get('type');
        $startDate = $request->query->get('start-date');
        $endDate = $request->query->get('end-date');
        $startDateTime = null;
        $endDateTime = null;

        if ($page < 1) {
            throw new BadRequestHttpException('Invalid page parameter');
        }

        if ($perPage < 1 || $perPage > 100) {
            throw new BadRequestHttpException('Invalid per-page parameter');
        }

        if ($endDate || $startDate) {
            $startDateTime = $startDate ? $this->createValidDateTime('Y-m-d H:i:s', $startDate.' 00:00:00') : null;
            $endDateTime = $endDate ? $this->createValidDateTime('Y-m-d H:i:s', $endDate.' 23:59:59') : null;
            $this->validateDateRange($startDateTime, $endDateTime);
        }

        /** @var ElasticQueryBuilder $query */
        $query = new ElasticQueryBuilder($this->elasticIndex, $this->elastic);

        $query = $query->searchFor($for);

        $query->setDateType($useDate);

        if ($subjects) {
            $query->whereSubjects($subjects);
        }
        if ($types) {
            $query->whereType($types);
        }

        if ($startDateTime || $endDateTime) {
            $query->betweenDates($startDateTime, $endDateTime);
        }

        $query = $query
            ->paginate($page, $perPage)
            ->order($order);

        switch ($sort) {
            case 'date':
                $query = $query->sortByDate();
                break;
            case 'relevance':
            default:
                $query = $query->sortByRelevance();
                break;
        }

        $query = $query->setClientOptions($this->clientOptions);

        $data = $this->client->searchDocuments($query->getRawQuery());

        if ($data instanceof QueryResponse) {
            if ($page > 1 && 0 === count($data->toArray())) {
                throw new NotFoundHttpException("No page {$page}");
            }

            $result = new SearchResponse(
                $data->toArray(),
                $data->getTotalResults(),
                $this->hydrateSubjects($data->getSubjects()),
                TypesResponse::fromArray($data->getTypeTotals())
            );

            return $this->serialize($result, $type->getParameter('version'));
        }
        if ($data instanceof ErrorResponse) {
            $this->logger->error('Error from elastic search during request', [
                'request' => $request,
                'error' => $data->error,
            ]);
        } else {
            $this->logger->error('Unknown error from elastic search during request', [
                'request' => $request,
                'error' => $data,
            ]);
        }

        throw new ServiceUnavailableHttpException(10);
    }

    /**
     * This will be replaced by call to cached subjects.
     */
    public function getSubjectName(string $id) : string
    {
        $words = explode('-', $id);
        $words[0] = ucfirst($words[0]);
        $words = array_map(function ($word) {
            if (in_array($word, ['the', 'a', 'of', 'in']) === false) {
                return ucfirst($word);
            }

            return $word;
        }, $words);

        return implode(' ', $words);
    }

    public function hydrateSubjects(array $subjects)
    {
        return array_map(function ($subject) {
            if ($subject['name'] === null) {
                $subject['name'] = $this->getSubjectName($subject['id']);
            }

            return $subject;
        }, $subjects);
    }

    private function serialize($data, int $version = null, $group = null)
    {
        $context = clone $this->context;
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
}
