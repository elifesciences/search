<?php

namespace eLife\Search\Workflow;

use eLife\ApiSdk\Model\Interview;
use eLife\ApiSdk\Model\Model;

final class InterviewWorkflow extends AbstractWorkflow
{
    use Blocks;
    use JsonSerializeTransport;
    use SortDate;

    /**
     * @param Interview $interview
     * @return array
     */
    public function prepare(Model $interview) : array
    {
        // Normalized fields.
        $interviewObject = json_decode($this->serialize($interview));
        $interviewObject->type = 'interview';
        $interviewObject->body = $this->flattenBlocks($interviewObject->content ?? []);
        unset($interviewObject->content);
        $interviewObject->snippet = ['format' => 'json', 'value' => json_encode($this->snippet($interview))];
        // Add publish date to sort on.
        $this->addSortDate($interviewObject, $interview->getPublishedDate());

        return [
            'json' => json_encode($interviewObject),
            'id' => $interviewObject->type.'-'.$interview->getId(),
        ];
    }

    public function deserialize(string $json) : Interview
    {
        return $this->serializer->deserialize($json, Interview::class, 'json');
    }

    public function serialize(Interview $interview) : string
    {
        return $this->serializer->serialize($interview, 'json');
    }

    public function getSdkClass() : string
    {
        return Interview::class;
    }
}
