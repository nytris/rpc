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

use Nytris\Rpc\Transport\Listener\ListenerInterface;
use Nytris\Rpc\Transport\Message\MessageType;
use Nytris\Rpc\Transport\Receiver\ReceiverInterface;
use Nytris\Rpc\Transport\Transmitter\TransmitterInterface;

/**
 * Class Transport.
 *
 * @author Dan Phillimore <dan@ovms.co>
 */
class Transport implements TransportInterface
{
    public function __construct(
        private readonly TransmitterInterface $transmitter,
        private readonly ListenerInterface $listener,
        private readonly ReceiverInterface $receiver
    ) {
    }

    /**
     * @inheritDoc
     */
    public function listen(): void
    {
        $this->listener->listen($this->receiver);
    }

    /**
     * @inheritDoc
     */
    public function pause(): void
    {
        $this->transmitter->pause();
        $this->receiver->pause();
    }

    /**
     * @inheritDoc
     */
    public function resume(): void
    {
        $this->transmitter->resume();
        $this->receiver->resume();
    }

    /**
     * @inheritDoc
     */
    public function send(MessageType $type, array $payload): void
    {
        $this->transmitter->transmit($type, $payload);
    }

    /**
     * @inheritDoc
     */
    public function stop(): void
    {
        $this->listener->stop();
    }
}
