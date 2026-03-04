<?php

declare(strict_types=1);

namespace Core;

/**
 * Простой роутер для обработки HTTP-запросов
 */
class Router
{
    private array $routes = [];
    private array $middlewares = [];
    private string $basePath = '';

    public function setBasePath(string $basePath): void
    {
        $this->basePath = rtrim($basePath, '/');
    }

    public function get(string $path, callable|array $handler, array $middleware = []): void
    {
        $this->addRoute('GET', $path, $handler, $middleware);
    }

    public function post(string $path, callable|array $handler, array $middleware = []): void
    {
        $this->addRoute('POST', $path, $handler, $middleware);
    }

    public function put(string $path, callable|array $handler, array $middleware = []): void
    {
        $this->addRoute('PUT', $path, $handler, $middleware);
    }

    public function delete(string $path, callable|array $handler, array $middleware = []): void
    {
        $this->addRoute('DELETE', $path, $handler, $middleware);
    }

    public function addMiddleware(string $name, callable $middleware): void
    {
        $this->middlewares[$name] = $middleware;
    }

    private function addRoute(string $method, string $path, callable|array $handler, array $middleware): void
    {
        $path = $this->basePath . '/' . trim($path, '/');
        $path = $path === '/' ? '/' : rtrim($path, '/');

        $this->routes[] = [
            'method' => $method,
            'path' => $path,
            'handler' => $handler,
            'middleware' => $middleware
        ];
    }

    public function dispatch(): void
    {
        $method = $_SERVER['REQUEST_METHOD'];
        $uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        $uri = $uri === '/' ? '/' : rtrim($uri, '/');

        // Обработка предварительных CORS-запросов
        if ($method === 'OPTIONS') {
            http_response_code(200);
            return;
        }

        foreach ($this->routes as $route) {
            $params = $this->matchRoute($route['path'], $uri);

            if ($params !== false && $route['method'] === $method) {
                // Запуск промежуточных обработчиков
                foreach ($route['middleware'] as $middlewareName) {
                    if (isset($this->middlewares[$middlewareName])) {
                        $result = call_user_func($this->middlewares[$middlewareName]);
                        if ($result === false) {
                            return;
                        }
                    }
                }

                // Вызов обработчика
                $this->callHandler($route['handler'], $params);
                return;
            }
        }

        // Маршрут не найден
        $this->notFound();
    }

    private function matchRoute(string $routePath, string $uri): array|false
    {
        // Преобразование параметров маршрута в регулярное выражение
        $pattern = preg_replace('/\{([a-zA-Z_]+)\}/', '(?P<$1>[^/]+)', $routePath);
        $pattern = '#^' . $pattern . '$#';

        if (preg_match($pattern, $uri, $matches)) {
            // Фильтрация числовых ключей
            return array_filter($matches, fn($key) => !is_int($key), ARRAY_FILTER_USE_KEY);
        }

        return false;
    }

    private function callHandler(callable|array $handler, array $params): void
    {
        if (is_array($handler)) {
            [$controllerClass, $method] = $handler;
            $controller = new $controllerClass();
            call_user_func_array([$controller, $method], $params);
        } else {
            call_user_func_array($handler, $params);
        }
    }

    private function notFound(): void
    {
        http_response_code(404);

        if ($this->isApiRequest()) {
            header('Content-Type: application/json');
            echo json_encode(['error' => 'Not Found']);
        } else {
            echo '<!DOCTYPE html><html><head><title>404 Not Found</title></head>';
            echo '<body><h1>404 - Page Not Found</h1></body></html>';
        }
    }

    private function isApiRequest(): bool
    {
        $uri = $_SERVER['REQUEST_URI'] ?? '';
        return str_starts_with($uri, '/api/');
    }
}
