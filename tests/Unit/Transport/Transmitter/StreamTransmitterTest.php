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
use Nytris\Rpc\Transport\Frame\FramingProtocolInterface;
use Nytris\Rpc\Transport\Message\MessageInterface;
use Nytris\Rpc\Transport\Message\MessageType;
use Nytris\Rpc\Transport\Stream\StreamContextInterface;
use Nytris\Rpc\Transport\Transmitter\StreamTransmitter;
use React\Stream\WritableStreamInterface;

/**
 * Class StreamTransmitterTest.
 *
 * @author Dan Phillimore <dan@ovms.co>
 */
class StreamTransmitterTest extends AbstractTestCase
{
    private MockInterface&FramingProtocolInterface $framingProtocol;
    private MockInterface&WritableStreamInterface $outputStream;
    private MockInterface&StreamContextInterface $streamContext;
    private StreamTransmitter $streamTransmitter;

    public function setUp(): void
    {
        $this->framingProtocol = mock(FramingProtocolInterface::class);
        $this->outputStream = mock(WritableStreamInterface::class, [
            'write' => true,
        ]);
        $this->streamContext = mock(StreamContextInterface::class, [
            'getOutputStream' => $this->outputStream,
        ]);

        $this->framingProtocol->shouldReceive('frameMessage')
            ->andReturnUsing(function (MessageInterface $message) {
                return json_encode([
                    'type' => $message->getType(),
                    'data' => $message->getPayload(),
                ]);
            })
            ->byDefault();

        $this->streamTransmitter = new StreamTransmitter($this->streamContext, $this->framingProtocol);
    }

    public function testPauseCausesMessagesToBeQueued(): void
    {
        $this->streamTransmitter->resume();
        $this->streamTransmitter->transmit(MessageType::CALL, ['foo' => 'bar']);

        $this->outputStream->expects('write')
            ->never();

        $this->streamTransmitter->pause();
        $this->streamTransmitter->transmit(MessageType::CALL, ['foo' => 'bar']);

        $this->outputStream->expects()
            ->write('{"type":"call","data":{"foo":"bar"}}')
            ->once();

        $this->streamTransmitter->resume();
    }

    public function testResumeWritesNoMessagesToOutputStreamWhenNoneYetQueued(): void
    {
        $this->framingProtocol->expects('frameMessage')
            ->never();
        $this->outputStream->expects('write')
            ->never();

        $this->streamTransmitter->resume();
    }

    public function testResumeWritesCorrectlyFramedSingleQueuedCallMessageToOutputStreamWhenResumed(): void
    {
        $this->streamTransmitter->transmit(MessageType::CALL, ['foo' => 'bar']);

        $this->outputStream->expects()
            ->write('{"type":"call","data":{"foo":"bar"}}')
            ->once();

        $this->streamTransmitter->resume();
    }

    public function testResumeWritesCorrectlyFramedMultipleQueuedCallMessagesToOutputStreamWhenResumed(): void
    {
        $this->streamTransmitter->transmit(MessageType::CALL, ['first' => 'one']);
        $this->streamTransmitter->transmit(MessageType::CALL, ['second' => 'two']);

        $this->outputStream->expects()
            ->write('{"type":"call","data":{"first":"one"}}')
            ->once();
        $this->outputStream->expects()
            ->write('{"type":"call","data":{"second":"two"}}')
            ->once();

        $this->streamTransmitter->resume();
    }

    public function testResumeClearsQueuedMessagesAfterWritingToOutputStreamWhenResumed(): void
    {
        $this->streamTransmitter->transmit(MessageType::CALL, ['first' => 'one']);
        $this->streamTransmitter->transmit(MessageType::CALL, ['second' => 'two']);

        $this->outputStream->expects('write')
            // Messages should only be written once.
            ->twice();

        $this->streamTransmitter->resume();
        $this->streamTransmitter->resume();
    }

    public function testTransmitWritesCorrectlyFramedCallMessagesToOutputStreamWhenResumed(): void
    {
        $this->streamTransmitter->resume();

        $this->framingProtocol->expects('frameMessage')
            ->with(
                Mockery::on(
                    fn (MessageInterface $message) =>
                        $message->getType() === MessageType::CALL && $message->getPayload() === ['foo' => 'bar']
                )
            )
            ->once()
            ->andReturn('{"type":"call","data":{"foo":"bar"}}');
        $this->outputStream->expects()
            ->write('{"type":"call","data":{"foo":"bar"}}')
            ->once();

        $this->streamTransmitter->transmit(MessageType::CALL, ['foo' => 'bar']);
    }

    public function testTransmitWritesCorrectlyFramedErrorMessagesToOutputStreamWhenResumed(): void
    {
        $this->streamTransmitter->resume();

        $this->framingProtocol->expects('frameMessage')
            ->with(
                Mockery::on(
                    fn(MessageInterface $message) => $message->getType() === MessageType::ERROR && $message->getPayload() === ['error' => 'Something went wrong']
                )
            )
            ->once()
            ->andReturn('{"type":"error","data":{"error":"Something went wrong"}}');
        $this->outputStream->expects()
            ->write('{"type":"error","data":{"error":"Something went wrong"}}')
            ->once();

        $this->streamTransmitter->transmit(MessageType::ERROR, ['error' => 'Something went wrong']);
    }

    public function testTransmitWritesCorrectlyFramedReturnMessagesToOutputStreamWhenResumed(): void
    {
        $this->streamTransmitter->resume();

        $this->framingProtocol->expects('frameMessage')
            ->with(
                Mockery::on(
                    fn(MessageInterface $message) => $message->getType() === MessageType::RETURN && $message->getPayload() === ['result' => 21]
                )
            )
            ->once()
            ->andReturn('{"type":"return","data":{"result":21}}');
        $this->outputStream->expects()
            ->write('{"type":"return","data":{"result":21}}')
            ->once();

        $this->streamTransmitter->transmit(MessageType::RETURN, ['result' => 21]);
    }

    public function testTransmitDoesNotWriteToOutputStreamWhenNotYetResumed(): void
    {
        $this->framingProtocol->expects('frameMessage')
            ->never();
        $this->outputStream->expects('write')
            ->never();

        $this->streamTransmitter->transmit(MessageType::CALL, ['foo' => 'bar']);
    }
}
