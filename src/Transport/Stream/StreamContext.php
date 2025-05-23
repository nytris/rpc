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

namespace Nytris\Rpc\Transport\Stream;

use React\Stream\ReadableStreamInterface;
use React\Stream\WritableStreamInterface;

/**
 * Class StreamContext.
 *
 * @author Dan Phillimore <dan@ovms.co>
 */
class StreamContext implements StreamContextInterface
{
    private ?ReadableStreamInterface $inputStream = null;
    private ?WritableStreamInterface $outputStream = null;

    /**
     * @inheritDoc
     */
    public function getInputStream(): ReadableStreamInterface
    {
        return $this->inputStream;
    }

    /**
     * @inheritDoc
     */
    public function getOutputStream(): WritableStreamInterface
    {
        return $this->outputStream;
    }

    /**
     * @inheritDoc
     */
    public function useStreams(
        ReadableStreamInterface $inputStream,
        WritableStreamInterface $outputStream
    ): void {
        $this->inputStream = $inputStream;
        $this->outputStream = $outputStream;
    }
}
