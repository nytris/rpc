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

namespace Nytris\Rpc\Transport\Frame;

use Nytris\Rpc\Exception\UnexpectedWireDataException;
use Nytris\Rpc\Transport\Message\MessageInterface;

/**
 * Class SerializeBasedFramingProtocol.
 *
 * Defines the wire protocol for transmission, encapsulating each message in a frame.
 *
 * @author Dan Phillimore <dan@ovms.co>
 */
class SerializeBasedFramingProtocol implements FramingProtocolInterface
{
    /**
     * @inheritDoc
     */
    public function frameMessage(MessageInterface $message): string
    {
        $rawData = serialize($message);

        return '__nytris__(' . strlen($rawData) . ')' . $rawData . "\n";
    }

    /**
     * @inheritDoc
     */
    public function hasFrame(string $buffer): bool
    {
        if ($buffer === '') {
            return false;
        }

        if (preg_match('/^__nytris__\((\d+)\)/', $buffer, $matches) === 0) {
            throw new UnexpectedWireDataException('Unexpected data at buffer head: ' . $buffer);
        }

        $prefix = $matches[0];
        $prefixLength = strlen($prefix);
        $dataLength = (int) $matches[1];

        return strlen($buffer) >= $prefixLength + $dataLength + 1;
    }

    /**
     * @inheritDoc
     */
    public function parseFrame(string &$buffer): MessageInterface
    {
        if (preg_match('/^__nytris__\((\d+)\)/', $buffer, $matches) === 0) {
            throw new UnexpectedWireDataException('Unexpected data at buffer head: ' . $buffer);
        }

        $prefix = $matches[0];
        $prefixLength = strlen($prefix);
        $dataLength = (int) $matches[1];
        $messageData = substr($buffer, $prefixLength, $dataLength);
        $buffer = substr($buffer, $prefixLength + $dataLength + 1);

        return unserialize($messageData);
    }
}
