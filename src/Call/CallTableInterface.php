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

use Exception;

/**
 * Interface CallTableInterface.
 *
 * @author Dan Phillimore <dan@ovms.co>
 */
interface CallTableInterface
{
    /**
     * Adds a call to the table, returning its unique ID.
     *
     * @param callable $onReturn
     * @param callable $onThrow
     * @return int The call ID.
     */
    public function addCall(callable $onReturn, callable $onThrow): int;

    /**
     * Called with the return value when the call was successful.
     */
    public function return(int $callId, mixed $value): void;

    /**
     * Called with the exception when the call was unsuccessful.
     */
    public function throw(int $callId, Exception $exception): void;
}
