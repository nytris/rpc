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

namespace Nytris\Rpc\Tests\Unit\Dispatcher;

use BadMethodCallException;
use Nytris\Rpc\Dispatcher\Dispatcher;
use Nytris\Rpc\Handler\HandlerInterface;
use Nytris\Rpc\Tests\AbstractTestCase;

/**
 * Class DispatcherTest.
 *
 * @author Dan Phillimore <dan@ovms.co>
 */
class DispatcherTest extends AbstractTestCase
{
    private Dispatcher $dispatcher;

    public function setUp(): void
    {
        $this->dispatcher = new Dispatcher();
    }

    public function testDispatchCallsMethodOnHandler(): void
    {
        $handler = new class('my expected result') implements HandlerInterface {
            public function __construct(
                private readonly string $result
            ) {
            }

            public function myMethod(string $arg1, string $arg2): string
            {
                return "args: ($arg1, $arg2) result: $this->result";
            }

            public function onUndefinedMethod(string $method, array $args): mixed
            {
                throw new BadMethodCallException("Method $method not found");
            }
        };
        $this->dispatcher->registerHandler($handler);

        $result = $this->dispatcher->dispatch($handler::class, 'myMethod', [
            'my first arg',
            'my second arg',
        ]);

        static::assertSame(
            'args: (my first arg, my second arg) result: my expected result',
            $result
        );
    }

    public function testDispatchThrowsExceptionWhenHandlerNotRegistered(): void
    {
        $this->expectException(BadMethodCallException::class);
        $this->expectExceptionMessage(
            'No handler registered for FQCN "My\\Nonexistent\\Handler" when attempting to call method "myMethod"'
        );

        $this->dispatcher->dispatch('My\\Nonexistent\\Handler', 'myMethod', []);
    }

    public function testDispatchCallsOnUndefinedMethodWhenMethodDoesNotExist(): void
    {
        $handler = new class('undefined method result') implements HandlerInterface {
            public function __construct(
                private readonly string $result
            ) {
            }

            public function onUndefinedMethod(string $method, array $args): mixed
            {
                if ($method === 'nonExistentMethod' && $args === ['my first arg', 'my second arg']) {
                    return $this->result;
                }

                throw new BadMethodCallException("Method $method not found");
            }
        };
        $this->dispatcher->registerHandler($handler);

        $result = $this->dispatcher->dispatch($handler::class, 'nonExistentMethod', [
            'my first arg',
            'my second arg',
        ]);

        static::assertSame('undefined method result', $result);
    }
}
