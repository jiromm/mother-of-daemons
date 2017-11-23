<?php

use Jiromm\MotherOfDaemons\MotherOfDaemons;
use Jiromm\MotherOfDaemons\Daemon\DaemonCollection;
use Jiromm\MotherOfDaemons\Exception\MotherOfDaemonException;
use Examples\ImageHandlerDaemon;

require __DIR__ . '/../vendor/autoload.php';
require __DIR__ . '/../examples/ImageHandlerDaemon.php';

try {
    $daemons = new DaemonCollection();

    $daemons->add(new ImageHandlerDaemon('buildings'));
    $daemons->add(new ImageHandlerDaemon('animals'));

    $mod = new MotherOfDaemons($daemons);
    $mod->run(true);
} catch (MotherOfDaemonException $e) {
    echo $e->getMessage() . PHP_EOL;
}
