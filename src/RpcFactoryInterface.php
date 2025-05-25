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
use React\Stream\ReadableStreamInterface;
use React\Stream\WritableStreamInterface;

/**
 * Interface RpcFactoryInterface.
 *
 * Library entrypoint; creates RPC instances with different configurations.
 *
 * @author Dan Phillimore <dan@ovms.co>
 */
interface RpcFactoryInterface
{
    /**
     * Creates a new RPC instance with custom streams and handlers.
     *
     * @param ReadableStreamInterface $inputStream The stream to read messages from
     * @param WritableStreamInterface $outputStream The stream to write messages to
     * @param HandlerInterface[] $handlers Handlers to register with the dispatcher
     * @return RpcInterface
     */
    public function createStreamRpc(
        ReadableStreamInterface $inputStream,
        WritableStreamInterface $outputStream,
        array $handlers = []
    ): RpcInterface;
}
