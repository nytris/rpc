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

namespace Nytris\Rpc\Tests\Unit\Call;

use BadMethodCallException;
use Exception;
use Nytris\Rpc\Call\CallTable;
use Nytris\Rpc\Tests\AbstractTestCase;

/**
 * Class CallTableTest.
 *
 * @author Dan Phillimore <dan@ovms.co>
 */
class CallTableTest extends AbstractTestCase
{
    private CallTable $callTable;

    public function setUp(): void
    {
        $this->callTable = new CallTable();
    }

    public function testAddCallReturnsUniqueCallIds(): void
    {
        $callId1 = $this->callTable->addCall(
            function () {},
            function () {}
        );
        $callId2 = $this->callTable->addCall(
            function () {},
            function () {}
        );

        static::assertNotEquals($callId1, $callId2);
    }

    public function testReturnInvokesTheCorrectCallable(): void
    {
        $returnValue = null;
        $this->callTable->addCall(function () {}, function () {});
        $callId = $this->callTable->addCall(
            function ($value) use (&$returnValue) {
                $returnValue = $value;
            },
            function () {}
        );
        $this->callTable->addCall(function () {}, function () {});

        $this->callTable->return($callId, 'my return value');

        static::assertSame('my return value', $returnValue);
    }

    public function testReturnRemovesCallables(): void
    {
        $this->callTable->addCall(function () {}, function () {});
        $callId = $this->callTable->addCall(
            function () {},
            function () {}
        );
        $this->callTable->addCall(function () {}, function () {});
        $this->callTable->return($callId, 'my return value');

        $this->expectException(BadMethodCallException::class);
        $this->expectExceptionMessage("Call with ID $callId not found");

        $this->callTable->return($callId, 'my return value');
    }

    public function testReturnThrowsExceptionWhenCallIdNotFound(): void
    {
        $this->expectException(BadMethodCallException::class);
        $this->expectExceptionMessage('Call with ID 21 not found"');

        $this->callTable->return(21, 'my return value');
    }

    public function testThrowInvokesTheCorrectCallable(): void
    {
        $expectedException = new Exception('My exception');
        $throwException = null;
        $this->callTable->addCall(function () {}, function () {});
        $callId = $this->callTable->addCall(
            function () {},
            function ($exception) use (&$throwException) {
                $throwException = $exception;
            }
        );
        $this->callTable->addCall(function () {}, function () {});

        $this->callTable->throw($callId, $expectedException);

        static::assertSame($expectedException, $throwException);
    }

    public function testThrowRemovesCallables(): void
    {
        $this->callTable->addCall(function () {}, function () {});
        $callId = $this->callTable->addCall(
            function () {},
            function () {}
        );
        $this->callTable->addCall(function () {}, function () {});
        $this->callTable->throw($callId, new Exception('My exception'));

        $this->expectException(BadMethodCallException::class);
        $this->expectExceptionMessage("Call with ID $callId not found");

        $this->callTable->throw($callId, new Exception('My exception'));
    }

    public function testThrowThrowsExceptionWhenCallIdNotFound(): void
    {
        $this->expectException(BadMethodCallException::class);
        $this->expectExceptionMessage('Call with ID 21 not found"');

        $this->callTable->throw(21, new Exception('My exception'));
    }
}
