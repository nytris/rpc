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
use Nytris\Rpc\Transport\Receiver\ReceiverInterface;

/**
 * Class DirectTransmitter.
 *
 * Directly transmits messages to the remote receiver within the same process.
 * Useful for testing and debugging.
 *
 * @author Dan Phillimore <dan@ovms.co>
 */
class DirectTransmitter implements TransmitterInterface
{
    /**
     * @var array{type: MessageType, payload: array<mixed>}[]
     */
    private array $messageQueue = [];
    private bool $paused = true;
    private ReceiverInterface $remoteReceiver;

    /**
     * Connects this transmitter directly to the remote receiver.
     */
    public function directConnect(ReceiverInterface $remoteReceiver): void
    {
        $this->remoteReceiver = $remoteReceiver;
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

        $this->remoteReceiver->receive($type, $payload);
    }
}
