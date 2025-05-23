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

namespace Nytris\Rpc\Transport\Message;

/**
 * Enum MessageType.
 *
 * @author Dan Phillimore <dan@ovms.co>
 */
enum MessageType: string
{
    /**
     * Message type for a call to a remote method.
     */
    case CALL = 'call';
    /**
     * Message type for an error during a call to a remote method.
     */
    case ERROR = 'error';
    /**
     * Message type for a return value of a remote call.
     */
    case RETURN = 'return';
}
