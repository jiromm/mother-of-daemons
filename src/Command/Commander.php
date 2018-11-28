<?php

namespace Jiromm\MotherOfDaemons\Command;

class Commander
{
    private $client;

    public function __construct(Client $client)
    {
        $this->client = $client;
    }

    public function __invoke(string $commandString)
    {
        if (empty($commandString)) {
            $this->help();
            return;
        }

        $particles = explode(' ', $commandString);
        $command = array_shift($particles);

        if (method_exists($this, $command)) {
            $this->$command(...$particles);
        } else {
            $this->unknownCommand();
        }
    }

    protected function start(string $daemon)
    {
        $response = $this->client->start($daemon);

        echo $response->getContent() . PHP_EOL;
    }

    protected function stop(string $daemon)
    {
        $response = $this->client->stop($daemon);

        echo $response->getContent() . PHP_EOL;
    }

    protected function list()
    {
        $response = $this->client->list();

        echo $response->getContent() . PHP_EOL;
    }

    protected function help()
    {
        echo 'Available Commands' . PHP_EOL . 'start|stop';
    }

    protected function unknownCommand()
    {
        echo 'Unknown Command' . PHP_EOL;
        $this->help();
    }
}
