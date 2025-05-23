<?php

/*
 * Nytris RPC - Remote Procedure Call abstraction for PHP.
 * Copyright (c) Dan Phillimore (asmblah)
 * https://github.com/nytris/rpc/
 *
 * Released under the MIT license.
 * https://github.com/nytris/rpc/raw/main/MIT-LICENSE.txt
 */

declare(strict_types=1);

namespace Nytris\Rpc\Handler;

use BadMethodCallException;

/**
 * Trait HandlerTrait.
 *
 * @author Dan Phillimore <dan@ovms.co>
 */
trait HandlerTrait
{
    /**
     * Called when an RPC call is received for a method not defined by the handler.
     *
     * @param string $method
     * @param array<mixed> $args
     */
    public function onUndefinedMethod(string $method, array $args): mixed
    {
        throw new BadMethodCallException(
            'Method "' . $method . '" is not defined by handler "' . static::class . '"'
        );
    }
}
