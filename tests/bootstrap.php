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

use Nytris\Boot\BootConfig;
use Nytris\Boot\PlatformConfig;
use Nytris\Nytris;
use Tasque\Core\Scheduler\ContextSwitch\PromiscuousStrategy;
use Tasque\EventLoop\TasqueEventLoopPackage;
use Tasque\TasquePackage;

require_once __DIR__ . '/../vendor/autoload.php';

Mockery::getConfiguration()->allowMockingNonExistentMethods(false);
Mockery::globalHelpers();

$bootConfig = new BootConfig(new PlatformConfig(dirname(__DIR__) . '/var/nytris/'));
$bootConfig->installPackage(new TasquePackage(
    schedulerStrategy: new PromiscuousStrategy(),
    preemptive: false,
));
$bootConfig->installPackage(new TasqueEventLoopPackage());

Nytris::boot($bootConfig);
