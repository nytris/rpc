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

namespace Nytris\Rpc\Tests\Unit\Transport\Listener;

use Mockery;
use Mockery\MockInterface;
use Nytris\Rpc\Tests\AbstractTestCase;
use Nytris\Rpc\Transport\Frame\FramingProtocolInterface;
use Nytris\Rpc\Transport\Listener\StreamListener;
use Nytris\Rpc\Transport\Message\Message;
use Nytris\Rpc\Transport\Message\MessageType;
use Nytris\Rpc\Transport\Receiver\ReceiverInterface;
use Nytris\Rpc\Transport\Stream\StreamContextInterface;
use React\Stream\ReadableStreamInterface;

/**
 * Class StreamListenerTest.
 *
 * @author Dan Phillimore <dan@ovms.co>
 */
class StreamListenerTest extends AbstractTestCase
{
    private MockInterface&FramingProtocolInterface $framingProtocol;
    private MockInterface&ReadableStreamInterface $inputStream;
    private MockInterface&ReceiverInterface $receiver;
    private MockInterface&StreamContextInterface $streamContext;
    private StreamListener $streamListener;

    public function setUp(): void
    {
        $this->framingProtocol = Mockery::mock(FramingProtocolInterface::class);
        $this->inputStream = Mockery::mock(ReadableStreamInterface::class);
        $this->receiver = Mockery::mock(ReceiverInterface::class);
        $this->streamContext = Mockery::mock(StreamContextInterface::class, [
            'getInputStream' => $this->inputStream,
        ]);

        $this->streamListener = new StreamListener(
            $this->streamContext,
            $this->framingProtocol
        );
    }

    public function testListenSetsUpDataHandlerOnInputStream(): void
    {
        $this->inputStream->expects('on')
            ->with('data', Mockery::type('callable'))
            ->once();

        $this->streamListener->listen($this->receiver);
    }

    public function testStopRemovesDataListenerFromInputStream(): void
    {
        $onDataCallback = null;

        $this->inputStream->expects('on')
            ->with('data', Mockery::type('callable'))
            ->once()
            ->andReturnUsing(function ($event, $callback) use (&$onDataCallback) {
                $onDataCallback = $callback;
            });

        $this->streamListener->listen($this->receiver);

        $this->inputStream->expects('removeListener')
            ->with('data', $onDataCallback)
            ->once();

        $this->streamListener->stop();
    }

    public function testListenDataHandlerProcessesFramesAndPassesToReceiverWhenAllAvailableInOneChunk(): void
    {
        $message = new Message(MessageType::CALL, ['callId' => 123, 'method' => 'myMethod']);
        $onDataCallback = null;
        $this->inputStream->allows('on')
            ->with('data', Mockery::type('callable'))
            ->andReturnUsing(function ($event, $callback) use (&$onDataCallback) {
                $onDataCallback = $callback;
            });

        $this->streamListener->listen($this->receiver);

        $this->framingProtocol->expects('hasFrame')
            ->with('my buffer data')
            ->once()
            ->andReturnTrue();
        $this->framingProtocol->expects('hasFrame')
            ->once()
            ->andReturnFalse();
        $this->framingProtocol->expects('parseFrame')
            ->with('my buffer data')
            ->once()
            ->andReturn($message);
        $this->receiver->expects('receive')
            ->with(MessageType::CALL, ['callId' => 123, 'method' => 'myMethod'])
            ->once();

        $onDataCallback('my buffer data');
    }

    public function testListenDataHandlerProcessesFramesAndPassesToReceiverWhenAllAvailableAcrossMultipleChunks(): void
    {
        $message = new Message(MessageType::CALL, ['callId' => 123, 'method' => 'myMethod']);
        $onDataCallback = null;
        $this->inputStream->allows('on')
            ->with('data', Mockery::type('callable'))
            ->andReturnUsing(function ($event, $callback) use (&$onDataCallback) {
                $onDataCallback = $callback;
            });

        $this->streamListener->listen($this->receiver);

        $this->framingProtocol->expects('hasFrame')
            ->with('my ')
            ->once()
            ->andReturnFalse();
        $this->framingProtocol->expects('hasFrame')
            ->with('my buffer ')
            ->once()
            ->andReturnFalse();
        $this->framingProtocol->expects('hasFrame')
            ->with('my buffer data')
            ->once()
            ->andReturnTrue();
        $this->framingProtocol->expects('hasFrame')
            ->with('') // As frame was consumed.
            ->once()
            ->andReturnFalse();
        $this->framingProtocol->expects('parseFrame')
            ->with('my buffer data')
            ->once()
            ->andReturnUsing(function (string &$buffer) use ($message) {
                $buffer = ''; // Simulate the frame being consumed.

                return $message;
            });
        $this->receiver->expects('receive')
            ->with(MessageType::CALL, ['callId' => 123, 'method' => 'myMethod'])
            ->once();

        $onDataCallback('my ');
        $onDataCallback('buffer ');
        $onDataCallback('data');
    }

    public function testListenDataHandlerProcessesMultipleFramesInOneChunk(): void
    {
        $message1 = new Message(MessageType::CALL, ['callId' => 123, 'method' => 'method1']);
        $message2 = new Message(MessageType::RETURN, ['callId' => 456, 'returnValue' => 'result']);
        $dataCallback = null;
        $this->inputStream->allows('on')
            ->with('data', Mockery::type('callable'))
            ->andReturnUsing(function ($event, $callback) use (&$dataCallback) {
                $dataCallback = $callback;
            });

        $this->streamListener->listen($this->receiver);

        // Simulate receiving data with multiple frames.
        $this->framingProtocol->expects('hasFrame')
            ->with(Mockery::type('string'))
            ->times(3)
            ->andReturn(true, true, false);
        $this->framingProtocol->expects('parseFrame')
            ->with(Mockery::type('string'))
            ->twice()
            ->andReturn($message1, $message2);
        $this->receiver->expects('receive')
            ->with(MessageType::CALL, ['callId' => 123, 'method' => 'method1'])
            ->once();
        $this->receiver->expects('receive')
            ->with(MessageType::RETURN, ['callId' => 456, 'returnValue' => 'result'])
            ->once();

        $dataCallback('data with multiple frames');
    }

    public function testListenDataHandlerAccumulatesDataUntilCompleteFrame(): void
    {
        $message = new Message(MessageType::CALL, ['callId' => 123, 'method' => 'testMethod']);
        $dataCallback = null;
        $this->inputStream->allows('on')
            ->with('data', Mockery::type('callable'))
            ->andReturnUsing(function ($event, $callback) use (&$dataCallback) {
                $dataCallback = $callback;
            });

        $this->streamListener->listen($this->receiver);

        // First chunk doesn't have a complete frame.
        $this->framingProtocol->expects('hasFrame')
            ->once()
            ->andReturn(false);

        $dataCallback('first chunk');

        // Second chunk completes the frame.
        $this->framingProtocol->expects('hasFrame')
            ->twice()
            ->andReturn(true, false);
        $this->framingProtocol->expects('parseFrame')
            ->once()
            ->andReturn($message);
        $this->receiver->expects('receive')
            ->with(MessageType::CALL, ['callId' => 123, 'method' => 'testMethod'])
            ->once();

        // Call the captured callback with second chunk.
        $dataCallback('second chunk');
    }
}
