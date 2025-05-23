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

use Mockery\MockInterface;
use Nytris\Rpc\Call\CallTableInterface;
use Nytris\Rpc\Rpc;
use Nytris\Rpc\Tests\AbstractTestCase;
use Nytris\Rpc\Transport\Message\MessageType;
use Nytris\Rpc\Transport\TransportInterface;
use Tasque\EventLoop\TasqueEventLoop;

/**
 * Class RpcTest.
 *
 * @author Dan Phillimore <dan@ovms.co>
 */
class RpcTest extends AbstractTestCase
{
    private MockInterface&CallTableInterface $callTable;
    private Rpc $rpc;
    private MockInterface&TransportInterface $transport;

    public function setUp(): void
    {
        $this->callTable = mock(CallTableInterface::class);
        $this->transport = mock(TransportInterface::class);

        $this->rpc = new Rpc($this->callTable, $this->transport);
    }

    public function testCallSendsCallMessageReturningPromise(): void
    {
        /** @var class-string $handlerFqcn */
        $handlerFqcn = 'My\\Handler\\Class';
        $method = 'myMethod';
        $args = ['arg1' => 'value1', 'arg2' => 'value2'];
        $callId = 21;
        $this->callTable->allows('addCall')
            ->andReturnUsing(function (callable $resolve, callable $reject) use ($callId) : int {
                $resolve('my result');

                return $callId;
            });

        $this->transport->expects('send')
            ->with(MessageType::CALL, [
                'callId' => $callId,
                'handlerFqcn' => $handlerFqcn,
                'method' => $method,
                'args' => $args,
            ])
            ->once();

        static::assertSame(
            'my result',
            TasqueEventLoop::await($this->rpc->call($handlerFqcn, $method, $args))
        );
    }

    public function testStartCallsListenAndResumeOnTransport(): void
    {
        $this->transport->expects('listen')
            ->once()
            ->ordered();
        $this->transport->expects('resume')
            ->once()
            ->ordered();

        $this->rpc->start();
    }

    public function testStopCallsPauseAndStopOnTransport(): void
    {
        $this->transport->expects('pause')
            ->once()
            ->ordered();
        $this->transport->expects('stop')
            ->once()
            ->ordered();

        $this->rpc->stop();
    }
}
