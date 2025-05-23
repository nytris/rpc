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

namespace Nytris\Rpc\Dispatcher;

use BadMethodCallException;
use Nytris\Rpc\Handler\HandlerInterface;

/**
 * Class Dispatcher.
 *
 * @author Dan Phillimore <dan@ovms.co>
 */
class Dispatcher implements DispatcherInterface
{
    /**
     * @var array<string, HandlerInterface>
     */
    private array $handlerFqcnToHandlerMap = [];

    /**
     * @inheritDoc
     */
    public function dispatch(string $handlerFqcn, string $method, array $args): mixed
    {
        $handler = $this->handlerFqcnToHandlerMap[$handlerFqcn] ?? null;

        if ($handler === null) {
            throw new BadMethodCallException(sprintf(
                'No handler registered for FQCN "%s" when attempting to call method "%s"',
                $handlerFqcn,
                $method
            ));
        }

        return method_exists($handler, $method) ?
            $handler->$method(...$args) :
            $handler->onUndefinedMethod($method, $args);
    }

    /**
     * @inheritDoc
     */
    public function registerHandler(HandlerInterface $handler): void
    {
        $this->handlerFqcnToHandlerMap[$handler::class] = $handler;
    }
}
