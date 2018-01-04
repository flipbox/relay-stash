<?php

/**
 * @copyright  Copyright (c) Flipbox Digital Limited
 * @license    https://github.com/flipbox/relay-stash/blob/master/LICENSE
 * @link       https://github.com/flipbox/relay-stash
 */

namespace Flipbox\Relay\Middleware;

use Flipbox\Relay\Exceptions\InvalidCachePoolException;
use Psr\Cache\CacheItemPoolInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * @author Flipbox Factory <hello@flipboxfactory.com>
 * @since 1.1.0
 */
abstract class AbstractCache extends AbstractMiddleware
{
    /**
     * @var CacheItemPoolInterface The connection
     */
    public $pool;

    /**
     * @var string
     */
    public $key;

    /**
     * @inheritdoc
     */
    public function init()
    {
        // Parent
        parent::init();

        // Ensure we have a valid pool
        if (!$this->pool instanceof CacheItemPoolInterface) {
            throw new InvalidCachePoolException(
                sprintf(
                    "The class '%s' requires a cache pool that is an instance of '%s', '%s' given.",
                    get_class($this),
                    CacheItemPoolInterface::class,
                    get_class($this->pool)
                )
            );
        }
    }

    /**
     * Returns the id used to cache a request.
     *
     * @param RequestInterface $request
     *
     * @return string
     */
    protected function getCacheKey(RequestInterface $request): string
    {
        if ($this->key === null) {
            $this->key = $request->getMethod() . md5((string)$request->getUri());
        }
        return (string)$this->key;
    }

    /**
     * @param ResponseInterface $response
     * @return bool
     */
    protected function isResponseSuccessful(ResponseInterface $response): bool
    {
        if ($response->getStatusCode() >= 200 &&
            $response->getStatusCode() < 300
        ) {
            return true;
        }

        $this->getLogger()->warning(
            "API request was not successful",
            [
                'code' => $response->getStatusCode(),
                'reason' => $response->getReasonPhrase()
            ]
        );

        return false;
    }
}
