<?php

namespace tests\eLife\Search\Workflow;

use eLife\ApiValidator\MessageValidator\JsonMessageValidator;
use eLife\ApiValidator\SchemaFinder\PuliSchemaFinder;
use eLife\Search\Api\ApiValidator;
use eLife\Search\Api\Elasticsearch\ElasticsearchDiscriminator;
use eLife\Search\Api\SearchResultDiscriminator;
use JMS\Serializer\EventDispatcher\EventDispatcher;
use JMS\Serializer\SerializationContext;
use JMS\Serializer\SerializerBuilder;
use Symfony\Bridge\PsrHttpMessage\Factory\DiactorosFactory;
use Webmozart\Json\JsonDecoder;

trait GetValidator
{
    public function getValidator()
    {
        $factoryClass = PULI_FACTORY_CLASS;

        $serializer = SerializerBuilder::create()
            ->configureListeners(function (EventDispatcher $dispatcher) {
                $dispatcher->addSubscriber(new ElasticsearchDiscriminator());
                $dispatcher->addSubscriber(new SearchResultDiscriminator());
            })
            ->build();

        return new ApiValidator(
            $serializer,
            SerializationContext::create(),
            new JsonMessageValidator(
                new PuliSchemaFinder((new $factoryClass())->createRepository()),
                new JsonDecoder()
            ),
            new DiactorosFactory()
        );
    }
}
