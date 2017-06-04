<?php

/**
 * @copyright  Copyright (c) Flipbox Digital Limited
 * @license    https://github.com/flipbox/relay-stash/blob/master/LICENSE
 * @link       https://github.com/flipbox/relay-stash
 */

namespace Flipbox\Stash\Exceptions;

/**
 * @author Flipbox Factory <hello@flipboxfactory.com>
 * @since 1.0.0
 */
class InvalidCachePoolException extends \Exception
{
    /**
     * @return string
     */
    public function getName(): string
    {
        return 'Invalid Cache Pool';
    }
}