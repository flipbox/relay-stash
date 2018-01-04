<?php

/**
 * @copyright  Copyright (c) Flipbox Digital Limited
 * @license    https://github.com/flipbox/relay-stash/blob/master/LICENSE
 * @link       https://github.com/flipbox/relay-stash
 */

namespace Flipbox\Relay\Middleware;

use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Stash\Interfaces\ItemInterface;

/**
 * @author Flipbox Factory <hello@flipboxfactory.com>
 * @since 1.1.0
 */
class Clear extends AbstractCache
{
    /**
     * @inheritdoc
     */
    public function __invoke(
        RequestInterface $request,
        ResponseInterface $response,
        callable $next = null
    ): ResponseInterface {
        parent::__invoke($request, $response);

        // Create a cache key
        $key = $this->getCacheKey($request);

        /** @var ItemInterface $item */
        $item = $this->pool->getItem($key);

        // If it's cached
        if ($item->isHit() && $item->clear()) {
            $this->info(
                "Item removed from cache successfully. [key: {key}]",
                [
                    'key' => $key
                ]
            );
        } else {
            $this->info(
                "Item not removed from cache. [key: {key}]",
                [
                    'key' => $key
                ]
            );
        }

        return $next($request, $response);
    }
}
