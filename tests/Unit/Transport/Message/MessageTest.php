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

namespace Nytris\Rpc\Tests\Unit\Transport\Message;

use Nytris\Rpc\Tests\AbstractTestCase;
use Nytris\Rpc\Transport\Message\Message;
use Nytris\Rpc\Transport\Message\MessageType;

/**
 * Class MessageTest.
 *
 * @author Dan Phillimore <dan@ovms.co>
 */
class MessageTest extends AbstractTestCase
{
    private Message $message;

    public function setUp(): void
    {
        $this->message = new Message(MessageType::CALL, ['key1' => 'value1', 'key2' => 'value2']);
    }

    public function testGetPayloadReturnsProvidedPayload(): void
    {
        static::assertSame(
            ['key1' => 'value1', 'key2' => 'value2'],
            $this->message->getPayload()
        );
    }

    public function testGetTypeReturnsProvidedType(): void
    {
        static::assertSame(MessageType::CALL, $this->message->getType());
    }
}
