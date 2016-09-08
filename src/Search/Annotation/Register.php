<?php

namespace eLife\Search\Annotation;

use Doctrine\Common\Annotations\AnnotationRegistry;

class Register
{
    public static function registerLoader()
    {
        AnnotationRegistry::registerLoader(function ($class) {
            if ($class === GearmanTask::class) {
                require_once __DIR__.'/GearmanTask.php';
            }

            return class_exists($class);
        });
    }
}
