<?php

namespace eLife\Search\Api;

use eLife\Search\Api\Response\ArticleResponse;
use eLife\Search\Api\Response\SearchResult;
use JMS\Serializer\EventDispatcher\Events;
use JMS\Serializer\EventDispatcher\EventSubscriberInterface;
use JMS\Serializer\EventDispatcher\PreDeserializeEvent;
use JMS\Serializer\EventDispatcher\PreSerializeEvent;

final class SearchResultDiscriminator implements EventSubscriberInterface
{
    public static function getSubscribedEvents()
    {
        return [
            ['event' => Events::PRE_SERIALIZE, 'method' => 'onPreSerialize'],
            ['event' => Events::PRE_DESERIALIZE, 'method' => 'onPreDeserialize'],
        ];
    }

    public function onPreDeserialize(PreDeserializeEvent $event)
    {
        $data = $event->getData();
        if (isset($data['type'])) {
            $data['internal_type'] = $data['type'];
            if (isset($data['status'])) {
                $data['internal_type'] .= '--'.$data['status'];
            }
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
