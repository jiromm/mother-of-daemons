<?php

while (true) {
    echo sprintf('handling [%s] images [%s] ...', $argv[1], md5(time())) . PHP_EOL;
    sleep(5);
}
