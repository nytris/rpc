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

namespace Nytris\Rpc\Tests\Unit\Transport\Stream;

use Mockery;
use Mockery\MockInterface;
use Nytris\Rpc\Tests\AbstractTestCase;
use Nytris\Rpc\Transport\Stream\StreamContext;
use React\Stream\ReadableStreamInterface;
use React\Stream\WritableStreamInterface;

/**
 * Class StreamContextTest.
 *
 * @author Dan Phillimore <dan@ovms.co>
 */
class StreamContextTest extends AbstractTestCase
{
    private MockInterface&ReadableStreamInterface $inputStream;
    private MockInterface&WritableStreamInterface $outputStream;
    private StreamContext $streamContext;

    public function setUp(): void
    {
        $this->inputStream = Mockery::mock(ReadableStreamInterface::class);
        $this->outputStream = Mockery::mock(WritableStreamInterface::class);

        $this->streamContext = new StreamContext();
    }

    public function testGetInputStreamReturnsTheStoredInputStream(): void
    {
        $this->streamContext->useStreams($this->inputStream, $this->outputStream);

        static::assertSame($this->inputStream, $this->streamContext->getInputStream());
    }

    public function testGetOutputStreamReturnsTheStoredOutputStream(): void
    {
        $this->streamContext->useStreams($this->inputStream, $this->outputStream);

        static::assertSame($this->outputStream, $this->streamContext->getOutputStream());
    }

    public function testUseStreamsStoresTheStreams(): void
    {
        $this->streamContext->useStreams($this->inputStream, $this->outputStream);

        static::assertSame($this->inputStream, $this->streamContext->getInputStream());
        static::assertSame($this->outputStream, $this->streamContext->getOutputStream());
    }
}
