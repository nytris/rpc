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

namespace Nytris\Rpc\Tests\Unit\Transport;

use Mockery\MockInterface;
use Nytris\Rpc\Tests\AbstractTestCase;
use Nytris\Rpc\Transport\Listener\ListenerInterface;
use Nytris\Rpc\Transport\Message\MessageType;
use Nytris\Rpc\Transport\Receiver\ReceiverInterface;
use Nytris\Rpc\Transport\Transmitter\TransmitterInterface;
use Nytris\Rpc\Transport\Transport;

/**
 * Class TransportTest.
 *
 * @author Dan Phillimore <dan@ovms.co>
 */
class TransportTest extends AbstractTestCase
{
    private MockInterface&ListenerInterface $listener;
    private MockInterface&ReceiverInterface $receiver;
    private MockInterface&TransmitterInterface $transmitter;
    private Transport $transport;

    public function setUp(): void
    {
        $this->transmitter = mock(TransmitterInterface::class);
        $this->listener = mock(ListenerInterface::class);
        $this->receiver = mock(ReceiverInterface::class);

        $this->transport = new Transport(
            $this->transmitter,
            $this->listener,
            $this->receiver
        );
    }

    public function testListenDelegatesToListener(): void
    {
        $this->listener->expects()
            ->listen($this->receiver)
            ->once();

        $this->transport->listen();
    }

    public function testPauseDelegatesToTransmitterAndReceiver(): void
    {
        $this->transmitter->expects()
            ->pause()
            ->once();
        $this->receiver->expects()
            ->pause()
            ->once();

        $this->transport->pause();
    }

    public function testResumeDelegatesToTransmitterAndReceiver(): void
    {
        $this->transmitter->expects()
            ->resume()
            ->once();
        $this->receiver->expects()
            ->resume()
            ->once();

        $this->transport->resume();
    }

    public function testSendDelegatesToTransmitter(): void
    {
        $type = MessageType::CALL;
        $payload = ['key1' => 'value1', 'key2' => 'value2'];

        $this->transmitter->expects()
            ->transmit($type, $payload)
            ->once();

        $this->transport->send($type, $payload);
    }

    public function testStopDelegatesToListener(): void
    {
        $this->listener->expects()
            ->stop()
            ->once();

        $this->transport->stop();
    }
}
