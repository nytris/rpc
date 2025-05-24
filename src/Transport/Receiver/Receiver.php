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

namespace Nytris\Rpc\Transport\Receiver;

use Nytris\Rpc\Call\CallTableInterface;
use Nytris\Rpc\Dispatcher\DispatcherInterface;
use Nytris\Rpc\Exception\DeserialisationFailedException;
use Nytris\Rpc\Exception\ProxyException;
use Nytris\Rpc\Exception\SerialisableExceptionInterface;
use Nytris\Rpc\Transport\Message\MessageType;
use Nytris\Rpc\Transport\Transmitter\TransmitterInterface;
use React\Promise\PromiseInterface;
use Throwable;

/**
 * Class Receiver.
 *
 * Handles messages received from a remote endpoint.
 *
 * If the incoming message is a return value or error for an RPC call originating
 * from this end, then the promise for the call will be resolved/rejected accordingly.
 *
 * If the incoming message is for an RPC call originating from the remote end,
 * then the dispatcher will be used to dispatch the call to the appropriate handler.
 *
 * @author Dan Phillimore <dan@ovms.co>
 */
class Receiver implements ReceiverInterface
{
    /**
     * @var array{type: MessageType, payload: array<mixed>}[]
     */
    private array $messageQueue = [];
    private bool $paused = true;

    public function __construct(
        private readonly CallTableInterface $callTable,
        private readonly DispatcherInterface $dispatcher,
        private readonly TransmitterInterface $transmitter
    ) {
    }

    /**
     * @inheritDoc
     */
    public function pause(): void
    {
        $this->paused = true;
    }

    /**
     * @inheritDoc
     */
    public function receive(MessageType $type, array $payload): void
    {
        if ($this->paused) {
            $this->messageQueue[] = ['type' => $type, 'payload' => $payload];
            return;
        }

        switch ($type) {
            case MessageType::CALL:
                $callId = (int) $payload['callId'];

                // A call was received from the remote endpoint, that should be dispatched locally.
                try {
                    $returnValue = $this->dispatcher->dispatch(
                        $payload['handlerFqcn'],
                        $payload['method'],
                        $payload['args']
                    );
                } catch (Throwable $exception) {
                    $this->returnError($callId, $exception);
                    return;
                }

                if ($returnValue instanceof PromiseInterface) {
                    // $returnValue is a promise, so await the final result.
                    $returnValue->then(
                        fn ($returnValue) => $this->returnResult($callId, $returnValue),
                        fn (Throwable $exception) => $this->returnError($callId, $exception)
                    );
                } else {
                    $this->returnResult($callId, $returnValue);
                }
                break;
            case MessageType::RETURN:
                /*
                 * A return value for a call to the remote end was received from the remote endpoint,
                 * that should be fulfilled locally to provide it back to the local caller.
                 *
                 * Note that if a promise is returned, it will be chained onto the original call promise.
                 */
                $this->callTable->return(callId: $payload['callId'], value: $payload['returnValue']);
                break;
            case MessageType::ERROR:
                /*
                 * An error for a call to the remote end was received from the remote endpoint,
                 * that should be rejected locally to provide it back to the local caller.
                 */
                $exceptionClass = $payload['exception']['class'] ?? null;

                if ($exceptionClass === null) {
                    throw new DeserialisationFailedException('Exception class missing');
                }

                if (!is_subclass_of($exceptionClass, SerialisableExceptionInterface::class)) {
                    throw new DeserialisationFailedException(sprintf(
                        'Exception class "%s" does not implement SerialisableExceptionInterface',
                        $exceptionClass
                    ));
                }

                $exception = $exceptionClass::deserialise($payload['exception']['data']);

                $this->callTable->throw(callId: $payload['callId'], exception: $exception);
                break;
        }
    }

    /**
     * @inheritDoc
     */
    public function resume(): void
    {
        $this->paused = false;

        foreach ($this->messageQueue as ['type' => $type, 'payload' => $payload]) {
            $this->receive($type, $payload);
        }

        $this->messageQueue = [];
    }

    private function returnError(int $callId, Throwable $exception): void
    {
        if (!$exception instanceof SerialisableExceptionInterface) {
            // Exception is not serialisable, so wrap its details in a ProxyException, which is.
            $exception = new ProxyException(
                originalClass: $exception::class,
                originalMessage: $exception->getMessage(),
                originalCode: $exception->getCode(),
                originalFile: $exception->getFile(),
                originalLine: $exception->getLine()
            );
        }

        $this->transmitter->transmit(type: MessageType::ERROR, payload: [
            'callId' => $callId,
            'exception' => ['class' => $exception::class, 'data' => $exception->serialise()],
        ]);
    }

    private function returnResult(int $callId, mixed $returnValue): void
    {
        $this->transmitter->transmit(type: MessageType::RETURN, payload: [
            'callId' => $callId,
            'returnValue' => $returnValue,
        ]);
    }
}
