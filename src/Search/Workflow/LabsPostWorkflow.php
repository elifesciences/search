<?php

namespace eLife\Search\Workflow;

use eLife\ApiSdk\Model\LabsPost;
use eLife\ApiSdk\Model\Model;

final class LabsPostWorkflow extends AbstractWorkflow
{
    use Blocks;
    use JsonSerializeTransport;
    use SortDate;

    /**
     * @param LabsPost $labsPost
     * @return array
     */
    public function prepare(Model $labsPost) : array
    {
        // Normalized fields.
        $labsPostObject = json_decode($this->serialize($labsPost));
        $labsPostObject->type = 'labs-post';
        $labsPostObject->body = $this->flattenBlocks($labsPostObject->content ?? []);
        unset($labsPostObject->content);
        $labsPostObject->snippet = ['format' => 'json', 'value' => json_encode($this->snippet($labsPost))];
        $this->addSortDate($labsPostObject, $labsPost->getPublishedDate());

        return [
            'json' => json_encode($labsPostObject),
            'id' => $labsPostObject->type.'-'.$labsPost->getId(),
        ];
    }

    public function getSdkClass() : string
    {
        return LabsPost::class;
    }
}
