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

namespace Nytris\Rpc\Tests\Functional;

use BadMethodCallException;
use Nytris\Rpc\Exception\ProxyException;
use Nytris\Rpc\Handler\HandlerInterface;
use Nytris\Rpc\RpcFactory;
use Nytris\Rpc\RpcInterface;
use Nytris\Rpc\Tests\AbstractTestCase;
use Nytris\Rpc\Tests\Functional\Fixtures\TestSerialisableException;
use React\Stream\ThroughStream;
use RuntimeException;
use Tasque\EventLoop\TasqueEventLoop;
use Throwable;

/**
 * Class RpcFactoryFunctionalTest.
 *
 * Tests RPC flow from end to end using a stream transport.
 *
 * @author Dan Phillimore <dan@ovms.co>
 */
class RpcFactoryStreamFunctionalTest extends AbstractTestCase
{
    private RpcInterface $clientRpc;
    private RpcFactory $factory;
    private HandlerInterface $handler;
    private RpcInterface $serverRpc;

    public function setUp(): void
    {
        $this->factory = new RpcFactory();

        // Create bidirectional streams for testing.
        $clientToServer = new ThroughStream();
        $serverToClient = new ThroughStream();

        $this->handler = new class implements HandlerInterface {
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

            public function onUndefinedMethod(string $method, array $args): mixed
            {
                throw new BadMethodCallException("Method '$method' not found");
            }
        };

        $this->serverRpc = $this->factory->createStreamRpc($clientToServer, $serverToClient, [
            $this->handler
        ]);
        $this->clientRpc = $this->factory->createStreamRpc($serverToClient, $clientToServer);
    }

    public function testCanCallRemoteMethodWithStringArguments(): void
    {
        $this->serverRpc->start();
        $this->clientRpc->start();

        $promise = $this->clientRpc->call($this->handler::class, 'greet', ['World']);

        static::assertSame('Hello, World!', TasqueEventLoop::await($promise));
    }

    public function testCanCallRemoteMethodWithIntegerArguments(): void
    {
        $this->serverRpc->start();
        $this->clientRpc->start();

        $promise = $this->clientRpc->call($this->handler::class, 'add', [5, 7]);

        static::assertSame(12, TasqueEventLoop::await($promise));
    }

    public function testCallsWhileStoppedAreQueuedAndSentWhenStarted(): void
    {
        $promise = $this->clientRpc->call($this->handler::class, 'greet', ['World']);

        $this->serverRpc->start();
        $this->clientRpc->start();

        static::assertSame('Hello, World!', TasqueEventLoop::await($promise));
    }

    public function testNonSerialisableExceptionsDuringCallHandlingAreReturnedAndThrownAsProxyExceptions(): void
    {
        $capturedException = null;
        $this->serverRpc->start();
        $this->clientRpc->start();

        $promise = $this->clientRpc->call($this->handler::class, 'failNonSerialisable');

        try {
            TasqueEventLoop::await($promise);
        } catch (Throwable $exception) {
            $capturedException = $exception;
        }

        static::assertInstanceOf(ProxyException::class, $capturedException);
        static::assertSame('RPC exception :: RuntimeException: Bang!', $capturedException->getMessage());
        static::assertSame('RuntimeException', $capturedException->getOriginalClass());
        static::assertSame('Bang!', $capturedException->getOriginalMessage());
        static::assertSame(123, $capturedException->getOriginalCode());
        static::assertSame(__FILE__, $capturedException->getOriginalFile());
        static::assertSame(68, $capturedException->getOriginalLine());
    }

    public function testSerialisableExceptionsDuringCallHandlingAreReturnedAndThrown(): void
    {
        $capturedException = null;
        $this->serverRpc->start();
        $this->clientRpc->start();

        $promise = $this->clientRpc->call($this->handler::class, 'failSerialisable');

        try {
            TasqueEventLoop::await($promise);
        } catch (Throwable $exception) {
            $capturedException = $exception;
        }

        static::assertInstanceOf(TestSerialisableException::class, $capturedException);
        // Ensure the deserialisation hook was used.
        static::assertSame('Bang! (deserialised)', $capturedException->getMessage());
    }
}
