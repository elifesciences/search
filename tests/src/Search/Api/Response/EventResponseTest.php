<?php

namespace tests\eLife\Search\Api\Response;

use eLife\Search\Api\Response\EventResponse;
use tests\eLife\Search\RamlRequirement;
use tests\eLife\Search\SerializerTest;

class EventResponseTest extends SerializerTest
{
    use RamlRequirement;

    public function testDeserialization()
    {
        $event = $this->responseFromArray(EventResponse::class, [
            'id' => '2',
            'title' => 'eLife Continuum webinar',
            'impactStatement' => 'something something impact',
            'starts' => '2016-08-04T15:00:00Z',
            'ends' => '2016-08-04T16:00:00Z',
            'timezone' => 'gmt',
        ]);

        $this->assertEquals('2', $event->id);
        $this->assertEquals('2016-08-04T15:00:00Z', $event->starts->format('Y-m-d\TH:i:s\Z'));
        $this->assertEquals('2016-08-04T16:00:00Z', $event->ends->format('Y-m-d\TH:i:s\Z'));
        $this->assertEquals('gmt', $event->timezone);
        $this->assertEquals('something something impact', $event->impactStatement);
        $this->assertEquals('event', $event->getType());
    }

    public function jsonProvider() : array
    {
        return [
            [
                $this->getFixture('event/v1/minimum.json'), '
                {
                    "id": "2",
                    "type": "event",
                    "title": "eLife Continuum webinar",
                    "starts": "2016-08-04T15:00:00Z",
                    "ends": "2016-08-04T16:00:00Z"
                }',
            ],
            [
                $this->getFixture('event/v1/complete.json'), '
                {
                    "id": "1",
                    "type": "event",
                    "title": "Changing peer review in cancer research: a seminar at Fred Hutch",
                    "impactStatement": "How eLife is influencing the culture of peer review",
                    "starts": "2016-04-22T20:00:00Z",
                    "ends": "2016-04-22T21:00:00Z",
                    "timezone": "America/Los_Angeles"
                }',
            ],
        ];
    }

    public function getResponseClass() : string
    {
        return EventResponse::class;
    }
}
