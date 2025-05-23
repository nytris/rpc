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

use Throwable;

/**
 * Interface RpcExceptionInterface.
 *
 * Base interface for all RPC-related exceptions.
 *
 * @author Dan Phillimore <dan@ovms.co>
 */
interface RpcExceptionInterface extends Throwable
{
}
