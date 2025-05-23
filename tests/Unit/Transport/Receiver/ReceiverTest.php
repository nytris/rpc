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

namespace Nytris\Rpc\Tests\Unit\Transport\Receiver;

use Exception;
use Mockery;
use Mockery\MockInterface;
use Nytris\Rpc\Call\CallTableInterface;
use Nytris\Rpc\Dispatcher\DispatcherInterface;
use Nytris\Rpc\Tests\AbstractTestCase;
use Nytris\Rpc\Transport\Message\MessageType;
use Nytris\Rpc\Transport\Receiver\Receiver;
use Nytris\Rpc\Transport\Transmitter\TransmitterInterface;
use React\Promise\PromiseInterface;

/**
 * Class ReceiverTest.
 *
 * @author Dan Phillimore <dan@ovms.co>
 */
class ReceiverTest extends AbstractTestCase
{
    private MockInterface&CallTableInterface $callTable;
    private MockInterface&DispatcherInterface $dispatcher;
    private Receiver $receiver;
    private MockInterface&TransmitterInterface $transmitter;

    public function setUp(): void
    {
        $this->callTable = mock(CallTableInterface::class);
        $this->dispatcher = mock(DispatcherInterface::class);
        $this->transmitter = mock(TransmitterInterface::class, [
            'transmit' => null,
        ]);

        $this->receiver = new Receiver(
            $this->callTable,
            $this->dispatcher,
            $this->transmitter
        );
    }

    public function testPauseCausesCallMessagesToBeQueued(): void
    {
        $this->receiver->resume();
        $this->receiver->receive(MessageType::CALL, [
            'callId' => 123,
            'handlerFqcn' => 'MyHandler',
            'method' => 'myMethod',
            'args' => ['first'],
        ]);

        $this->dispatcher->expects('dispatch')
            ->never();
        $this->transmitter->expects('transmit')
            ->never();

        $this->receiver->pause();
        $this->receiver->receive(MessageType::CALL, [
            'callId' => 123,
            'handlerFqcn' => 'MyHandler',
            'method' => 'myMethod',
            'args' => ['second'],
        ]);

        $this->dispatcher->expects()
            ->dispatch('MyHandler', 'myMethod', ['second'])
            ->once()
            ->andReturn('my result');
        $this->transmitter->expects()
            ->transmit(MessageType::RETURN, [
                'callId' => 123,
                'returnValue' => 'my result',
            ])
            ->once();

        $this->receiver->resume();
    }

    public function testReceiveHandlesCallMessagesCorrectly(): void
    {
        $this->receiver->resume();

        $this->dispatcher->expects()
            ->dispatch('MyHandler', 'myMethod', ['arg1', 'arg2'])
            ->once()
            ->andReturn('my result');
        $this->transmitter->expects('transmit')
            ->with(
                Mockery::on(fn ($type) => $type === MessageType::RETURN),
                ['callId' => 123, 'returnValue' => 'my result']
            )
            ->once();

        $this->receiver->receive(MessageType::CALL, [
            'callId' => 123,
            'handlerFqcn' => 'MyHandler',
            'method' => 'myMethod',
            'args' => ['arg1', 'arg2']
        ]);
    }

    public function testReceiveHandlesCallMessagesThatCauseExceptionsToBeRaised(): void
    {
        $this->receiver->resume();
        $exception = new Exception('Something went wrong');

        $this->dispatcher->expects()
            ->dispatch('MyHandler', 'failingMethod', [])
            ->once()
            ->andThrow($exception);
        $this->transmitter->expects('transmit')
            ->with(
                Mockery::on(fn ($type) => $type === MessageType::ERROR),
                ['callId' => 456, 'exception' => $exception]
            )
            ->once();

        $this->receiver->receive(MessageType::CALL, [
            'callId' => 456,
            'handlerFqcn' => 'MyHandler',
            'method' => 'failingMethod',
            'args' => []
        ]);
    }

    public function testReceiveHandlesCallMessagesWithEventuallyResolvedPromiseReturnedFromDispatcher(): void
    {
        $this->receiver->resume();
        $promise = mock(PromiseInterface::class);
        $resolvedValue = 'resolved value';

        $promise->expects('then')
            ->andReturnUsing(function (callable $resolve) use ($promise, $resolvedValue) {
                $resolve($resolvedValue);

                return $promise;
            })
            ->once();
        $this->dispatcher->expects()
            ->dispatch('MyHandler', 'promiseMethod', ['arg1'])
            ->once()
            ->andReturn($promise);
        $this->transmitter->expects('transmit')
            ->with(
                Mockery::on(fn ($type) => $type === MessageType::RETURN),
                ['callId' => 789, 'returnValue' => $resolvedValue]
            )
            ->once();

        $this->receiver->receive(MessageType::CALL, [
            'callId' => 789,
            'handlerFqcn' => 'MyHandler',
            'method' => 'promiseMethod',
            'args' => ['arg1']
        ]);
    }

