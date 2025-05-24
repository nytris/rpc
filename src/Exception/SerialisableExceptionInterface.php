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

/**
 * Interface SerialisableExceptionInterface.
 *
 * Implemented by exceptions that can be serialised for transport over RPC.
 *
 * @author Dan Phillimore <dan@ovms.co>
 */
interface SerialisableExceptionInterface extends RpcExceptionInterface
{
    /**
     * Deserialises the exception following transport over RPC.
     *
     * @param array<mixed> $data
     */
    public static function deserialise(array $data): SerialisableExceptionInterface;

    /**
     * Serialises the exception for transport over RPC.
     *
     * @return array<mixed>
     */
    public function serialise(): array;
}
