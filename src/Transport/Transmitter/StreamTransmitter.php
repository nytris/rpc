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

use Nytris\Rpc\Transport\Frame\FramingProtocolInterface;
use Nytris\Rpc\Transport\Message\Message;
use Nytris\Rpc\Transport\Message\MessageType;
use Nytris\Rpc\Transport\Stream\StreamContextInterface;

/**
 * Class StreamTransmitter.
 *
 * Transmits messages to a remote receiver using a ReactPHP stream.
 * Useful for inter-process communication.
 *
 * @author Dan Phillimore <dan@ovms.co>
 */
class StreamTransmitter implements TransmitterInterface
{
    /**
     * @var array{type: MessageType, payload: array<mixed>}[]
     */
    private array $messageQueue = [];
    private bool $paused = true;

    public function __construct(
        private readonly StreamContextInterface $streamContext,
        private readonly FramingProtocolInterface $framingProtocol
    ) {
    }

    /**
     * @inheritDoc
     */
    public function pause(): void
    {
        $this->paused = true;
    }

    /**
     * @inheritDoc
     */
    public function resume(): void
    {
        $this->paused = false;

        foreach ($this->messageQueue as ['type' => $type, 'payload' => $payload]) {
            $this->transmit($type, $payload);
        }

        $this->messageQueue = [];
    }

    /**
     * @inheritDoc
     */
    public function transmit(MessageType $type, array $payload): void
    {
        if ($this->paused) {
            $this->messageQueue[] = ['type' => $type, 'payload' => $payload];
            return;
        }

        $stream = $this->streamContext->getOutputStream();

        $stream->write(
            data: $this->framingProtocol->frameMessage(new Message($type, $payload))
        );
    }
}
