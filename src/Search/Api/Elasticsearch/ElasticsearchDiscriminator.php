<?php

namespace eLife\Search\Api\Elasticsearch;

use JMS\Serializer\EventDispatcher\Events;
use JMS\Serializer\EventDispatcher\EventSubscriberInterface;
use JMS\Serializer\EventDispatcher\PreDeserializeEvent;

final class ElasticsearchDiscriminator implements EventSubscriberInterface
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

        // Elasticsearch type setting.
        if (
            isset($data['_type']) &&
            isset($data['_source']) &&
            isset($data['_source']['type']) === false
        ) {
            $data['_source']['type'] = $data['_type'];
        }

        // Discriminator.
        switch (true) {
            // Nope out early to avoid errors.
            case
                isset($data['_index']) === false &&
                isset($data['_shards']) === false &&
                isset($data['acknowledged']) === false &&
                isset($data['created']) === false &&
                isset($data['error']) === false:
            case is_string($data):
                return;

            // We have an elastic search response (with search results).
            case isset($data['hits']):
                $data['internal_search_type'] = 'search';
                break;

            // We have a single individual result.
            case isset($data['_source']):
                $data['internal_search_type'] = 'document';
                break;

            // We have hit an error.
            // @todo maybe some normalization here?
            case isset($data['error']):
                $data['internal_search_type'] = 'error';
                break;

            // We have an acknowledged message (success)
            case isset($data['acknowledged']) && $data['acknowledged'] === true:
            case isset($data['created']) && $data['created'] === true:
            case isset($data['found']) && $data['found'] === true:
                $data['internal_search_type'] = 'success';
                break;

            default:
                $data['internal_search_type'] = 'unknown';
        }

        $event->setData($data);
    }
}
