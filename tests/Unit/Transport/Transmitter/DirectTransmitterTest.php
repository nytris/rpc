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

namespace Nytris\Rpc\Tests\Unit\Transport\Transmitter;

use Mockery;
use Mockery\MockInterface;
use Nytris\Rpc\Tests\AbstractTestCase;
use Nytris\Rpc\Transport\Message\MessageType;
use Nytris\Rpc\Transport\Receiver\ReceiverInterface;
use Nytris\Rpc\Transport\Transmitter\DirectTransmitter;

/**
 * Class DirectTransmitterTest.
 *
 * @author Dan Phillimore <dan@ovms.co>
 */
class DirectTransmitterTest extends AbstractTestCase
{
    private DirectTransmitter $directTransmitter;
    private MockInterface&ReceiverInterface $remoteReceiver;

    public function setUp(): void
    {
        $this->remoteReceiver = mock(ReceiverInterface::class, [
            'receive' => null,
        ]);

        $this->directTransmitter = new DirectTransmitter();
        $this->directTransmitter->directConnect($this->remoteReceiver);
    }

    public function testPauseCausesMessagesToBeQueued(): void
    {
        $this->directTransmitter->resume();
        $this->directTransmitter->transmit(MessageType::CALL, ['foo' => 'bar']);

        $this->remoteReceiver->expects('receive')
            ->never();

        $this->directTransmitter->pause();
        $this->directTransmitter->transmit(MessageType::CALL, ['foo' => 'bar']);

        $this->remoteReceiver->expects()
            ->receive(MessageType::CALL, ['foo' => 'bar'])
            ->once();

        $this->directTransmitter->resume();
    }

    public function testResumeSendsNoMessagesWhenNoneYetQueued(): void
    {
        $this->remoteReceiver->expects('receive')
            ->never();

        $this->directTransmitter->resume();
    }

    public function testResumeSendsSingleQueuedCallMessageWhenResumed(): void
    {
        $this->directTransmitter->transmit(MessageType::CALL, ['foo' => 'bar']);

        $this->remoteReceiver->expects()
            ->receive(MessageType::CALL, ['foo' => 'bar'])
            ->once();

        $this->directTransmitter->resume();
    }

    public function testResumeSendsMultipleQueuedCallMessagesWhenResumed(): void
    {
        $this->directTransmitter->transmit(MessageType::CALL, ['first' => 'one']);
        $this->directTransmitter->transmit(MessageType::CALL, ['second' => 'two']);

        $this->remoteReceiver->expects()
            ->receive(MessageType::CALL, ['first' => 'one'])
            ->once();
        $this->remoteReceiver->expects()
            ->receive(MessageType::CALL, ['second' => 'two'])
            ->once();

        $this->directTransmitter->resume();
    }

    public function testResumeClearsQueuedMessagesAfterSendingThemWhenResumed(): void
    {
        $this->directTransmitter->transmit(MessageType::CALL, ['first' => 'one']);
        $this->directTransmitter->transmit(MessageType::CALL, ['second' => 'two']);

        $this->remoteReceiver->expects('receive')
            // Messages should only be sent once
            ->twice();

        $this->directTransmitter->resume();
        $this->directTransmitter->resume();
    }

    public function testTransmitSendsCallMessagesImmediatelyWhenResumed(): void
    {
        $this->directTransmitter->resume();

        $this->remoteReceiver->expects()
            ->receive(
                Mockery::on(fn ($type) => $type === MessageType::CALL),
                ['foo' => 'bar']
            )
            ->once();

        $this->directTransmitter->transmit(MessageType::CALL, ['foo' => 'bar']);
    }

    public function testTransmitSendsErrorMessagesImmediatelyWhenResumed(): void
    {
        $this->directTransmitter->resume();

        $this->remoteReceiver->expects()
            ->receive(
                Mockery::on(fn($type) => $type === MessageType::ERROR),
                ['error' => 'Something went wrong']
            )
            ->once();

        $this->directTransmitter->transmit(MessageType::ERROR, ['error' => 'Something went wrong']);
    }

    public function testTransmitSendsReturnMessagesImmediatelyWhenResumed(): void
    {
        $this->directTransmitter->resume();

        $this->remoteReceiver->expects()
            ->receive(
                Mockery::on(fn($type) => $type === MessageType::RETURN),
                ['result' => 21]
            )
            ->once();

        $this->directTransmitter->transmit(MessageType::RETURN, ['result' => 21]);
    }

    public function testTransmitDoesNotSendMessagesWhenNotYetResumed(): void
    {
        $this->remoteReceiver->expects('receive')
            ->never();

        $this->directTransmitter->transmit(MessageType::CALL, ['foo' => 'bar']);
    }
}
