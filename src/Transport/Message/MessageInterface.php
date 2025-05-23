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

namespace Nytris\Rpc\Transport\Message;

/**
 * Interface MessageInterface.
 *
 * @author Dan Phillimore <dan@ovms.co>
 */
interface MessageInterface
{
    /**
     * Fetches the message payload.
     *
     * @return array<mixed>
     */
    public function getPayload(): array;

    /**
     * Fetches the type of message.
     */
    public function getType(): MessageType;
}
