<?php

namespace Jiromm\MotherOfDaemons\Router;

use Psr\Http\Message\ServerRequestInterface;

interface RouterInterface
{
    public function on(string $route, callable $callback);
    public function run(ServerRequestInterface $request);
}
