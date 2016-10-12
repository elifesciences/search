<?php

namespace eLife\Search\Api\Elasticsearch;

use JMS\Serializer\EventDispatcher\Events;
use JMS\Serializer\EventDispatcher\EventSubscriberInterface;
use JMS\Serializer\EventDispatcher\PreDeserializeEvent;
use JMS\Serializer\EventDispatcher\PreSerializeEvent;

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
//            ['event' => Events::PRE_SERIALIZE, 'method' => 'onPreSerialize'],
            ['event' => Events::PRE_DESERIALIZE, 'method' => 'onPreDeserialize'],
        ];
    }

    public function onPreDeserialize(PreDeserializeEvent $event)
    {
        $data = $event->getData();
        if (is_string($data)) {
            return;
        }

        if (isset($data['_source'])) {
            $event->setData($data['_source']);

            return;
        }

        if (isset($data['error'])) {
            $data['internal_type'] = 'error';
            $event->setData($data);

            return;
        }
        if (isset($data['acknowledged']) && $data['acknowledged'] === true) {
            $data['internal_type'] = 'success';
            $event->setData($data);

            return;
        }

        if (!isset($data['internal_type'])) {
            $data['internal_type'] = 'success';
            $event->setData($data);
        }
    }

    public function onPreSerialize(PreSerializeEvent $event)
    {
        $object = $event->getObject();
        if (is_object($object) && $object instanceof SearchResult) {
            /* @noinspection PhpUndefinedFieldInspection */
            $object->internal_type = $object->getType();
            if ($object instanceof ArticleResponse) {
                /* @noinspection PhpUndefinedFieldInspection */
                $object->internal_type .= '--'.$object->status;
            }
        }
    }
}
