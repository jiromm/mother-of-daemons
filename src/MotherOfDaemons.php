<?php

namespace Jiromm\MotherOfDaemons;

use Jiromm\MotherOfDaemons\Daemon\DaemonCollection;
use Jiromm\MotherOfDaemons\Daemon\DaemonInterface;
use React\ChildProcess\Process;
use React\EventLoop\Factory;
use React\EventLoop\LoopInterface;

class MotherOfDaemons
{
    /**
     * @var DaemonCollection|DaemonInterface[]
     */
    private $daemons;

    /**
     * @var ProcessManager
     */
    private $processManager;

    /**
     * @var LoopInterface
     */
    private $loop;

    public function __construct(DaemonCollection $daemons)
    {
        $this->daemons = $daemons;
        $this->processManager = new ProcessManager();
    }

    public function run($debug = false): void
    {
        $this->loop = Factory::create();

        $this->monitor($debug);

        foreach ($this->daemons as $daemon) {
            $this->createDaemon($daemon);
            $this->setupTick($daemon);
        }

        $this->loop->run();
    }

    public function getLoop(): LoopInterface
    {
        return $this->loop;
    }

    private function monitor(bool $debug): void
    {
        if (!$debug) return;

        $this->processManager->on('add', function (string $processName, Process $process) {
            $process->stdout->on('data', function ($chunk) {
                echo '[>>>] ' . $chunk;
            });

            echo sprintf('%sProcess [%s] started with PID #%d', "\e[1;32m", $processName, $process->getPid()) . PHP_EOL;
        });

        $this->processManager->on('remove', function (string $processName, Process $process) {
            echo sprintf('%sProcess [%s] terminated', "\e[1;31m", $processName, $process->getPid()) . PHP_EOL;
        });
    }

    private function execute(DaemonInterface $daemon): Process
    {
        $command = $daemon->getCommand();
        $process = new Process('exec ' . $command);
        $process->start($this->getLoop());

        return $process;
    }

    private function setupTick(DaemonInterface $daemon): void
    {
        $config = $daemon->getConfig();
        $mother = $this;

        $this->loop->addPeriodicTimer($config->offsetGet('interval'), function () use ($mother, $daemon) {
            $config = $daemon->getConfig();
            $mother->resolveDaemons($daemon, $config->offsetGet('threshold'), $config->offsetGet('limit'));
        });
    }

    private function resolveDaemons(DaemonInterface $daemon, int $threshold, int $limit): void
    {
        $count = $daemon->getMessageCount();
        $processList = $this->processManager->get($daemon->getCommand());

        $availableInstanceCount = count($processList);
        $totalRequiredInstanceCount = ceil($count / $threshold) ?: 1;

        if ($totalRequiredInstanceCount > $limit) {
            $totalRequiredInstanceCount = $limit;
        }

        if ($totalRequiredInstanceCount > $availableInstanceCount) {
            $this->increaseDaemons($daemon, $totalRequiredInstanceCount, $availableInstanceCount);
        }

        if ($totalRequiredInstanceCount < $availableInstanceCount) {
            $this->decreaseDaemons($daemon, $totalRequiredInstanceCount, $availableInstanceCount);
        }
    }

    private function increaseDaemons(DaemonInterface $daemon, int $totalRequiredInstanceCount, int $availableInstanceCount): void
    {
        $requiredInstanceCount = $totalRequiredInstanceCount - $availableInstanceCount;

        for ($i = 0; $i < $requiredInstanceCount; $i++) {
            $this->createDaemon($daemon);
        }
    }

    private function decreaseDaemons(DaemonInterface $daemon, int $totalRequiredInstanceCount, int $availableInstanceCount): void
    {
        $processList = $this->processManager->get($daemon->getCommand());
        $excessedInstanceCount = $availableInstanceCount - $totalRequiredInstanceCount;

        for ($i = 0; $i < $excessedInstanceCount; $i++) {
            end($processList);

            $process = current($processList);
            $this->removeDaemon($process);
        }
    }

    private function createDaemon(DaemonInterface $daemon): void
    {
        $process = $this->execute($daemon);
        $this->processManager->add($daemon->getCommand(), $process);
    }

    private function removeDaemon(Process $process): void
    {
        $process->terminate(SIGTERM);
    }
}
