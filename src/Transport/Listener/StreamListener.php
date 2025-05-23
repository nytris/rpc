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

namespace Nytris\Rpc\Transport\Listener;

use Nytris\Rpc\Transport\Frame\FramingProtocolInterface;
use Nytris\Rpc\Transport\Receiver\ReceiverInterface;
use Nytris\Rpc\Transport\Stream\StreamContextInterface;

/**
 * Class StreamListener.
 *
 * Receives messages from a remote receiver using a ReactPHP stream.
 * Useful for inter-process communication.
 *
 * @author Dan Phillimore <dan@ovms.co>
 */
class StreamListener implements ListenerInterface
{
    /**
     * @var callable|null
     */
    private $onData = null;

    public function __construct(
        private readonly StreamContextInterface $streamContext,
        private readonly FramingProtocolInterface $framingProtocol
    ) {
    }

    /**
     * @inheritDoc
     */
    public function listen(ReceiverInterface $receiver): void
    {
        $stream = $this->streamContext->getInputStream();

        $buffer = '';

        $this->onData = function (string $chunk) use (&$buffer, $receiver) {
            $buffer .= $chunk;

            while ($this->framingProtocol->hasFrame($buffer)) {
                $message = $this->framingProtocol->parseFrame($buffer);

                $receiver->receive($message->getType(), $message->getPayload());
            }
        };

        $stream->on('data', $this->onData);
    }

    /**
     * @inheritDoc
     */
    public function stop(): void
    {
        $stream = $this->streamContext->getInputStream();

        $stream->removeListener('data', $this->onData);
    }
}
