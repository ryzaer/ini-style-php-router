<?php

class Router
{
    private $routes = [];
    private $config = [];

    public function __construct($configPath)
    {
        $this->config = $this->loadConfig($configPath);       
        $this->loadRoutes($this->config['router'] ?? []);
    }

    public function getConfig()
    {
        return $this->config;
    }

    private function loadConfig($file)
    {
        $lines = file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        $section = null;
        $config = [];

        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '' || $line[0] === ';') continue;

            if (preg_match('/^\[([a-zA-Z0-9_]+)\]$/', $line, $matches)) {
                $section = strtolower($matches[1]);
                continue;
            }

            if ($section === null) continue;

            if (!isset($config[$section])) {
                $config[$section] = [];
            }

            if (strpos($line, '=') !== false) {
                if ($section == 'router') {
                    $config[$section][] = $line;
                } else {
                    [$key, $value] = array_map('trim', explode('=', $line, 2));
                    $config[$section][$key] = $value;
                }
            }
        }

        return $config;
    }

    private function loadRoutes($routes)
    {
        $this->config['router'] = [];
        foreach ($routes as $line => $value) {
            preg_match('/\[(.*?)\]/', $value, $matches);
            $options = [];
            if ($matches) {
                $value = str_replace($matches[0], '', $value);
                parse_str(str_replace(',', '&', $matches[1]), $options);
            }
            [$key, $handler] = array_map('trim', explode('=', $value, 2));
            
            $this->config['router'][$key] = $handler;

            if (preg_match('/^([A-Z|]+)\s+([^\[]+)?$/', $key, $matches)) {
                $methods = explode('|', $matches[1]);
                $path = trim($matches[2]);
                $optionsLeft = [];

                foreach ($methods as $method) {
                    $this->routes[$method][] = [
                        'path' => $path,
                        'handler' => $handler,
                        'options' => $options,
                        'regex' => $this->compilePathToRegex($path),
                        'params' => $this->extractParams($path),
                    ];
                }
            }
        }
    }

    private function compilePathToRegex($path)
    {
        return '#^' . preg_replace('/\{[^\/]+\}/', '([^/]+)', $path) . '$#';
    }

    private function extractParams($path)
    {
        preg_match_all('/\{([^\/]+)\}/', $path, $matches);
        return $matches[1] ?? [];
    }

    private function includeHandler($route, $params, $http_code = 0)
    {
        if ($http_code && is_numeric($http_code)) {
            http_response_code($http_code);
        }

        if (!strpos($route, '@')) return false;

        [$controller, $action] = explode('@', $route, 2);
        $controllerFile = __DIR__ . "/controllers/{$controller}.php";

        if (file_exists($controllerFile)) {
            require_once $controllerFile;
            if (class_exists($controller)) {
                $obj = new $controller();
                if (method_exists($obj, $action)) {
                    $obj->$action((object)$params,$this,$http_code);
                    return true;
                }
            }
        }

        return false;
    }

    public function dispatch($uri, $method)
    {
        $path = parse_url($uri, PHP_URL_PATH);
        $method = strtoupper($method);
        $params = [];
        $path = str_replace(substr($_SERVER['SCRIPT_NAME'], 0, -10), '', $path);

        $errorHandler = $this->config['global']['error_handler'] ?? null;

        if (!isset($this->routes[$method])) {
            if ($errorHandler) {
                $this->includeHandler($errorHandler, $params, 405);
            } else {
                http_response_code(405);
                echo "405 Method Not Allowed";
            }
            return;
        }

        foreach ($this->routes[$method] as $route) {
            if (preg_match($route['regex'], $path, $matches)) {
                array_shift($matches);
                $params = array_combine($route['params'], $matches);

                if (isset($route['options']['cors']) && $route['options']['cors'] === 'true') {
                    header('Access-Control-Allow-Origin: *');
                }

                if (!empty($route['options']['auth']) && $route['options']['auth'] === 'true') {
                    session_start();

                    $authKeys = explode('|', $this->config['global']['auth_data'] ?? '');
                    $authKeys = array_map('trim', $authKeys);
                    $missing = array_filter($authKeys, function ($key) {
                        return !isset($_SESSION[$key]);
                    });

                    if (!empty($missing)) {
                        if ($errorHandler) {
                            $this->includeHandler($errorHandler, $params, 403);
                        } else {
                            http_response_code(403);
                            echo "403 Forbidden (missing auth data)";
                        }
                        return;
                    }
                }

                if ($this->includeHandler($route['handler'], $params)) return;

                if ($errorHandler) {
                    $this->includeHandler($errorHandler, $params, 500);
                } else {
                    http_response_code(500);
                    echo "500 Controller not found.";
                }
                return;
            }
        }

        if ($errorHandler) {
            $this->includeHandler($errorHandler, $params, 404);
        } else {
            http_response_code(404);
            echo "404 Not Found";
        }
    }
}