    public function testReceiveHandlesCallMessagesWithEventuallyRejectedPromiseReturnedFromDispatcher(): void
    {
        $this->receiver->resume();
        $promise = mock(PromiseInterface::class);
        $exception = new Exception('Bang!');

        $promise->expects('then')
            ->andReturnUsing(function (callable $resolve, callable $reject) use ($promise, $exception) {
                $reject($exception);

                return $promise;
            })
            ->once();
        $this->dispatcher->expects()
            ->dispatch('MyHandler', 'promiseMethod', ['arg1'])
            ->once()
            ->andReturn($promise);
        $this->transmitter->expects('transmit')
            ->with(
                Mockery::on(fn ($type) => $type === MessageType::ERROR),
                ['callId' => 789, 'exception' => $exception]
            )
            ->once();

        $this->receiver->receive(MessageType::CALL, [
            'callId' => 789,
            'handlerFqcn' => 'MyHandler',
            'method' => 'promiseMethod',
            'args' => ['arg1']
        ]);
    }

    public function testReceiveHandlesReturnMessagesCorrectly(): void
    {
        $this->receiver->resume();

        $this->callTable->expects()
            ->return(123, 'return value')
            ->once();
        $this->dispatcher->expects('dispatch')
            ->never();
        $this->transmitter->expects('transmit')
            ->never();

        $this->receiver->receive(MessageType::RETURN, [
            'callId' => 123,
            'returnValue' => 'return value'
        ]);
    }

    public function testReceiveHandlesErrorMessages(): void
    {
        $this->receiver->resume();
        $exception = new Exception('Something went wrong');

        $this->callTable->expects()
            ->throw(456, $exception)
            ->once();
        $this->dispatcher->expects('dispatch')
            ->never();
        $this->transmitter->expects('transmit')
            ->never();

        $this->receiver->receive(MessageType::ERROR, [
            'callId' => 456,
            'exception' => $exception
        ]);
    }

    public function testResumeSendsNoMessagesWhenNoneYetQueued(): void
    {
        $this->dispatcher->expects('dispatch')
            ->never();
        $this->transmitter->expects('transmit')
            ->never();
        $this->callTable->expects('return')
            ->never();
        $this->callTable->expects('throw')
            ->never();

        $this->receiver->resume();
    }

    public function testResumeProcessesQueuedMessagesInOrder(): void
    {
        $this->receiver->pause();
        $this->receiver->receive(MessageType::CALL, [
            'callId' => 1,
            'handlerFqcn' => 'FirstHandler',
            'method' => 'first',
            'args' => [1],
        ]);
        $this->receiver->receive(MessageType::CALL, [
            'callId' => 2,
            'handlerFqcn' => 'SecondHandler',
            'method' => 'second',
            'args' => [2],
        ]);

        $this->dispatcher->expects()
            ->dispatch('FirstHandler', 'first', [1])
            ->once()
            ->globally()->ordered()
            ->andReturn('my first result');
        $this->transmitter->expects('transmit')
            ->with(
                Mockery::on(fn ($type) => $type === MessageType::RETURN),
                ['callId' => 1, 'returnValue' => 'my first result']
            )
            ->once()
            ->globally()->ordered();
        $this->dispatcher->expects()
            ->dispatch('SecondHandler', 'second', [2])
            ->once()
            ->globally()->ordered()
            ->andReturn('my second result');
        $this->transmitter->expects('transmit')
            ->with(
                Mockery::on(fn ($type) => $type === MessageType::RETURN),
                ['callId' => 2, 'returnValue' => 'my second result']
            )
            ->once()
            ->globally()->ordered();

        $this->receiver->resume();
    }

    public function testResumeClearsQueueAfterProcessing(): void
    {
        $this->receiver->pause();
        $this->receiver->receive(MessageType::CALL, [
            'callId' => 123,
            'handlerFqcn' => 'MyHandler',
            'method' => 'myMethod',
            'args' => [],
        ]);

        $this->dispatcher->expects('dispatch')
            ->once();
        $this->transmitter->expects('transmit')
            ->once();

        $this->receiver->resume();
        $this->receiver->resume(); // Second resume should not process messages again.
    }
}
