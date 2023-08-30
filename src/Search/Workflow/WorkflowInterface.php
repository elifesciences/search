<?php

namespace eLife\Search\Workflow;

use eLife\ApiSdk\Model\ArticleVersion;

interface WorkflowInterface
{
    public function run($entity);
    public function getSdkClass();
}