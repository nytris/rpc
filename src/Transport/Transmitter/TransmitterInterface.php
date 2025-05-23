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

namespace Nytris\Rpc\Transport\Transmitter;

use Nytris\Rpc\Transport\Message\MessageType;

/**
 * Interface TransmitterInterface.
 *
 * Handles sending messages to a remote endpoint.
 *
 * @author Dan Phillimore <dan@ovms.co>
 */
interface TransmitterInterface
{
    /**
     * Stops the transmitter from transmitting messages.
     * Any messages sent while paused will be queued until the transmitter is resumed.
     */
    public function pause(): void;

    /**
     * Starts the transmitter transmitting messages.
     * Any messages sent queued up while paused will be processed.
     */
    public function resume(): void;

    /**
     * Sends a message to a remote endpoint.
     *
     * @param MessageType $type Message type to send.
     * @param array<mixed> $payload Message payload.
     */
    public function transmit(MessageType $type, array $payload): void;
}
