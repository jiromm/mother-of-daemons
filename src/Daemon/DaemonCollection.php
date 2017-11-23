<?php

namespace Jiromm\MotherOfDaemons\Daemon;

class DaemonCollection implements \IteratorAggregate
{
    private $daemons;

    public function add(DaemonInterface $daemon)
    {
        $this->daemons[] = $daemon;
    }

    public function getIterator()
    {
        return new \ArrayIterator($this->daemons);
    }
}
