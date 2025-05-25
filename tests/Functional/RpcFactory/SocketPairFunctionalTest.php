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

namespace Nytris\Rpc\Tests\Functional\RpcFactory;

use Nytris\Rpc\Exception\ProxyException;
use Nytris\Rpc\Handler\HandlerInterface;
use Nytris\Rpc\Handler\HandlerTrait;
use Nytris\Rpc\RpcFactory;
use Nytris\Rpc\RpcInterface;
use Nytris\Rpc\Tests\AbstractTestCase;
use Nytris\Rpc\Tests\Functional\Fixtures\TestSerialisableException;
use React\Stream\ReadableResourceStream;
use React\Stream\WritableResourceStream;
use RuntimeException;
use Tasque\EventLoop\TasqueEventLoop;

/**
 * Class SocketPairFunctionalTest.
 *
 * Tests RPC flow from end to end using the low-level PHP ext-sockets API
 * via socket_create_pair(...).
 *
 * @author Dan Phillimore <dan@ovms.co>
 */
class SocketPairFunctionalTest extends AbstractTestCase
{
    private RpcInterface $clientRpc;
    private RpcFactory $factory;
    private HandlerInterface $handler;
    private RpcInterface $serverRpc;

    public function setUp(): void
    {
        $this->factory = new RpcFactory();

        $this->handler = new class implements HandlerInterface {
            use HandlerTrait;

            public function greet(string $name): string
            {
                return "Hello, $name!";
            }

            public function add(int $a, int $b): int
            {
                return $a + $b;
            }

            public function failSerialisable(): void
            {
                throw new TestSerialisableException('Bang!');
            }

            public function failNonSerialisable(): void
            {
                throw new RuntimeException('Bang!', 123);
            }
        };

        // Create a pair of connected sockets for bidirectional communication.
        $sockets = [];

        if (!socket_create_pair(AF_UNIX, SOCK_STREAM, 0, $sockets)) {
            $this->fail('Failed to create socket pair: ' . socket_strerror(socket_last_error()));
        }

        socket_set_nonblock($sockets[0]);
        socket_set_nonblock($sockets[1]);

        // Convert sockets to PHP stream resources.
        $clientSocketStream = socket_export_stream($sockets[0]);
        $serverSocketStream = socket_export_stream($sockets[1]);

        // Create ReactPHP stream wrappers around the socket streams.
        $clientInputStream = new ReadableResourceStream($clientSocketStream);
        $clientOutputStream = new WritableResourceStream($clientSocketStream);
        $serverInputStream = new ReadableResourceStream($serverSocketStream);
        $serverOutputStream = new WritableResourceStream($serverSocketStream);

        $this->serverRpc = $this->factory->createStreamRpc($serverInputStream, $serverOutputStream, [
            $this->handler
        ]);
        $this->clientRpc = $this->factory->createStreamRpc($clientInputStream, $clientOutputStream);
    }

    public function testCanCallRemoteMethodWithStringArgumentsUsingSockets(): void
    {
        $this->serverRpc->start();
        $this->clientRpc->start();

        $promise = $this->clientRpc->call($this->handler::class, 'greet', ['Socket User']);

        static::assertSame('Hello, Socket User!', TasqueEventLoop::await($promise));
    }

    public function testCanCallRemoteMethodWithIntegerArgumentsUsingSockets(): void
    {
        $this->serverRpc->start();
        $this->clientRpc->start();

        $promise = $this->clientRpc->call($this->handler::class, 'add', [10, 15]);

        static::assertSame(25, TasqueEventLoop::await($promise));
    }

    public function testCanHandleUndefinedMethodErrorUsingSockets(): void
    {
        $this->serverRpc->start();
        $this->clientRpc->start();

        $this->expectException(ProxyException::class);
        $this->expectExceptionMessage(
            'RPC exception :: BadMethodCallException: Method "nonexistentMethod" is not defined by handler'
        );

        $promise = $this->clientRpc->call($this->handler::class, 'nonexistentMethod', []);
        TasqueEventLoop::await($promise);
    }

    public function testCanHandleSerialisableExceptionUsingSockets(): void
    {
        $this->serverRpc->start();
        $this->clientRpc->start();

        $this->expectException(TestSerialisableException::class);
        $this->expectExceptionMessage('Bang!');

        $promise = $this->clientRpc->call($this->handler::class, 'failSerialisable', []);
        TasqueEventLoop::await($promise);
    }
}
