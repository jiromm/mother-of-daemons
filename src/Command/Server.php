<?php

namespace Jiromm\MotherOfDaemons\Command;

use Jiromm\MotherOfDaemons\Router\RouterInterface;
use Psr\Http\Message\ServerRequestInterface;
use React\EventLoop\LoopInterface;

class Server
{
    const HOST = '127.0.0.1';
    const PORT = '8001';

    protected $loop;
    protected $socket;

    public function __construct(LoopInterface $loop, RouterInterface $router)
    {
        $this->loop = $loop;

        $server = new \React\Http\Server(function (ServerRequestInterface $request) use ($router) {
            return $router->run($request);
        });

        $this->socket = new \React\Socket\Server(self::HOST . ':' . self::PORT, $this->loop);
        $server->listen($this->socket);

        echo 'Listening on ' . str_replace('tcp:', 'http:', $this->socket->getAddress()) . PHP_EOL;
    }
}
