<?php

namespace Jiromm\MotherOfDaemons\Router;

use Psr\Http\Message\ServerRequestInterface;
use React\Http\Response;

class RegexRouter implements RouterInterface
{
    private $routes = [];

    public function on(string $pattern, callable $callback)
    {
        $this->routes[$pattern] = $callback;
    }

    public function run(ServerRequestInterface $request)
    {
        $path = $request->getUri()->getPath();

        foreach ($this->routes as $pattern => $callback) {
            if (preg_match('/^' . str_replace('/', '\/', $pattern) . '/', $path, $params) === 1) {
                array_shift($params);
                $result = call_user_func($callback, $request, ...$params);

                return new Response(
                    200,
                    ['Content-Type' => 'application/json; charset=utf-8'],
                    $result
                );
            }
        }
    }
}
