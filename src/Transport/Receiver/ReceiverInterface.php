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

namespace Nytris\Rpc\Transport\Receiver;

use Nytris\Rpc\Exception\RpcExceptionInterface;
use Nytris\Rpc\Transport\Message\MessageType;

/**
 * Interface ReceiverInterface.
 *
 * Handles messages received from a remote endpoint.
 *
 * @author Dan Phillimore <dan@ovms.co>
 */
interface ReceiverInterface
{
    /**
     * Stops the receiver from handling messages.
     * Any messages received while paused will be queued until the receiver is resumed.
     */
    public function pause(): void;

    /**
     * Receives a message from a remote endpoint.
     *
     * @param MessageType $type Message type received.
     * @param array<mixed> $payload Message payload.
     * @throws RpcExceptionInterface
     */
    public function receive(MessageType $type, array $payload): void;

    /**
     * Starts the receiver listening for messages.
     * Any messages received queued up while paused will be processed.
     */
    public function resume(): void;
}
