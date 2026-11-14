<?php

namespace App\Core;

use RuntimeException;

class Router
{
    private array $routes = [];

    public function get(string $uri, array $action, array $middleware = []): void
    {
        $this->addRoute('GET', $uri, $action, $middleware);
    }

    public function post(string $uri, array $action, array $middleware = []): void
    {
        $this->addRoute('POST', $uri, $action, $middleware);
    }

    private function addRoute(string $method, string $uri, array $action, array $middleware): void
    {
        $normalizedUri = '/' . trim($uri, '/');
        if ($normalizedUri !== '/') {
            $normalizedUri = rtrim($normalizedUri, '/');
        }

        // Convert /listing/{id} into regex #^/listing/([^/]+)$#
        $pattern = preg_replace('/\{([a-zA-Z0-9_]+)\}/', '([^/]+)', $normalizedUri);
        $regex = '#^' . $pattern . '$#';

        $this->routes[] = [
            'method'     => $method,
            'uri'        => $normalizedUri,
            'regex'      => $regex,
            'action'     => $action,
            'middleware' => $middleware,
        ];
    }

    public function dispatch(string $requestUri, string $requestMethod): void
    {
        $path = parse_url($requestUri, PHP_URL_PATH) ?? '/';

        // Strip XAMPP subdirectory prefix (/farmlink-web/public)
        $scriptDir = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/');
        if ($scriptDir !== '' && str_starts_with($path, $scriptDir)) {
            $path = substr($path, strlen($scriptDir));
        }

        $path = '/' . trim($path, '/');

        foreach ($this->routes as $route) {
            if ($route['method'] === strtoupper($requestMethod) && preg_match($route['regex'], $path, $matches)) {
                array_shift($matches); // Remove full match, keep captured params

                foreach ($route['middleware'] as $middlewareEntry) {
                    $this->runMiddleware($middlewareEntry);
                }

                [$controllerClass, $method] = $route['action'];

                if (!class_exists($controllerClass)) {
                    throw new RuntimeException("Controller {$controllerClass} does not exist.");
                }

                $controller = new $controllerClass();
                if (!method_exists($controller, $method)) {
                    throw new RuntimeException("Method {$method} not found on {$controllerClass}.");
                }

                call_user_func_array([$controller, $method], $matches);
                return;
            }
        }

        http_response_code(404);
        echo '404 Not Found - FarmLink Route Does Not Exist.';
    }

    private function runMiddleware(string|array $middlewareEntry): void
    {
        if (is_array($middlewareEntry)) {
            [$class, $param] = $middlewareEntry;
            (new $class())->handle($param);
        } else {
            (new $middlewareEntry())->handle();
        }
    }
}
