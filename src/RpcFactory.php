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

use Nytris\Rpc\Call\CallTable;
use Nytris\Rpc\Dispatcher\Dispatcher;
use Nytris\Rpc\Transport\Frame\FramingProtocolInterface;
use Nytris\Rpc\Transport\Frame\SerializeBasedFramingProtocol;
use Nytris\Rpc\Transport\Listener\StreamListener;
use Nytris\Rpc\Transport\Receiver\Receiver;
use Nytris\Rpc\Transport\Stream\StreamContext;
use Nytris\Rpc\Transport\Transmitter\StreamTransmitter;
use Nytris\Rpc\Transport\Transport;
use React\Stream\ReadableStreamInterface;
use React\Stream\WritableStreamInterface;

/**
 * Class RpcFactory.
 *
 * Library entrypoint; creates RPC instances with different configurations.
 *
 * @author Dan Phillimore <dan@ovms.co>
 */
class RpcFactory implements RpcFactoryInterface
{
    public function __construct(
        private readonly FramingProtocolInterface $framingProtocol = new SerializeBasedFramingProtocol()
    ) {
    }

    /**
     * @inheritDoc
     */
    public function createStreamRpc(
        ReadableStreamInterface $inputStream,
        WritableStreamInterface $outputStream,
        array $handlers = []
    ): RpcInterface {
        $callTable = new CallTable();
        $dispatcher = new Dispatcher();
        $streamContext = new StreamContext();

        $streamContext->useStreams($inputStream, $outputStream);

        $transmitter = new StreamTransmitter($streamContext, $this->framingProtocol);
        $listener = new StreamListener($streamContext, $this->framingProtocol);
        $receiver = new Receiver($callTable, $dispatcher, $transmitter);

        $transport = new Transport($transmitter, $listener, $receiver);

        foreach ($handlers as $handler) {
            $dispatcher->registerHandler($handler);
        }

        return new Rpc($callTable, $transport);
    }
}
