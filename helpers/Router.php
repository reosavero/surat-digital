<?php

/**
 * Router Dispatcher - Surat Digital
 */

class Router
{
    private static array $routes = [];
    private static array $middleware = [];
    private static ?string $notFoundHandler = null;

    public static function get(string $path, string $handler, array $middleware = []): void
    {
        self::addRoute('GET', $path, $handler, $middleware);
    }

    public static function post(string $path, string $handler, array $middleware = []): void
    {
        self::addRoute('POST', $path, $handler, $middleware);
    }

    public static function put(string $path, string $handler, array $middleware = []): void
    {
        self::addRoute('PUT', $path, $handler, $middleware);
    }

    public static function delete(string $path, string $handler, array $middleware = []): void
    {
        self::addRoute('DELETE', $path, $handler, $middleware);
    }

    public static function any(string $path, string $handler, array $middleware = []): void
    {
        self::addRoute('ANY', $path, $handler, $middleware);
    }

    public static function addMiddleware(string $middleware): void
    {
        self::$middleware[] = $middleware;
    }

    public static function setNotFound(string $handler): void
    {
        self::$notFoundHandler = $handler;
    }

    public static function dispatch(): void
    {
        $method = $_POST['_method'] ?? $_SERVER['REQUEST_METHOD'];
        $uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?? '/';
        
        // Strip base path dynamically
        $basePath = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME']));
        if ($basePath !== '/' && $basePath !== '\\' && str_starts_with($uri, $basePath)) {
            $uri = substr($uri, strlen($basePath));
        }
        $uri = '/' . trim($uri, '/');

        foreach (self::$middleware as $middleware) {
            self::runMiddleware($middleware);
        }

        $route = self::matchRoute($method, $uri);

        if ($route === null) {
            if (self::$notFoundHandler) {
                self::callHandler(self::$notFoundHandler, []);
            } else {
                http_response_code(404);
                echo '<!DOCTYPE html><html><head><title>404 Not Found</title></head><body style="font-family:sans-serif;display:flex;align-items:center;justify-content:center;min-height:100vh;margin:0;background:#f5f5f6;"><div style="text-align:center;"><h1 style="font-size:4rem;color:#003399;margin:0;">404</h1><p style="color:#666;">Halaman tidak ditemukan</p><a href="' . (defined('BASE_URL') ? BASE_URL : '') . '/login" style="color:#003399;text-decoration:none;font-weight:600;">Kembali ke Halaman Login</a></div></body></html>';
            }
            return;
        }

        foreach ($route['middleware'] as $middleware) {
            self::runMiddleware($middleware);
        }

        self::callHandler($route['handler'], $route['params']);
    }

    private static function addRoute(string $method, string $path, string $handler, array $middleware): void
    {
        $pattern = preg_replace('/\{([a-zA-Z_]+)\}/', '(?P<$1>[^/]+)', $path);
        $pattern = '#^' . $pattern . '$#';
        
        self::$routes[] = [
            'method' => $method,
            'pattern' => $pattern,
            'path' => $path,
            'handler' => $handler,
            'middleware' => $middleware,
        ];
    }

    private static function matchRoute(string $method, string $uri): ?array
    {
        foreach (self::$routes as $route) {
            if ($route['method'] !== $method && $route['method'] !== 'ANY') {
                continue;
            }
            
            if (preg_match($route['pattern'], $uri, $matches)) {
                $params = array_filter($matches, 'is_string', ARRAY_FILTER_USE_KEY);
                return [
                    'handler' => $route['handler'],
                    'params' => $params,
                    'middleware' => $route['middleware'],
                ];
            }
        }
        return null;
    }

    private static function callHandler(string $handler, array $params): void
    {
        if (str_contains($handler, '@')) {
            [$controller, $action] = explode('@', $handler);
            $controllerFile = CONTROLLERS_PATH . $controller . '.php';
            
            if (!file_exists($controllerFile)) {
                throw new Exception("Controller {$controller} not found");
            }
            
            require_once $controllerFile;
            
            if (!class_exists($controller)) {
                throw new Exception("Controller class {$controller} not found");
            }
            
            $instance = new $controller();
            if (!method_exists($instance, $action)) {
                throw new Exception("Action {$action} not found in {$controller}");
            }
            
            call_user_func_array([$instance, $action], $params);
        } else {
            if (is_callable($handler)) {
                call_user_func_array($handler, $params);
            } elseif (file_exists($handler)) {
                extract($params);
                require $handler;
            }
        }
    }

    private static function runMiddleware(string $middleware): void
    {
        $middlewareFile = BASE_PATH . 'middleware/' . $middleware . '.php';
        if (file_exists($middlewareFile)) {
            require_once $middlewareFile;
            if (class_exists($middleware)) {
                $instance = new $middleware();
                if (method_exists($instance, 'handle')) {
                    $instance->handle();
                }
            }
        }
    }
}
