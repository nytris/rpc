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

namespace Nytris\Rpc\Tests\Functional\Fixtures;

use Nytris\Rpc\Exception\SerialisableExceptionInterface;
use RuntimeException;

/**
 * Class TestSerialisableException.
 *
 * @author Dan Phillimore <dan@ovms.co>
 */
class TestSerialisableException extends RuntimeException implements SerialisableExceptionInterface
{
    public static function deserialise(array $data): SerialisableExceptionInterface
    {
        // Add a suffix to show that this deserialisation hook was used.
        return new self($data['myMessage'] . ' (deserialised)');
    }

    public function serialise(): array
    {
        return [
            'myMessage' => $this->getMessage(),
        ];
    }
}
