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
 * Class Message.
 *
 * @author Dan Phillimore <dan@ovms.co>
 */
class Message implements MessageInterface
{
    /**
     * @param MessageType $type
     * @param array<mixed> $payload
     */
    public function __construct(
        private readonly MessageType $type,
        private readonly array $payload
    ) {
    }

    /**
     * @inheritDoc
     */
    public function getPayload(): array
    {
        return $this->payload;
    }

    /**
     * @inheritDoc
     */
    public function getType(): MessageType
    {
        return $this->type;
    }
}
