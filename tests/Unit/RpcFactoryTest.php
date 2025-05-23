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

namespace Nytris\Rpc\Tests\Unit;

use Nytris\Rpc\Handler\HandlerInterface;
use Nytris\Rpc\RpcFactory;
use Nytris\Rpc\RpcInterface;
use Nytris\Rpc\Tests\AbstractTestCase;
use React\Stream\ThroughStream;

/**
 * Class RpcFactoryTest.
 *
 * @author Dan Phillimore <dan@ovms.co>
 */
class RpcFactoryTest extends AbstractTestCase
{
    private RpcFactory $factory;

    public function setUp(): void
    {
        $this->factory = new RpcFactory();
    }

    public function testCreateStreamRpcWithNoHandlersReturnsRpcInstance(): void
    {
        $inputStream = new ThroughStream();
        $outputStream = new ThroughStream();

        $rpc = $this->factory->createStreamRpc($inputStream, $outputStream);

        static::assertInstanceOf(RpcInterface::class, $rpc);
    }

    public function testCreateStreamRpcWithHandlersReturnsRpcInstance(): void
    {
        $inputStream = new ThroughStream();
        $outputStream = new ThroughStream();
        $handler = mock(HandlerInterface::class);

        $rpc = $this->factory->createStreamRpc($inputStream, $outputStream, [$handler]);

        static::assertInstanceOf(RpcInterface::class, $rpc);
    }
}
