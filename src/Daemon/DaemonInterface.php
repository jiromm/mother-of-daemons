<?php

namespace Jiromm\MotherOfDaemons\Daemon;

interface DaemonInterface
{
    public function getConfig() : \ArrayAccess;

    public function getCommand() : string;

    public function getMessageCount() : int;
}
