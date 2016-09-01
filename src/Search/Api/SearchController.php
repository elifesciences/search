<?php

namespace eLife\Search\Api;

use eLife\Search\Api\Response\ArticleResponse\PoaArticle;
use eLife\Search\Api\Response\HasHeaders;
use eLife\Search\Api\Response\SearchResponse;
use JMS\Serializer\Serializer;
use Symfony\Component\HttpFoundation\Response;

class SearchController
{
    private $serializer;

    public function __construct(Serializer $serializer)
    {
        $this->serializer = $serializer;
    }

    public function serialize($data) {
        $headers = [];
        $json = $this->serializer->serialize($data, 'json');
        if ($data instanceof HasHeaders) {
            $headers = $data->getHeaders();
        }
        return new Response($json, 200, $headers);
    }

    public function indexAction() {
        return $this->serialize(new SearchResponse([]));
    }
}
