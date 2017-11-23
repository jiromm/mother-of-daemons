<?php

namespace Jiromm\MotherOfDaemons;

use Jiromm\MotherOfDaemons\Exception\NotFoundException;
use Jiromm\MotherOfDaemons\Exception\UnacceptableException;
use Evenement\EventEmitter;
use React\ChildProcess\Process;

class ProcessManager extends EventEmitter
{
    /**
     * @var array
     */
    private $processes;

    public function add(string $processName, Process $process) : void
    {
        $that = $this;

        if (isset($this->processes[$processName][$process->getPid()])) {
            throw new UnacceptableException(sprintf('Process [%s] already in process list with PID #%d', $processName, $process->getPid()));
        }

        $this->processes[$processName][$process->getPid()] = $process;
        $this->emit('add', [$processName, $process]);

        $process->on('exit', function () use ($processName, $process, $that) {
            $that->remove($processName, $process);
        });
    }

    public function remove(string $processName, Process $process) : void
    {
        if (!isset($this->processes[$processName][$process->getPid()])) {
            throw new NotFoundException(sprintf('Process [%s] not found in list with PID #%d', $processName, $process->getPid()));
        }

        unset($this->processes[$processName][$process->getPid()]);
        $this->emit('remove', [$processName, $process]);
    }

    public function get(string $processName) : array
    {
        if (!isset($this->processes[$processName])) {
            throw new NotFoundException(sprintf('Process [%s] not found in list', $processName));
        }

        return $this->processes[$processName];
    }
}
