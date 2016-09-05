<?php

namespace tests\eLife\Search\Api\Response;

use eLife\Search\Api\Response\EventResponse;
use tests\eLife\Search\SerializerTest;

class EventResponseTest extends SerializerTest
{
    public function testDeserialization()
    {
        $event = $this->responseFromArray(EventResponse::class, [
            'id' => '2',
            'title' => 'eLife Continuum webinar',
            'impactStatement' => 'something something impact',
            'starts' => '2016-08-04T15:00:00+00:00',
            'ends' => '2016-08-04T16:00:00+00:00',
            'timezone' => 'gmt',
        ]);

        $this->assertEquals('2', $event->id);
        $this->assertEquals('2016-08-04T15:00:00+00:00', $event->starts->format('c'));
        $this->assertEquals('2016-08-04T16:00:00+00:00', $event->ends->format('c'));
        $this->assertEquals('gmt', $event->timezone);
        $this->assertEquals('something something impact', $event->impactStatement);
        $this->assertEquals('event', $event->getType());
    }

    public function jsonProvider() : array
    {
        $minimum = '
            {
                "id": "2",
                "title": "eLife Continuum webinar",
                "starts": "2016-08-04T15:00:00+00:00",
                "ends": "2016-08-04T16:00:00+00:00",
                "content": [
                    {
                        "type": "paragraph",
                        "text": "We’re inviting all interested parties to participate in a webinar to learn about leveraging our new open-source publishing platform."
                    }
                ]
            }
        ';

        $minimum_expected = '
            {
                "id": "2",
                "type": "event",
                "title": "eLife Continuum webinar",
                "starts": "2016-08-04T15:00:00+00:00",
                "ends": "2016-08-04T16:00:00+00:00"
            }
        ';

        $complete = '
            {
                "id": "1",
                "title": "Changing peer review in cancer research: a seminar at Fred Hutch",
                "impactStatement": "How eLife is influencing the culture of peer review",
                "starts": "2016-04-22T20:00:00+00:00",
                "ends": "2016-04-22T21:00:00+00:00",
                "timezone": "America/Los_Angeles",
                "venue": {
                    "id": "ChIJzT5V0iQVkFQR0L1g2ZzelL0",
                    "coordinates": {
                        "latitude": 47.6272994,
                        "longitude": -122.3324216
                    },
                    "name": [
                        "D1-080/084 (Thomas Building)",
                        "Fred Hutchinson Cancer Research Center"
                    ],
                    "address": {
                        "formatted": [
                            "1100 Fairview Ave N",
                            "Seattle, WA 98109",
                            "United States"
                        ],
                        "components": {
                            "streetAddress": [
                                "1100 Fairview Ave North"
                            ],
                            "locality": [
                                "South Lake Union",
                                "Seattle"
                            ],
                            "area": [
                                "King County",
                                "Washington"
                            ],
                            "country": "United States",
                            "postalCode": "98109"
                        }
                    }
                },
                "content": [
                    {
                        "type": "paragraph",
                        "text": "Please join us for lunch and a discussion on peer review at eLife – the open-access journal for outstanding research backed by the Howard Hughes Medical Institute, the Max Planck Society, and the Wellcome Trust."
                    },
                    {
                        "type": "paragraph",
                        "text": "At eLife, working scientists, including Jon Cooper, Sue Biggins and Wenying Shou, make initial decisions and oversee the review process, which emphasises quality, speed, transparency, and saving time for authors."
                    },
                    {
                        "type": "paragraph",
                        "text": "Join us to learn how eLife is changing the culture of peer review, and more about what eLife publishes in life sciences research."
                    },
                    {
                        "type": "paragraph",
                        "text": "Plus free pizza and eLife t-shirts!"
                    },
                    {
                        "type": "paragraph",
                        "text": "All are welcome. Free to attend."
                    }
                ]
            }
        ';

        $complete_expected = '
            {
                "id": "1",
                "type": "event",
                "title": "Changing peer review in cancer research: a seminar at Fred Hutch",
                "impactStatement": "How eLife is influencing the culture of peer review",
                "starts": "2016-04-22T20:00:00+00:00",
                "ends": "2016-04-22T21:00:00+00:00",
                "timezone": "America/Los_Angeles"
            }
        ';

        return [
            [
                $minimum, $minimum_expected,
            ],
            [
                $complete, $complete_expected,
            ],
        ];
    }

    public function getResponseClass() : string
    {
        return EventResponse::class;
    }
}
