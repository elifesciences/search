<?php

namespace eLife\Search\Api;

use eLife\ApiClient\HttpClient\Guzzle6HttpClient;
use eLife\ApiSdk\ApiSdk;
use eLife\ApiSdk\Client\BlogArticles;
use eLife\ApiSdk\Model\BlogArticle;
use eLife\Search\Api\Query\MockQueryBuilder;
use eLife\Search\Api\Response\BlogArticleResponse;
use eLife\Search\Api\Response\SearchResponse;
use eLife\Search\Workflow\ApiWorkflow;
use GuzzleHttp\Client;
use JMS\Serializer\SerializationContext;
use JMS\Serializer\Serializer;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class SearchController
{
    private $serializer;
    private $apiUrl;

    public function __construct(
        Serializer $serializer,
        SerializationContext $context,
        string $apiUrl
    ) {
        $this->serializer = $serializer;
        $this->context = $context;
        $this->apiUrl = $apiUrl;
    }

    public function blogApiAction()
    {
        $sdk = new ApiSdk(
            new Guzzle6HttpClient(
                new Client(['base_uri' => $this->apiUrl])
            )
        );
        $workflow = new ApiWorkflow(
           $sdk,
            $this->serializer
        );

        $workflow->initialize();
        $workflow->useContext('blog-articles');

        while ($article = $workflow->getNext()) {
            $snippet = $sdk->getSerializer()->normalize($article);
        }

        $workflow->tearDown();

        return '';
//        // Create article thing.
//        $articles = $sdk->blogArticles();
//        // Loop
//        foreach ($articles as $article) {
//            // Prompt some PStorm auto-complete
//            if ($article instanceof BlogArticle) {
//                // Get the title
//                $snippet = $sdk->getSerializer()->normalize($article);
//                var_dump($snippet);
//            }
//        }

//        return '';
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

        $result = $this->responseFromArray(SearchResponse::class, ['items' => $data]);

        return $this->serialize($result);
    }

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
        return $this->serialize(new SearchResponse([]), 1);
    }

    public function blogArticleAction()
    {
        $blog = $this->responseFromArray(BlogArticleResponse::class, [
            'id' => '123',
            'title' => 'testing title',
        ]);

        return $this->serialize(new SearchResponse([
            $blog,
            $blog,
            $blog,
        ]), 1);
    }
}
