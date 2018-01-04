<?php

/**
 * @copyright  Copyright (c) Flipbox Digital Limited
 * @license    https://github.com/flipbox/relay-stash/blob/master/LICENSE
 * @link       https://github.com/flipbox/relay-stash
 */

namespace Flipbox\Relay\Middleware;

use Flipbox\Relay\Exceptions\InvalidCachePoolException;
use Flipbox\Http\Stream\Factory as StreamFactory;
use Psr\Cache\CacheItemPoolInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;
use Stash\Interfaces\ItemInterface;

/**
 * @author Flipbox Factory <hello@flipboxfactory.com>
 * @since 1.0.0
 */
class Stash extends AbstractMiddleware
{

    /**
     * @var CacheItemPoolInterface The connection
     */
    public $pool;

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
     * @inheritdoc
     */
    public function __invoke(
        RequestInterface $request,
        ResponseInterface $response,
        callable $next = null
    ): ResponseInterface {
        // Do parent (logging)
        parent::__invoke($request, $response);

        // Create a cache key
        $key = $this->getCacheKey($request);

        /** @var ItemInterface $item */
        $item = $this->pool->getItem($key);

        // If it's cached
        if ($item->isHit()) {
            return $this->applyCacheToResponseBody($response, $item);
        } else {
            // Log
            $this->info(
                "Item not found in Cache. [key: {key}]",
                [
                    'key' => $key
                ]
            );
        }

        // Lock item
        $item->lock();

        /** @var ResponseInterface $response */
        $response = $next($request, $response);

        // Only cache successful responses
        if ($this->isResponseSuccessful($response)) {
            $this->cacheResponse($response, $item);
        } else {
            // Log
            $this->info(
                "Did not save to cache because request was unsuccessful.",
                [
                    'key' => $key,
                    'statusCode' => $response->getStatusCode()
                ]
            );
        }

        return $response;
    }

    /**
     * @param ResponseInterface $response
     * @param ItemInterface $item
     * @return ResponseInterface
     */
    protected function applyCacheToResponseBody(ResponseInterface $response, ItemInterface $item)
    {
        // Log
        $this->info(
            "Item found in Cache. [key: {key}, expires: {expires}]",
            [
                'key' => $item->getKey(),
                'expires' => $item->getExpiration()->getTimestamp()
            ]
        );

        // Add response body
        return $response->withBody(
            StreamFactory::create($item->get())
        );
    }

    /**
     * @param ResponseInterface $response
     * @param ItemInterface $item
     */
    protected function cacheResponse(ResponseInterface $response, ItemInterface $item)
    {
        /** @var StreamInterface $body */
        $body = $response->getBody();

        // Set cache contents
        $item->set($body->getContents());

        // Save cache item
        $this->pool->save($item);

        // Rewind stream
        $body->rewind();

        // Log
        $this->info(
            "Save item to Cache. [key: {key}, expires: {expires}]",
            [
                'key' => $item->getKey(),
                'expires' => $item->getExpiration()->getTimestamp()
            ]
        );
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
        return $request->getMethod() . md5((string)$request->getUri());
    }
}
