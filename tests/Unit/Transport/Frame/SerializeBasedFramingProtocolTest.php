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

namespace Nytris\Rpc\Tests\Unit\Transport\Frame;

use Nytris\Rpc\Exception\UnexpectedWireDataException;
use Nytris\Rpc\Tests\AbstractTestCase;
use Nytris\Rpc\Transport\Frame\SerializeBasedFramingProtocol;
use Nytris\Rpc\Transport\Message\Message;
use Nytris\Rpc\Transport\Message\MessageType;

/**
 * Class SerializeBasedFramingProtocolTest.
 *
 * @author Dan Phillimore <dan@ovms.co>
 */
class SerializeBasedFramingProtocolTest extends AbstractTestCase
{
    private SerializeBasedFramingProtocol $framingProtocol;

    public function setUp(): void
    {
        $this->framingProtocol = new SerializeBasedFramingProtocol();
    }

    public function testFrameMessageReturnsFormattedFrame(): void
    {
        $message = new Message(MessageType::CALL, ['callId' => 123, 'method' => 'myMethod']);
        $serializedMessage = serialize($message);
        $expectedFrame = '__nytris__(' . strlen($serializedMessage) . ')' . $serializedMessage . "\n";

        static::assertSame($expectedFrame, $this->framingProtocol->frameMessage($message));
    }

    public function testHasFrameReturnsFalseForEmptyBuffer(): void
    {
        static::assertFalse($this->framingProtocol->hasFrame(''));
    }

    public function testHasFrameThrowsExceptionForInvalidBufferHead(): void
    {
        $this->expectException(UnexpectedWireDataException::class);
        $this->expectExceptionMessage('Unexpected data at buffer head: invalid_buffer_head');

        $this->framingProtocol->hasFrame('invalid_buffer_head');
    }

    public function testHasFrameReturnsFalseWhenBufferTooShort(): void
    {
        $message = new Message(MessageType::CALL, ['callId' => 123]);
        $serializedMessage = serialize($message);
        $frame = '__nytris__(' . strlen($serializedMessage) . ')' . substr($serializedMessage, 0, -5);

        static::assertFalse($this->framingProtocol->hasFrame($frame));
    }

    public function testHasFrameReturnsTrueWhenBufferContainsCompleteFrame(): void
    {
        $message = new Message(MessageType::CALL, ['callId' => 123]);
        $serializedMessage = serialize($message);
        $frame = '__nytris__(' . strlen($serializedMessage) . ')' . $serializedMessage . "\n";

        static::assertTrue($this->framingProtocol->hasFrame($frame));
    }

    public function testParseFrameThrowsExceptionForInvalidBufferHead(): void
    {
        $this->expectException(UnexpectedWireDataException::class);
        $this->expectExceptionMessage('Unexpected data at buffer head: invalid_buffer_head');

        $buffer = 'invalid_buffer_head';
        $this->framingProtocol->parseFrame($buffer);
    }

    public function testParseFrameExtractsMessageAndUpdatesBuffer(): void
    {
        $message = new Message(MessageType::CALL, ['callId' => 123]);
        $serializedMessage = serialize($message);
        $frame = '__nytris__(' . strlen($serializedMessage) . ')' . $serializedMessage . "\n";
        $buffer = $frame . 'remaining data';

        $result = $this->framingProtocol->parseFrame($buffer);

        static::assertEquals($message->getType(), $result->getType());
        static::assertEquals($message->getPayload(), $result->getPayload());
        static::assertEquals('remaining data', $buffer);
    }
}
