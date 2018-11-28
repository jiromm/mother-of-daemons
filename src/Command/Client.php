<?php

namespace Jiromm\MotherOfDaemons\Command;

use Zend\Http\Response;

class Client
{
    private $uri;
    private $client;

    public function __construct(string $uri)
    {
        $this->uri = $uri;
        $this->client = new \Zend\Http\Client($uri, [
            'maxredirects' => 0,
            'timeout'      => 5,
        ]);
    }

    public function start(string $daemon): Response
    {
        $this
            ->client
            ->setUri($this->uri . '/start')
            ->setParameterGet(['daemon' => $daemon]);
        return $this->client->send();
    }

    public function stop(string $daemon): Response
    {
        $this
            ->client
            ->setUri($this->uri . '/stop')
            ->setParameterGet(['daemon' => $daemon]);
        return $this->client->send();
    }

    public function list(): Response
    {
        $this->client->setUri($this->uri . '/list');
        return $this->client->send();
    }
}
