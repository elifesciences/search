<?php

namespace tests\eLife\Search;

/**
 * @group web2
 */
class EventDateTest extends ElasticTestCase
{
    public function test_event_end_date_is_correct()
    {
        $this->addDocumentToElasticSearch('
            {
              "content": [
                {
                  "text": "Some description",
                  "type": "paragraph"
                }
              ],
              "venue": {
                "name": [
                  "Cambridge Corn Exchange"
                ]
              },
              "impactStatement": "Event impact statement",
              "ends": "2017-01-28T16:00:00Z",
              "starts": "2017-01-28T12:00:00Z",
              "title": "Test of an event",
              "type": "event",
              "id": "8dfde846"
            }
        ');

        $this->newClient();
        $this->jsonRequest('GET', '/search');
        $json = $this->getJsonResponse();
        $this->assertEquals($json->items[0]->ends, '2017-01-28T16:00:00Z');
    }
}
