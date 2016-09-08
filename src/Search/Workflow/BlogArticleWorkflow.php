<?php

namespace eLife\Search\Workflow;

use eLife\ApiClient\ApiClient\BlogClient;
use eLife\ApiClient\ApiClient\SubjectsClient;
use eLife\ApiSdk\Client\BlogArticles;
use eLife\ApiSdk\Client\Subjects;
use eLife\ApiSdk\Model\BlogArticle;
use eLife\Search\Annotation\GearmanTask;

class BlogArticleWorkflow implements Workflow
{
    private $blogClient;
    private $subjectClient;
    /**
     * @var SubjectsClient
     */
    private $subjectsClient;

    public function __construct(BlogClient $blogClient, SubjectsClient $subjectsClient)
    {
        $this->blogClient = $blogClient;
        $this->subjectsClient = $subjectsClient;
    }

    /**
     * @GearmanTask(
     *     name="get_single_blog_post",
     *     parameters={"offset"}
     * )
     */
    public function getSingleBlogArticle($offset)
    {
        // Articles
        $articles = new BlogArticles(
            $this->blogClient,
            new Subjects(
                $this->subjectsClient
            )
        );
        $subset = $articles->slice($offset, 2);
        $single = $subset->map(function (BlogArticle $item) {
            return $item->getTitle();
        })->toArray();

        return [
            'item' => $single[0],
            'next' => isset($single[1]),
        ];
    }

    /**
     * @GearmanTask(
     *     name="get_blog_posts",
     *     parameters={"page", "per-page"}
     * )
     */
    public function getBlogArticles($page, $perPage)
    {
        // Articles
        $articles = new BlogArticles(
            $this->blogClient,
            new Subjects(
                $this->subjectsClient
            )
        );
        $subset = $articles->slice($page, $perPage);

        return $subset->map(function (BlogArticle $item) {
            return $item->getTitle();
        })->toArray();
    }

    /**
     * @GearmanTask(
     *     name="reverse"
     * )
     */
    public function reverse($data)
    {
        return strrev($data);
    }
}
