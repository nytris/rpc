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

namespace Nytris\Rpc\Call;

use BadMethodCallException;
use Exception;

/**
 * Class CallTable.
 *
 * @author Dan Phillimore <dan@ovms.co>
 */
class CallTable implements CallTableInterface
{
    /**
     * @var array<int, array{onReturn: (callable(mixed): void), onThrow: (callable(Exception): void)}>
     */
    private array $callIdToCallablesMap = [];
    private int $nextCallId = 0;

    /**
     * @inheritDoc
     */
    public function addCall(callable $onReturn, callable $onThrow): int
    {
        $callId = $this->nextCallId++;

        $this->callIdToCallablesMap[$callId] = [
            'onReturn' => $onReturn,
            'onThrow' => $onThrow,
        ];

        return $callId;
    }

    /**
     * @inheritDoc
     */
    public function return(int $callId, mixed $value): void
    {
        $callables = $this->callIdToCallablesMap[$callId] ?? null;
        unset($this->callIdToCallablesMap[$callId]);

        if ($callables === null) {
            throw new BadMethodCallException('Call with ID ' . $callId . ' not found"');
        }

        $callables['onReturn']($value);
    }

    /**
     * @inheritDoc
     */
    public function throw(int $callId, Exception $exception): void
    {
        $callables = $this->callIdToCallablesMap[$callId] ?? null;
        unset($this->callIdToCallablesMap[$callId]);

        if ($callables === null) {
            throw new BadMethodCallException('Call with ID ' . $callId . ' not found"');
        }

        $callables['onThrow']($exception);
    }
}
