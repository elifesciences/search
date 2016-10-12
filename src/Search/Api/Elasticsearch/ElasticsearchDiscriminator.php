<?php

namespace eLife\Search\Api\Elasticsearch;

use JMS\Serializer\EventDispatcher\Events;
use JMS\Serializer\EventDispatcher\EventSubscriberInterface;
use JMS\Serializer\EventDispatcher\PreDeserializeEvent;

class ElasticsearchDiscriminator implements EventSubscriberInterface
{
    /**
     * Returns the events to which this class has subscribed.
     *
     * Return format:
     *     array(
     *         array('event' => 'the-event-name', 'method' => 'onEventName', 'class' => 'some-class', 'format' => 'json'),
     *         array(...),
     *     )
     *
     * The class may be omitted if the class wants to subscribe to events of all classes.
     * Same goes for the format key.
     *
     * @return array
     */
    public static function getSubscribedEvents()
    {
        return [
            ['event' => Events::PRE_DESERIALIZE, 'method' => 'onPreDeserialize'],
        ];
    }

    public function onPreDeserialize(PreDeserializeEvent $event)
    {
        $data = $event->getData();

        switch (true) {
            // Nope out early to avoid errors.
            default:
            case is_string($data):
                return;

            // We have an elastic search response (with search results).
            case isset($data['hits']):
                $data['internal_type'] = 'hits';
                break;

            // We have a single individual result.
            // @todo Move to wrapper around single result. (only if its used much!)
            case isset($data['_source']):
                $data = $data['_source'];
                break;

            // We have hit an error.
            // @todo maybe some normalization here?
            case isset($data['error']):
                $data['internal_type'] = 'error';
                break;

            // We have an acknowledged message (success)
            // @todo look into non successful versions of these
            case isset($data['acknowledged']) && $data['acknowledged'] === true:
                $data['internal_type'] = 'success';
                break;
        }

        $event->setData($data);
    }
}
