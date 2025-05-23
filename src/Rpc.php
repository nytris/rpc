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

namespace Nytris\Rpc;

use Nytris\Rpc\Call\CallTableInterface;
use Nytris\Rpc\Transport\Message\MessageType;
use Nytris\Rpc\Transport\TransportInterface;
use React\Promise\Promise;
use React\Promise\PromiseInterface;

/**
 * Class Rpc.
 *
 * @author Dan Phillimore <dan@ovms.co>
 */
class Rpc implements RpcInterface
{
    public function __construct(
        private readonly CallTableInterface $callTable,
        private readonly TransportInterface $transport
    ) {
    }

    /**
     * @inheritDoc
     */
    public function call(string $handlerFqcn, string $method, array $args = []): PromiseInterface
    {
        return new Promise(function (
            callable $resolve,
            callable $reject
        ) use (
            $handlerFqcn,
            $method,
            $args
        ) {
            $callId = $this->callTable->addCall($resolve, $reject);

            $this->transport->send(MessageType::CALL, [
                'callId' => $callId,
                'handlerFqcn' => $handlerFqcn,
                'method' => $method,
                'args' => $args,
            ]);
        });
    }

    /**
     * @inheritDoc
     */
    public function start(): void
    {
        $this->transport->listen();
        $this->transport->resume();
    }

    /**
     * @inheritDoc
     */
    public function stop(): void
    {
        $this->transport->pause();
        $this->transport->stop();
    }
}
