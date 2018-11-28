<?php

use Jiromm\MotherOfDaemons\MotherOfDaemons;
use Jiromm\MotherOfDaemons\Daemon\DaemonCollection;
use Jiromm\MotherOfDaemons\Exception\MotherOfDaemonException;
use Examples\ImageHandlerDaemon;

require __DIR__ . '/../vendor/autoload.php';
require __DIR__ . '/../examples/ImageHandlerDaemon.php';

try {
    $mod = new MotherOfDaemons();
    $command = '';

    if (isset($argv[1])) {
        array_shift($argv);
        $command = implode(' ', $argv);
    }

    $mod->command($command);
} catch (MotherOfDaemonException $e) {
    echo $e->getMessage() . PHP_EOL;
}
