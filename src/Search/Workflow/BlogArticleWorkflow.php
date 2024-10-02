<?php

namespace eLife\Search\Workflow;

use eLife\ApiSdk\Model\BlogArticle;
use eLife\ApiSdk\Model\Model;
use eLife\Search\Api\ApiValidator;
use eLife\Search\Api\Elasticsearch\MappedElasticsearchClient;
use Psr\Log\LoggerInterface;
use Symfony\Component\Serializer\Serializer;

final class BlogArticleWorkflow extends AbstractWorkflow
{
    use Blocks;
    use JsonSerializeTransport;
    use SortDate;

    /**
     * @param BlogArticle $blogArticle
     * @return array
     */
    public function index(Model $blogArticle) : array
    {
        // Normalized fields.
        $blogArticleObject = json_decode($this->serialize($blogArticle));
        $blogArticleObject->type = 'blog-article';
        $blogArticleObject->body = $this->flattenBlocks($blogArticleObject->content ?? []);
        unset($blogArticleObject->content);
        $blogArticleObject->snippet = ['format' => 'json', 'value' => json_encode($this->snippet($blogArticle))];
        $this->addSortDate($blogArticleObject, $blogArticle->getPublishedDate());

        // Return.
        return [
            'json' => json_encode($blogArticleObject),
            'id' => $blogArticleObject->type.'-'.$blogArticle->getId(),
        ];
    }

    public function getSdkClass() : string
    {
        return BlogArticle::class;
    }
}
