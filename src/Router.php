<?php

namespace App;

use App\Middleware\Json;

class Router
{
    private array $routes = [];

    public function get(string $pattern, callable $handler): void
    {
        $this->routes[] = ['GET', $pattern, $handler];
    }

    public function post(string $pattern, callable $handler): void
    {
        $this->routes[] = ['POST', $pattern, $handler];
    }

    public function put(string $pattern, callable $handler): void
    {
        $this->routes[] = ['PUT', $pattern, $handler];
    }

    public function dispatch(string $method, string $uri): void
    {
        $uri = strtok($uri, '?');

        foreach ($this->routes as [$routeMethod, $pattern, $handler]) {
            if ($routeMethod !== $method) {
                continue;
            }

            $regex = '#^' . preg_replace('/\{[a-z]+\}/', '(\d+)', $pattern) . '$#';

            if (preg_match($regex, $uri, $matches)) {
                array_shift($matches);
                $params = array_map('intval', $matches);
                call_user_func_array($handler, $params);
                return;
            }
        }

        Json::error("Route not found: {$method} {$uri}", 404);
    }
}