<?php
/**
 * README.
 *
 * This contains SHIMs for certain classes.
 */
namespace {
    require_once __DIR__.'/../vendor/autoload.php';

    if (!class_exists('GearmanClient')) {
        class GearmanClient
        {
        }

        define('GEARMAN_SUCCESS', 'GEARMAN_SUCCESS');
    }
}

namespace Csa\Bundle\GuzzleBundle\Cache {

    use Psr\Http\Message\RequestInterface;
    use Psr\Http\Message\ResponseInterface;

    interface StorageAdapterInterface
    {
        /**
         * @param RequestInterface $request
         *
         * @return null|ResponseInterface
         */
        public function fetch(RequestInterface $request);

        /**
         * @param RequestInterface  $request
         * @param ResponseInterface $response
         */
        public function save(RequestInterface $request, ResponseInterface $response);
    }

}

namespace test\eLife\ApiSdk {

    use Csa\Bundle\GuzzleBundle\Cache\StorageAdapterInterface;
    use eLife\ApiValidator\MessageValidator;
    use Psr\Http\Message\RequestInterface;
    use Psr\Http\Message\ResponseInterface;

    final class ValidatingStorageAdapter implements StorageAdapterInterface
    {
        private $storageAdapter;
        private $validator;

        public function __construct(StorageAdapterInterface $storageAdapter, MessageValidator $validator)
        {
            $this->storageAdapter = $storageAdapter;
            $this->validator = $validator;
        }

        public function fetch(RequestInterface $request)
        {
            return $this->storageAdapter->fetch($request);
        }

        public function save(RequestInterface $request, ResponseInterface $response)
        {
            $this->validator->validate($request);
            $this->validator->validate($response);

            $this->storageAdapter->save($request, $response);
        }
    }
}

namespace Csa\Bundle\GuzzleBundle\GuzzleHttp\Middleware {

    use Csa\Bundle\GuzzleBundle\Cache\StorageAdapterInterface;
    use GuzzleHttp\Promise\FulfilledPromise;
    use GuzzleHttp\Promise\RejectedPromise;
    use Psr\Http\Message\RequestInterface;

    /**
     * Mock Middleware.
     *
     * @author Charles Sarrazin <charles@sarraz.in>
     */
    class MockMiddleware extends CacheMiddleware
    {
        const DEBUG_HEADER = 'X-Guzzle-Mock';
        const DEBUG_HEADER_HIT = 'REPLAY';
        const DEBUG_HEADER_MISS = 'RECORD';

        private $mode;

        public function __construct(StorageAdapterInterface $adapter, $mode, $debug = false)
        {
            parent::__construct($adapter, $debug);

            $this->mode = $mode;
        }

        public function __invoke(callable $handler)
        {
            return function (RequestInterface $request, array $options) use ($handler) {
                if ('record' === $this->mode) {
                    return $this->handleSave($handler, $request, $options);
                }

                try {
                    if (null === $response = $this->adapter->fetch($request)) {
                        throw new \RuntimeException('Record not found.');
                    }

                    $response = $this->addDebugHeader($response, 'REPLAY');
                } catch (\RuntimeException $e) {
                    return new RejectedPromise($e);
                }

                return new FulfilledPromise($response);
            };
        }
    }
}

namespace Csa\Bundle\GuzzleBundle\GuzzleHttp\Middleware {

    use Csa\Bundle\GuzzleBundle\Cache\StorageAdapterInterface;
    use GuzzleHttp\Promise\FulfilledPromise;
    use Psr\Http\Message\RequestInterface;
    use Psr\Http\Message\ResponseInterface;

    /**
     * Cache Middleware.
     *
     * @author Charles Sarrazin <charles@sarraz.in>
     */
    class CacheMiddleware
    {
        const DEBUG_HEADER = 'X-Guzzle-Cache';
        const DEBUG_HEADER_HIT = 'HIT';
        const DEBUG_HEADER_MISS = 'MISS';

        protected $adapter;
        protected $debug;

        public function __construct(StorageAdapterInterface $adapter, $debug = false)
        {
            $this->adapter = $adapter;
            $this->debug = $debug;
        }

        public function __invoke(callable $handler)
        {
            return function (RequestInterface $request, array $options) use ($handler) {
                if (!$response = $this->adapter->fetch($request)) {
                    return $this->handleSave($handler, $request, $options);
                }

                $response = $this->addDebugHeader($response, static::DEBUG_HEADER_HIT);

                return new FulfilledPromise($response);
            };
        }

        protected function handleSave(callable $handler, RequestInterface $request, array $options)
        {
            return $handler($request, $options)->then(
                function (ResponseInterface $response) use ($request) {
                    $this->adapter->save($request, $response);

                    return $this->addDebugHeader($response, static::DEBUG_HEADER_MISS);
                }
            );
        }

        protected function addDebugHeader(ResponseInterface $response, $value)
        {
            if (!$this->debug) {
                return $response;
            }

            return $response->withHeader(static::DEBUG_HEADER, $value);
        }
    }

}
