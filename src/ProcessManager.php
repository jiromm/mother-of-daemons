<?php

namespace Jiromm\DaemonMaster;

use Jiromm\MotherOfDaemons\Exception\NotFoundException;
use Jiromm\MotherOfDaemons\Exception\UnacceptableException;
use Evenement\EventEmitter;
use React\ChildProcess\Process;

class ProcessManager extends EventEmitter
{
    private $processList = [];
    private $reverseList = [];

    public function add(string $processName, Process $process): void
    {
        if (isset($this->processList[$processName][$process->getPid()])) {
            throw new UnacceptableException(sprintf('Process [%s] already in process list with the PID #%d', $processName, $process->getPid()));
        }

        $this->reverseList[$process->getPid()] = $processName;
        $this->processList[$processName][$process->getPid()] = $process;
        $this->emit('add', [$processName, $process]);

        $process->on('exit', function () use ($processName, $process) {
            $this->remove($processName, $process);
        });
    }

    public function remove(string $processName, Process $process): void
    {
        if (!isset($this->processList[$processName][$process->getPid()])) {
            throw new NotFoundException(sprintf('Process [%s] not found in list with the PID #%d', $processName, $process->getPid()));
        }

        unset($this->processList[$processName][$process->getPid()]);
        unset($this->reverseList[$process->getPid()]);

        $this->emit('remove', [$processName, $process]);
    }

    public function get(string $processName): array
    {
        if (!isset($this->processList[$processName])) {
            throw new NotFoundException(sprintf('Process [%s] not found in list', $processName));
        }

        return $this->processList[$processName];
    }

    public function getByPid(int $pid): Process
    {
        if (!isset($this->reverseList[$pid])) {
            throw new NotFoundException(sprintf('Process with the PID #%d was not found in (reverse) list', $pid));
        }

        if (!isset($this->processList[$this->reverseList[$pid]][$pid])) {
            throw new NotFoundException(sprintf('Process with the PID #%d was not found in list', $pid));
        }

        return $this->processList[$this->reverseList[$pid]][$pid];
    }

    public function getList(): array
    {
        return $this->processList;
    }
}
