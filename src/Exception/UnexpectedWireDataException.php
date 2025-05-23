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

use LogicException;

/**
 * Class UnexpectedWireDataException.
 *
 * Thrown when unexpected data is encountered in the wire protocol.
 *
 * @author Dan Phillimore <dan@ovms.co>
 */
class UnexpectedWireDataException extends LogicException implements RpcExceptionInterface
{
}
