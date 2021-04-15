<?php

namespace tests\eLife\Search\Workflow;

use ComposerLocator;
use eLife\ApiValidator\MessageValidator\JsonMessageValidator;
use eLife\ApiValidator\SchemaFinder\PathBasedSchemaFinder;
use eLife\Search\Api\ApiValidator;
use eLife\Search\Api\Elasticsearch\ElasticsearchDiscriminator;
use JMS\Serializer\EventDispatcher\EventDispatcher;
use JMS\Serializer\SerializationContext;
use JMS\Serializer\SerializerBuilder;
use JsonSchema\Validator;
use Symfony\Bridge\PsrHttpMessage\Factory\DiactorosFactory;

trait GetValidator
{
    public function getValidator()
    {
        $serializer = SerializerBuilder::create()
            ->configureListeners(function (EventDispatcher $dispatcher) {
                $dispatcher->addSubscriber(new ElasticsearchDiscriminator());
            })
            ->build();

        return new ApiValidator(
            $serializer,
            SerializationContext::create(),
            new JsonMessageValidator(
                new PathBasedSchemaFinder(ComposerLocator::getPath('elife/api').'/dist/model'),
                new Validator()
            ),
            new DiactorosFactory()
        );
    }
}
