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

namespace Nytris\Rpc;

use Nytris\Rpc\Handler\HandlerInterface;
use React\Promise\PromiseInterface;

/**
 * Interface RpcInterface.
 *
 * @author Dan Phillimore <dan@ovms.co>
 */
interface RpcInterface
{
    /**
     * Calls a method on a remote handler object, returning the result as a promise.
     *
     * @param class-string<HandlerInterface> $handlerFqcn
     * @param string $method
     * @param array<mixed> $args
     * @return PromiseInterface<mixed>
     */
    public function call(string $handlerFqcn, string $method, array $args = []): PromiseInterface;

    /**
     * Starts the RPC instance, allowing it to send and receive messages.
     */
    public function start(): void;

    /**
     * Stops the RPC instance, preventing it from sending or receiving messages.
     * Instead, they will be queued until the instance is started again.
     */
    public function stop(): void;
}
