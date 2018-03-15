<?php

namespace eLife\Search\Annotation;

use Doctrine\Common\Annotations\AnnotationRegistry;

final class Register
{
    public static function registerLoader()
    {
        AnnotationRegistry::registerLoader(function ($class) {
            if (GearmanTask::class === $class) {
                require_once __DIR__.'/GearmanTask.php';
            }

            return class_exists($class);
        });
    }
}
