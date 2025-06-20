<?php

class Router
{
    private $routes = [];
    private $config = [];
    private $globalOptions = [];

    public function __construct($configPath)
    {
        $this->config = $this->loadConfig($configPath);
        $this->globalOptions = $this->config['global'] ?? [];        
        $this->loadRoutes($this->config['router'] ?? []);
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
                // preg_match('/\[(.*?)\]/', $line, $matches);
                // var_dump($matches);
                // if($matches){
                //     // meletakkan opsi di akhir
                //     $line = str_replace($matches[0], '', $line);
                //     $line = $line.' '.$matches[0];
                // }
                if($section == 'router'){
                    $config[$section][] = $line;
                }else{
                    [$key, $value] = array_map('trim', explode('=', $line, 2));
                    $config[$section][$key] = $value;
                }
            }
        }

        return $config;
    }

    private function loadRoutes($routes)
    {
        foreach ($routes as $line => $value) {
            preg_match('/\[(.*?)\]/', $value, $matches);
            $options = [];
            if($matches){
                $value = str_replace($matches[0], '', $value);
                parse_str(str_replace(',', '&', $matches[1]),$options);
            }
            [$key, $handler] = array_map('trim', explode('=', $value, 2));
            // Pisahkan metode, path & parameter
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

    public function dispatch($uri, $method)
    {
        $path = parse_url($uri, PHP_URL_PATH);
        $method = strtoupper($method);
        // ini memotong kelebihan url
        $path = str_replace(substr($_SERVER['SCRIPT_NAME'],0,-10),'',$path);
        if (!isset($this->routes[$method])) {
            http_response_code(405);
            echo "405 Method Not Allowed";
            return;
        }        
        foreach ($this->routes[$method] as $route) {
            if (preg_match($route['regex'], $path, $matches)) {
                array_shift($matches);
                $params = array_combine($route['params'], $matches);

                if (isset($route['options']['cors']) && $route['options']['cors'] === 'true') 
                    header('Access-Control-Allow-Origin: *');                

                if (!empty($route['options']['auth']) && $route['options']['auth'] === 'true') {
                    session_start();
                    if (!isset($_SESSION['user'])) {
                        http_response_code(403);
                        echo "Forbidden";
                        return;
                    }
                }

                [$controller, $action] = explode('@', $route['handler']);
                $controllerFile = __DIR__ . "/controllers/{$controller}.php";

                if (file_exists($controllerFile)) {
                    require_once $controllerFile;
                    $obj = new $controller();
                    return call_user_func_array([$obj, $action], $params);
                }

                http_response_code(500);
                echo "Controller not found.";
                return;
            }
        }

        http_response_code(404);
        echo "404 Not Found";
    }
}