<?php

namespace Jiromm\MotherOfDaemons\Daemon;

class DaemonConfig implements \ArrayAccess
{
    private $storage = [];

    public function __construct(array $data = [])
    {
        $this->storage = array_merge([
            /**
             * The time in seconds after which daemon periodically will check the state of the queue
             */
            'interval'  => 5 * 100,

            /**
             * The max number of actions that can be handled by one daemon
             */
            'threshold' => 100,

            /**
             * Maximum number of daemons allowed
             */
            'limit' => 1,
        ], $data);
    }

    public function offsetSet($offset, $value): void
    {
        if (is_null($offset)) {
            $this->storage[] = $value;
        } else {
            $this->storage[$offset] = $value;
        }
    }

    public function offsetExists($offset): bool
    {
        return isset($this->storage[$offset]);
    }

    public function offsetUnset($offset): void
    {
        unset($this->storage[$offset]);
    }

    public function offsetGet($offset)
    {
        return isset($this->storage[$offset]) ? $this->storage[$offset] : null;
    }

    public function getArrayCopy(): array
    {
        return $this->storage;
    }
}
