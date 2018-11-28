<?php

namespace Jiromm\MotherOfDaemons;

use Jiromm\MotherOfDaemons\Command\Client;
use Jiromm\MotherOfDaemons\Command\Commander;
use Jiromm\MotherOfDaemons\Command\Dispatcher;
use Jiromm\MotherOfDaemons\Daemon\DaemonCollection;
use Jiromm\MotherOfDaemons\Daemon\DaemonInterface;
use Jiromm\MotherOfDaemons\Router\RegexRouter;
use Jiromm\MotherOfDaemons\Router\RouterInterface;
use React\ChildProcess\Process;
use React\EventLoop\Factory;
use React\EventLoop\LoopInterface;
use React\Signals\Killer\SerialKiller;

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

    /**
     * @var SerialKiller
     */
    private $killer;

    /**
     * @var Dispatcher
     */
    private $dispatcher;

    /**
     * @var RouterInterface
     */
    private $router;

    public function __construct(DaemonCollection $daemons = null)
    {
        $this->daemons = $daemons;
        $this->loop = Factory::create();
        $this->router = new RegexRouter();
        $this->dispatcher = new Dispatcher($this, $this->router);
    }

    public function run($debug = false): void
    {
        $this->processManager = new ProcessManager();
        $this->killer = new SerialKiller($this->loop, [SIGTERM, SIGINT]);

        $this->killer->onExit(function () {
            foreach ($this->processManager->getList() as $processList) {
                foreach ($processList as $process) {
                    $this->removeDaemon($process);
                }
            }

            $loop = $this->loop;
            // Wait until everthing is closed
            $this->loop->addPeriodicTimer(1, function () use ($loop) {
                $loop->stop();
            });
        });

        new Command\Server($this->loop, $this->router);

        $this->monitor($debug);

        foreach ($this->daemons as $daemon) {
            $this->createDaemon($daemon);
            $this->setupTick($daemon);
        }

        $this->loop->run();
    }

    public function command(string $command)
    {
        $client = new Client('http://' . Command\Server::HOST . ':' . Command\Server::PORT);
        $commander = new Commander($client);
        $commander($command);
    }

    public function createDaemon(DaemonInterface $daemon): void
    {
        $process = $this->execute($daemon);
        $this->processManager->add($daemon->getCommand(), $process);
    }

    public function removeDaemon(Process $process): void
    {
        $process->terminate(SIGUSR1);
    }

    public function getLoop(): LoopInterface
    {
        return $this->loop;
    }

    public function getDaemons(): DaemonCollection
    {
        return $this->daemons;
    }

    public function getProcessManager(): ProcessManager
    {
        return $this->processManager;
    }

    private function monitor(bool $debug): void
    {
        if (!$debug) return;

        $this->processManager->on('add', function (string $processName, Process $process) {
            $process->stdout->on('data', function ($chunk) {
                echo "\e[0m" . $chunk;
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

        $this->loop->addPeriodicTimer($config->offsetGet('interval'), function () use ($daemon) {
            $config = $daemon->getConfig();
            $this->resolveDaemons($daemon, $config->offsetGet('threshold'), $config->offsetGet('limit'));
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
}
