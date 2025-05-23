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

use Nytris\Rpc\Transport\Receiver\ReceiverInterface;

/**
 * Class NullListener.
 *
 * @author Dan Phillimore <dan@ovms.co>
 */
class NullListener implements ListenerInterface
{
    /**
     * @inheritDoc
     */
    public function listen(ReceiverInterface $receiver): void
    {
        // Do nothing.
    }

    /**
     * @inheritDoc
     */
    public function stop(): void
    {
        // Do nothing.
    }
}
