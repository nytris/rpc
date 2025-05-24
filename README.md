# Nytris RPC

[![Build Status](https://github.com/nytris/rpc/workflows/CI/badge.svg)](https://github.com/nytris/rpc/actions?query=workflow%3ACI)

Remote Procedure Call abstraction for PHP.

## What is it?
Nytris RPC is a PHP library that provides a robust, asynchronous Remote Procedure Call (RPC) implementation.
It allows you to make calls to remote endpoints in an asynchronous manner using ReactPHP streams for communication,
with the ability to pause and resume the transport layer when needed.

## How does it work?
The library provides a transport layer that can be used to send and receive messages to and from a remote endpoint.
It uses ReactPHP for asynchronous operations and provides a Promise-based API for handling responses.

The architecture consists of several key components:
- **RpcFactory:** Creates and configures RPC instances: the standard library entrypoint.
- **Transport:** Handles the sending and receiving of messages.
- **CallTable:** Manages active RPC calls and their callbacks.
- **Dispatcher:** Routes incoming RPC calls to the appropriate handlers.
- **Handler:** Implements the user-defined business logic for handling RPC calls.
- **Framing Protocol:** Handles message framing for reliable message boundaries (defaults to PHP `serialize()`-based).

## Requirements

- PHP 8.1 or later
- [ReactPHP](https://reactphp.org/) (installed automatically as a dependency)

## Installation

Install this package with Composer:

```shell
$ composer require nytris/rpc
```

## Usage

### Simple example using named pipes (FIFOs)

As a simple example, create two named pipes (FIFOs) for communication between the server and client processes.
In reality, you would likely use sockets instead.

```shell
mkfifo server_to_client
mkfifo client_to_server
```

`My/App/Handler/MyCommandHandler.php`
```php
<?php

declare(strict_types=1);

namespace My\App\Handler;

use Nytris\Rpc\Handler\HandlerInterface;
use Nytris\Rpc\Handler\HandlerTrait;

class MyCommandHandler implements HandlerInterface
{
    use HandlerTrait;

    public function myMethod(string $arg1, string $arg2): string
    {
        return "Processed: $arg1 and $arg2";
    }
}
```

`server.php`
```php
<?php

declare(strict_types=1);

use My\App\Handler\MyCommandHandler;
use Nytris\Rpc\RpcFactory;
use React\EventLoop\Loop;
use React\Stream\ReadableResourceStream;
use React\Stream\WritableResourceStream;

require_once __DIR__ . '/vendor/autoload.php';

// Open input and output streams using the named pipes (FIFOs).
$clientToServer = fopen('client_to_server', 'rb');
stream_set_blocking($clientToServer, false);
$serverToClient = fopen('server_to_client', 'wb');
stream_set_blocking($serverToClient, false);

$input = new ReadableResourceStream($clientToServer);
$output = new WritableResourceStream($serverToClient);

// Create an RPC instance, providing the input and output streams
// and handlers for the actual methods that may be called remotely.
$rpcFactory = new RpcFactory();
$rpc = $rpcFactory->createStreamRpc($input, $output, [
    new MyCommandHandler(),
]);

$rpc->start();

print 'Server started.' . PHP_EOL;

Loop::run();
```

`client.php`

```php
<?php

declare(strict_types=1);

use My\App\Handler\MyCommandHandler;
use Nytris\Rpc\RpcFactory;
use React\EventLoop\Loop;
use React\Stream\ReadableResourceStream;
use React\Stream\WritableResourceStream;

require_once __DIR__ . '/vendor/autoload.php';

// Open input and output streams using the named pipes (FIFOs).
// Note that these are reversed from their usage in the server process.
$clientToServer = fopen('client_to_server', 'wb');
stream_set_blocking($clientToServer, false);
$serverToClient = fopen('server_to_client', 'rb');
stream_set_blocking($serverToClient, false);

$output = new WritableResourceStream($clientToServer);
$input = new ReadableResourceStream($serverToClient);

// Note that the handler does not need to be registered with the client-side RPC instance.
// You could however register command handlers if you needed the server to be able to call the client too.
$rpcFactory = new RpcFactory();
$rpc = $rpcFactory->createStreamRpc($input, $output);
$rpc->start();

// Make an RPC call to the remote handler in the `server.php` process.
$rpc->call(MyCommandHandler::class, 'myMethod', ['value1', 'value2'])
    ->then(
        function ($result) use ($rpc, $input, $output) {
            // Handle successful result.
            echo "Success: " . json_encode($result, JSON_PRETTY_PRINT) . PHP_EOL;

            $rpc->stop();
            $input->close();
            $output->close();
        },
        function (Throwable $exception) {
            // Handle error raised while handling the call.
            echo "Error: " . $exception->getMessage() . PHP_EOL;
        }
    );

// Run the event loop to process messages.
Loop::run();
```

```shell
php server.php &
php client.php
```

### Creating and configuring handlers

Handlers are PHP classes that implement `Nytris\Rpc\Handler\HandlerInterface` and are registered on the receiving side (usually the "server" side) to handle incoming RPC calls from clients. Each handler typically represents a group of related methods that can be called remotely.
Note that the server/client naming is only used in the documentation here for demonstration purposes; handlers may be registered on either side and the setup may be peer-to-peer or otherwise.

Key points about handlers:
- They must implement the `onUndefinedMethod` method, which is called when a non-existent method is invoked.
- The handler's class name is used to identify it during RPC calls.
- Handlers are registered on the side where the actual method implementations exist, usually the "server" side.

To simplify handler implementation, you can use the `Nytris\Rpc\Handler\HandlerTrait` trait, which provides a default implementation of `onUndefinedMethod` that throws appropriate exceptions:

```php
<?php

declare(strict_types=1);

namespace My\App\Handler;

use Nytris\Rpc\Handler\HandlerInterface;
use Nytris\Rpc\Handler\HandlerTrait;

class MyCommandHandler implements HandlerInterface
{
    use HandlerTrait;

    /**
     * Example method that may be called via RPC.
     *
     * @param string $arg1 First argument
     * @param string $arg2 Second argument
     * @return string The result to send back to the caller
     */
    public function myMethod(string $arg1, string $arg2): string
    {
        return "Processed: $arg1 and $arg2";
    }
}
```

### Error/exception handling

Exceptions raised while handling an RPC call will be checked for whether they implement
the `Nytris\Rpc\Exception\SerialisableExceptionInterface` interface.
If they do, they will be serialised using its `->serialise()` method, allowing the Promise received
by the caller to receive an instance of the specific exception class.
Otherwise, an instance of `Nytris\Rpc\Exception\ProxyException` will be used.

## Advanced usage

### Custom framing protocol

By default, the library uses a PHP `serialize()`-based framing protocol. You can implement your own framing protocol by implementing the `FramingProtocolInterface` and then providing it when constructing `Nytris\Rpc\RpcFactory`:

```php
<?php

declare(strict_types=1);

use Nytris\Rpc\RpcFactory;
use Nytris\Rpc\Transport\Frame\FramingProtocolInterface;
use Nytris\Rpc\Transport\Message\Message;
use Nytris\Rpc\Transport\Message\MessageInterface;
use Nytris\Rpc\Transport\Message\MessageType;

class MyCustomFramingProtocol implements FramingProtocolInterface
{
    public function frameMessage(MessageInterface $message) : string
    {
        return 'my frame encapsulating message contents';
    }

    public function hasFrame(string $buffer) : bool
    {
        return str_starts_with($buffer, 'my_frame_header');
    }
    
    public function parseFrame(string &$buffer): MessageInterface
    {
        // Note the ::CALL type is just for demonstration purposes:
        // in reality the buffer will likely contain data indicating the message type.
        return new Message(MessageType::CALL, ['my parsed arg']);
    }
}

// Use the custom framing protocol.
$framingProtocol = new MyCustomFramingProtocol();
$rpcFactory = new RpcFactory(framingProtocol: $framingProtocol);
```

Contributions are welcome! Please feel free to submit a Pull Request.

### Running Tests

To run the test suite:

```bash
composer test
```

## License

Nytris RPC is open source software licensed under the [MIT license](MIT-LICENSE.txt).

## See also

- [ReactPHP](https://reactphp.org/) - Event-driven, non-blocking I/O with PHP.
- [PHP Code Shift](https://github.com/asmblah/php-code-shift) - Library for programmatically transforming PHP code.
- [Nytris](https://github.com/nytris/nytris) - Low-level PHP framework.
