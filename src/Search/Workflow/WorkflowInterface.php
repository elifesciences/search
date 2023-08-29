<?php

namespace eLife\Search\Queue;

use eLife\ApiSdk\Model\ArticleVersion;

interface WorkflowInterface
{
    public function insert(string $json, string $id);
    public function index(ArticleVersion $article): array;
    public function postValidate(string $id);

    public function getSdkClass();
}