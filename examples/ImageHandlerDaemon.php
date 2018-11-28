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

    public function getName(): string
    {
        return __CLASS__ . '::' . str_replace(' ', '-', $this->dirName);
    }

    public function getConfig(): DaemonConfig
    {
        return new DaemonConfig([
            'threshold' => 20000,
            'interval' => 10000,
            'limit' => 1,
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
