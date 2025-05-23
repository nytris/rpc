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

namespace Nytris\Rpc\Transport;

use Nytris\Rpc\Transport\Message\MessageType;

/**
 * Interface TransportInterface.
 *
 * @author Dan Phillimore <dan@ovms.co>
 */
interface TransportInterface
{
    /**
     * Starts the transport listening for incoming messages.
     *
     * If the incoming message is a return value or error for an RPC call originating
     * from this end, then the promise for the call will be resolved/rejected accordingly.
     *
     * If the incoming message is for an RPC call originating from the remote end,
     * then the dispatcher will be used to dispatch the call to the appropriate handler.
     */
    public function listen(): void;

    /**
     * Stops the transport from listening for or transmitting messages.
     * Any messages sent or received while paused will be queued until the transport is resumed.
     */
    public function pause(): void;

    /**
     * Starts the transport listening for and transmitting messages.
     * Any messages sent or received queued up while paused will be processed.
     */
    public function resume(): void;

    /**
     * Sends a message to the remote end of the transport.
     *
     * @param MessageType $type Message type to send.
     * @param array<mixed> $payload Message payload.
     */
    public function send(MessageType $type, array $payload): void;

    /**
     * Stops the transport from listening for incoming messages.
     */
    public function stop(): void;
}
