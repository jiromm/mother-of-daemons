<?php

namespace Jiromm\MotherOfDaemons\Daemon;

interface DaemonInterface
{
    public function getName(): string;

    public function getConfig(): DaemonConfig;

    public function getCommand(): string;

    public function getMessageCount(): int;
}
