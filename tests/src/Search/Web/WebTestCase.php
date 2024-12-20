<?php

namespace tests\eLife\Search\Web;

use eLife\Search\Kernel;
use RuntimeException;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Silex\WebTestCase as SilexWebTestCase;
use Symfony\Component\HttpKernel\HttpKernelBrowser;

abstract class WebTestCase extends SilexWebTestCase
{
    protected Kernel $kernel;

    protected HttpKernelBrowser $api;

    /**
     * Creates the application.
     *
     * @return HttpKernelInterface
     */
    public function createApplication()
    {
        $this->kernel = new Kernel($this->createConfiguration());

        return $this->kernel->getApp();
    }

    public function createConfiguration()
    {
        if (file_exists($configFile = __DIR__.'/../../../../config.php')) {
            $config = include __DIR__.'/../../../../config.php';
        } else {
            throw new RuntimeException('No config.php has been found.');
        }

        return $this->modifyConfiguration($config);
    }

    public function modifyConfiguration($config)
    {
        return $config;
    }

    public function newClient()
    {
        return $this->api = $this->createClient();
    }

    public function getResponse()
    {
        return $this->api->getResponse();
    }
}
