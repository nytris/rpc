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

namespace Nytris\Rpc\Exception;

use Exception;

/**
 * Class ProxyException.
 *
 * Represents an exception that could not be serialised for transport over RPC,
 * because it did not implement SerialisableExceptionInterface.
 *
 * @author Dan Phillimore <dan@ovms.co>
 */
class ProxyException extends Exception implements SerialisableExceptionInterface
{
    /**
     * @param string $originalClass The class name of the original exception
     * @param string $originalMessage The message of the original exception
     * @param int $originalCode The code of the original exception
     * @param string $originalFile The file where the original exception was thrown
     * @param int $originalLine The line where the original exception was thrown
     */
    public function __construct(
        private readonly string $originalClass,
        private readonly string $originalMessage,
        private readonly int $originalCode,
        private readonly string $originalFile,
        private readonly int $originalLine
    ) {
        $message = sprintf(
            'RPC exception :: %s: %s',
            $this->originalClass,
            $this->originalMessage
        );

        parent::__construct($message);
    }

    /**
     * @inheritDoc
     */
    public static function deserialise(array $data): SerialisableExceptionInterface
    {
        return new ProxyException(
            $data['originalClass'],
            $data['originalMessage'],
            $data['originalCode'],
            $data['originalFile'],
            $data['originalLine']
        );
    }

    /**
     * Fetches the class name of the original exception.
     */
    public function getOriginalClass(): string
    {
        return $this->originalClass;
    }

    /**
     * Fetches the code of the original exception.
     */
    public function getOriginalCode(): int
    {
        return $this->originalCode;
    }

    /**
     * Fetches the file where the original exception was thrown.
     */
    public function getOriginalFile(): string
    {
        return $this->originalFile;
    }

    /**
     * Fetches the line where the original exception was thrown.
     */
    public function getOriginalLine(): int
    {
        return $this->originalLine;
    }

    /**
     * Fetches the message of the original exception.
     */
    public function getOriginalMessage(): string
    {
        return $this->originalMessage;
    }

    /**
     * @inheritDoc
     */
    public function serialise(): array
    {
        return [
            'originalClass' => $this->originalClass,
            'originalMessage' => $this->originalMessage,
            'originalCode' => $this->originalCode,
            'originalFile' => $this->originalFile,
            'originalLine' => $this->originalLine,
        ];
    }
}
