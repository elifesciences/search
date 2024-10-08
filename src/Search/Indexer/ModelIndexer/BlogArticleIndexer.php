<?php

namespace eLife\Search\Indexer\ModelIndexer;

use eLife\ApiSdk\Model\BlogArticle;
use eLife\ApiSdk\Model\Model;
use eLife\Search\Indexer\ChangeSet;

final class BlogArticleIndexer extends AbstractModelIndexer
{

    protected function getSdkClass(): string
    {
        return BlogArticle::class;
    }

    /**
     * @param BlogArticle $blogArticle
     * @return ChangeSet
     */
    public function prepareChangeSet(Model $blogArticle) : ChangeSet
    {
        $changeSet = new ChangeSet();

        // Normalized fields.
        $blogArticleObject = json_decode($this->serialize($blogArticle));
        $blogArticleObject->type = 'blog-article';
        $blogArticleObject->body = $this->flattenBlocks($blogArticleObject->content ?? []);
        unset($blogArticleObject->content);
        $blogArticleObject->snippet = ['format' => 'json', 'value' => json_encode($this->snippet($blogArticle))];
        $this->addSortDate($blogArticleObject, $blogArticle->getPublishedDate());


        $changeSet->addInsert(
            $blogArticleObject->type.'-'.$blogArticle->getId(),
            json_encode($blogArticleObject)
        );

        return $changeSet;
    }
}
