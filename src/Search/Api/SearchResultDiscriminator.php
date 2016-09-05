<?php

namespace eLife\Search\Api;

use eLife\Search\Api\Response\ArticleResponse;
use eLife\Search\Api\Response\ArticleResponse\PoaArticle;
use eLife\Search\Api\Response\ArticleResponse\VorArticle;
use eLife\Search\Api\Response\SearchResult;
use JMS\Serializer\EventDispatcher\EventSubscriberInterface;
use JMS\Serializer\EventDispatcher\Events;
use JMS\Serializer\EventDispatcher\PreDeserializeEvent;
use JMS\Serializer\EventDispatcher\PreSerializeEvent;

class SearchResultDiscriminator implements EventSubscriberInterface
{
    public static function getSubscribedEvents()
    {
        return [
            ['event' => Events::PRE_SERIALIZE, 'method' => 'onPreSerialize'],
            ['event' => Events::PRE_DESERIALIZE, 'method' => 'onPreDeserialize']
        ];
    }

    public function onPreDeserialize(PreDeserializeEvent $event) {
        $data = $event->getData();
        if (isset($data['type'])) {
            $data['internal_type'] = $data['type'];
            if (isset($data['status'])) {
                $data['internal_type'] .=  '--' . $data['status'] ;
            }
            $event->setData($data);
        }
    }

    /**
     * @param PreSerializeEvent $event
     */
    public function onPreSerialize(PreSerializeEvent $event)
    {
        $object = $event->getObject();
        if (is_object($object) && $object instanceof SearchResult) {
            $object->internal_type = $object->getType();
            if ($object instanceof ArticleResponse) {
                $object->internal_type .= '--' . $object->status;
            }
        }
    }
}
