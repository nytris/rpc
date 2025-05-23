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

namespace Nytris\Rpc\Transport\Frame;

use Nytris\Rpc\Transport\Message\MessageInterface;

/**
 * Interface FramingProtocolInterface.
 *
 * Defines the wire protocol for transmission, encapsulating each message in a frame.
 *
 * @author Dan Phillimore <dan@ovms.co>
 */
interface FramingProtocolInterface
{
    /**
     * Frames a message for transmission.
     */
    public function frameMessage(MessageInterface $message): string;

    /**
     * Determines whether the head of the buffer contains a complete frame yet.
     */
    public function hasFrame(string $buffer): bool;

    /**
     * Fetches the next frame from the buffer, if available.
     */
    public function parseFrame(string &$buffer): MessageInterface;
}
