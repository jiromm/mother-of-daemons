<?php

namespace Examples;

use Jiromm\MotherOfDaemons\Daemon\DaemonConfig;
use Jiromm\MotherOfDaemons\Daemon\DaemonInterface;

class ImageHandlerDaemon implements DaemonInterface
{
    protected $dirName;

    public function __construct(string $dirName)
    {
        $this->dirName = $dirName;
    }

    public function getConfig(): \ArrayAccess
    {
        return new DaemonConfig([
            'threshold' => 2,
            'interval' => 1,
            'limit' => 3,
        ]);
    }

    public function getCommand(): string
    {
        return sprintf('php handle-images.php %s', $this->dirName);
    }

    public function getMessageCount(): int
    {
        return rand(0, 10);
    }
}
