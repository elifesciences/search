<?php

namespace eLife\Search\Api;

use eLife\Search\Api\Response\ArticleResponse\PoaArticle;
use eLife\Search\Api\Response\BlogArticleResponse;
use eLife\Search\Api\Response\HasHeaders;
use eLife\Search\Api\Response\SearchResponse;
use JMS\Serializer\SerializationContext;
use JMS\Serializer\Serializer;
use Symfony\Component\HttpFoundation\Response;

class SearchController
{
    private $serializer;

    public function __construct(Serializer $serializer, SerializationContext $context)
    {
        $this->serializer = $serializer;
        $this->context = $context;
    }

    public function indexAction()
    {
        return $this->serialize(new SearchResponse([
            new PoaArticle(),
            new PoaArticle(),
        ]), 1);
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

    public function blogArticleAction()
    {
        $blog = $this->responseFromArray(BlogArticleResponse::class, [
            'id' => '123',
            'title' => 'testing title'
        ]);

        return $this->serialize(new SearchResponse([
            $blog,
            $blog,
            $blog
        ]), 1);
    }

    private function responseFromArray($className, $data)
    {
        return $this->serializer->deserialize(json_encode($data), $className, 'json');
    }
}
