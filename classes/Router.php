<?php

class Router
{
    private $routes = [];
    private $fn;

    function __construct($configPath=null)
    {
        $this->basename = basename('.');
        if($configPath){
            $configFile = "{$this->basename}/$configPath";
            if(!file_exists($configFile)){
                echo "File {$configPath}, Not exist!";
                $configFile = null;
                exit;
            }else{
                $this->config = $this->loadConfig($configPath);       
                $this->loadRoutes($this->config['router'] ?? []);
            }
        }

        if(isset($this->config['global']['cache_path']))
            $this->cachesPath = $this->config['global']['cache_path'];
        if(isset($this->config['global']['controller_path']))
            $this->controllersPath = $this->config['global']['controller_path'];
        if(isset($this->config['global']['template_path']))
            $this->templatesPath = $this->config['global']['template_path'];
        if(isset($this->config['global']['allow_extension']))
            $this->extension = $this->config['global']['allow_extension'];

        
        $this->cachesPath = "{$this->basename}/{$this->cachesPath}";
        $this->controllersPath = "{$this->basename}/{$this->controllersPath}";
        $this->templatesPath = "{$this->basename}/{$this->templatesPath}";
    }

    function getConfig()
    {
        // getconfig hanya bisa dibaca di cli mode
        return isset($this->config)?$this->config:'';
    }
    function getAuthData()
    {
        $authKeys = isset($this->data['global']['auth_data']) ? explode("|",$this->data['global']['auth_data']) : [] ;
        $authData = [];
        foreach ($authKeys as $key) {
           $authData[$key] = isset($_SESSION[$key])&&$_SESSION[$key] ? $_SESSION[$key] : ''; 
        }
        return $authData;
    }

    function apiResponse(int $code,array $result,$custom=[],bool $arg=false)
    {
        $response = array_merge($custom,['result'=>$result]);
        http_response_code($code);
        header('Content-Type: application/json; charset=utf-8');
        if(is_bool($arg) && $arg)
            $arg = JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT;
        echo json_encode($response,$arg);
        exit;
    }

    private function setConfig():void
    {
        foreach ($this->config as $key => $value) {
            $this->data[$key] = $value;
        }
        // hilangkan variable data router
        unset($this->data['router']);
        // hilangkan variable config
        unset($this->config);
    }

    private function loadConfig($file)
    {
        $lines = file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        $section = [];
        $config = [];

        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '' || $line[0] === ';') continue;
            
            if (preg_match('/^\[([a-zA-Z0-9_:]+)\]$/', $line, $matches)) {
                $section = explode(":",preg_replace('/\s+/','_',strtolower($matches[1])));
                continue;
            }
            if ($section[0] === null) continue;

            if (strpos($line, '=') !== false) {
                // $line = preg_replace('/;.*(?:\r?\n)?/',"\n",$line);
                if ($section[0] == 'router') {
                    $config[$section[0]][] = $line;
                } else {
                    [$key, $value] = array_map('trim', explode('=', $line, 2));
                    $addsubs=true;
                    if ($section[0] === 'global' || $section[0] === 'pwa') {
                        $config[$section[0]][$key] = $value;
                        $addsubs=false;
                    } else {
                        if(count($section)>1){
                            $config[$section[0]][$section[1]][$key] = $value;
                        }else{
                            $addsubs=false;
                        }
                    }
                    if(!$addsubs)
                        $config[$section[0]][$key] = $value;
                }
            }else{
                if (!isset($config[$section[0]])) {
                    $config[$section[0]] = [];
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

    private function includeHandler($route, $params, $http_code=200)
    {
        if (!strpos($route, '@')) return false;

        $params= array_merge(["http_code" => $http_code],$params);

        [$controller, $action] = explode('@', $route, 2);
        $controllerFile = "{$this->controllersPath}/{$controller}.php";
        
        if (file_exists($controllerFile)) {
            require_once $controllerFile;
            if (class_exists($controller)) {
                $obj = new $controller();
                if (method_exists($obj, $action)) {
                    $obj->$action($this,(object)$params);
                    return true;
                }
            }
        }

        return false;
    }

    static function dispatch($configPath,$cli=[])
    {
        !$cli || self::getCLI($cli);
        $self = new self($configPath);
        $self->fn = \__fn::get();
        $self->setConfig();
        
        if(isset($_SERVER['REQUEST_URI']) && isset($_SERVER['REQUEST_URI'])){
            
            $uri = $_SERVER['REQUEST_URI'];
            $method = $_SERVER['REQUEST_METHOD'];
            $path = parse_url($uri, PHP_URL_PATH);
            $method = strtoupper($method);
            $params = [];
            $path = str_replace(substr($_SERVER['SCRIPT_NAME'], 0, -10), '', $path);

            $errorHandler = $self->get('global.error_handler');
            // conditional templating cache
            if($self->get('global.cache_enable') === 'true' )
                $self->enableCache = true;
            
            if (!isset($self->routes[$method])) {
                http_response_code(405);
                if ($errorHandler) {
                    $self->includeHandler($errorHandler, $params, 405);
                } else {
                    echo "405 Method Not Allowed";
                }
                return;
            }            

            foreach ($self->routes[$method] as $route) {
                if (preg_match($route['regex'], $path, $matches)) {
                    array_shift($matches);
                    $params = array_combine($route['params'], $matches);

                    if (isset($route['options']['cors']) && $route['options']['cors']){
                        $origin = $route['options']['cors'] === 'true' ? '*' : $route['options']['cors'];  
                        header("Access-Control-Allow-Origin: $origin");
                    }

                    if (!empty($route['options']['auth']) && $route['options']['auth'] === 'true') {
                        session_start();
                        if($self->get('global.auth_data')){
                            $authKeys = explode('|', $self->get('global.auth_data') ?? '');
                            $authKeys = array_map('trim', $authKeys);
                            $missing = array_filter($authKeys, function ($key) {
                                return !isset($_SESSION[$key]);
                            });
                            
                            if (!empty($missing)) {
                                http_response_code(403);
                                if ($errorHandler) {
                                    $self->includeHandler($errorHandler, $params, 403);
                                } else {
                                    echo "403 Forbidden (missing auth data)";
                                }
                                return;
                            }
                        }
                    }

                    if ($self->includeHandler($route['handler'], $params)) return;
                    
                    http_response_code(500);
                    if ($errorHandler) {
                        $self->includeHandler($errorHandler, $params, 500);
                    } else {
                        echo "500 Controller not found.";
                    }
                    return;
                }
            }

            http_response_code(404);
            if ($errorHandler) {
                $self->includeHandler($errorHandler, $params, 404);
            } else {
                echo "404 Not Found";
            }
        }
    }

    
    // ini bagian layouting
    protected array $data = [];
    protected array $sections = [];
    protected ?string $parentLayout = null;
    protected bool $enableCache = false;
    protected array $includedFiles = [];

    protected function getCacheFilePath($layoutFile): string
    {
        $hash = md5($layoutFile . serialize($this->data));
        return $this->cachesPath . '/tpl_' . $hash . '.html';
    }

    protected function parseExtends(string &$content): void
    {
        if (preg_match('/\{\{@extends:([^\}]+)\}\}/', $content, $match)) {
            $this->parentLayout = trim($match[1]);
            $content = str_replace($match[0], '', $content);
        }
    }

    protected function parseSections(string &$content): void
    {
        preg_match_all('/\{\{@section:([^\}]+)\}\}(.*?)\{\{@endsection\}\}/s', $content, $matches, PREG_SET_ORDER);

        foreach ($matches as $match) {
            $sectionName = trim($match[1]);
            $sectionContent = trim($match[2]);
            $this->sections[$sectionName] = $sectionContent;
            $content = str_replace($match[0], '', $content);
        }
    }

    protected function parseComponents(string $content): string
    {
        return preg_replace_callback('/\{\{@component:\s*[\'"](.+?)["\']\s+with\s+(.+?)\}\}/', function ($matches) {
            $path = $matches[1];
            $params = $matches[2];

            // parse key="value" pairs
            preg_match_all('/(\w+)\s*=\s*["\'](.*?)["\']/', $params, $pairs, PREG_SET_ORDER);
            $data = [];
            foreach ($pairs as $pair) 
                $data[$pair[1]] = $pair[2];

            if (!file_exists($path))
                return "<!-- Component not found: $path -->";

            $component = new self();
            foreach ($data as $key => $val) 
                $component->set($key, $val);
            
            return $component->render($path);
        }, $content);
    }

    protected function injectYields(string $content): string
    {
        // 1) Placeholder dengan konten default:
        //    {{@section:header}} ...default... {{@endsection}}
        $content = preg_replace_callback(
            '/\{\{@section:([^\}]+)\}\}(.*?)\{\{@endsection\}\}/s',
            function ($m) {
                $name = trim($m[1]);
                $default = $this->parse($m[2]);           // proses variabel, dll.
                return $this->sections[$name] ?? $default;
            },
            $content
        );

        // 2) Placeholder tunggal tanpa default:
        //    {{@section:header}}
        $content = preg_replace_callback(
            '/\{\{@section:([^\}]+)\}\}/',
            function ($m) {
                $name = trim($m[1]);
                return $this->sections[$name] ?? '';      // kosong kalau tak diisi
            },
            $content
        );

        return $content;
    }

    function set($key, $value=null): void
    {
        if(is_array($key)){
            foreach ($key as $k => $v)
                if(!is_numeric($k))
                    $this->data[$k] = $v;                
        }elseif(is_string($key)){
            $this->data[$key] = $value;
        }
    }

    function get($string)
    {
        $string = explode('.',$string);
        $key = $string[0];
        unset($string[0]);
        $value = trim(implode('.',$string));
        if($value){
            if(isset($this->data[$key][$value])) 
                return $this->data[$key][$value];
        }else{
            if(isset($this->data[$key]))
                return $this->data[$key];
        }
        return null;
    }

    protected function getDataValue(string $path)
    {
        $parts = explode('.', $path);
        $value = $this->data;

        foreach ($parts as $part) {
            if (is_array($value) && isset($value[$part])) {
                $value = $value[$part];
            } elseif (is_object($value) && isset($value->$part)) {
                $value = $value->$part;
            } else {
                return '';
            }
        }
        return $value;
    }

    protected function parseHelpers(string $content): string
    {
        return preg_replace_callback('/\{\{\s*(upper|lower|date)\s+([\w\.]+)(?:\s+"(.*?)")?\s*\}\}/', function ($matches) {
            $func = $matches[1];
            $key = $matches[2];
            $format = $matches[3] ?? null;
            $value = $this->getDataValue($key);

            if ($func === 'upper') {
                return strtoupper((string)$value);
            } elseif ($func === 'lower') {
                return strtolower((string)$value);
            } elseif ($func === 'date') {
                $timestamp = strtotime((string)$value);
                if (!$timestamp) return '';
                return date($format ?: 'Y-m-d', $timestamp);
            }

            return '';
        }, $content);
    }

    protected function parseVariables(string $content): string
    {
        return preg_replace_callback('/\{\{@([\w\.]+)((?:\|[\w]+(?::[^|}]+)?)*)\}\}/', function ($matches) {
            $key = $matches[1];
            $filterString = $matches[2];

            $filters = [];
            if ($filterString) {
                preg_match_all('/\|([\w]+)(?::(["\'])(.*?)\2)?/', $filterString, $filterMatches, PREG_SET_ORDER);
                foreach ($filterMatches as $filterMatch) {
                    $filterName = $filterMatch[1];
                    $filterArg = $filterMatch[3] ?? null;
                    $filters[] = $filterArg !== null ? "$filterName:$filterArg" : $filterName;
                }
            }

            $value = $this->getDataValue($key);
            return $this->applyFilters((string)$value, $filters);
        }, $content);
    }

    protected function parseIncludes(string $content): string
    {
        // Pertama: parsing ekspresi dengan ~
        $content = preg_replace_callback('/\{\{\s*\'([^\']+)\'\s*~\s*(.*?)\s*~\s*\'([^\']+)\'\s*\}\}/', function ($matches) {
            $start = $matches[1];
            $var = $matches[2];
            $end = $matches[3];
            $middle = $this->getDataValue($var);
            $filePath = $start . $middle . $end;
            return $this->loadFile($filePath);
        }, $content);

        // Kedua: include biasa
        return preg_replace_callback('/\{\{\s*[\'"](.+?)["\']\s*\}\}/', function ($matches) {
            return $this->loadFile($matches[1]);
        }, $content);
    }

    protected function loadFile(string $filePath): string
    {
        if (file_exists($filePath)) {
            $this->includedFiles[] = $filePath;
            return $this->parse(file_get_contents($filePath));
        }
        return "<!-- File not found: $filePath -->";
    }

    protected function parseConditionals(string $content): string
    {
        return preg_replace_callback('/\{\{if (.+?)\}\}(.*?)\{\{endif\}\}/s', function ($matches) {
            $block = $matches[0];  // Semua isi dari {{if}} sampai {{endif}}
            $condition = trim($matches[1]);
            $body = $matches[2];

            // Pisahkan elseif dan else
            $parts = preg_split('/\{\{(elseif .+?|else)\}\}/', $body, -1, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY);

            $conditions = [];
            $currentCondition = $condition;

            // Parsing semua blok if, elseif, else
            for ($i = 0; $i < count($parts); $i += 2) {
                $contentBlock = $parts[$i];
                $next = $parts[$i + 1] ?? null;

                if (strpos($next, 'elseif') === 0) {
                    $conditions[] = ['condition' => $currentCondition, 'content' => $contentBlock];
                    $currentCondition = trim(substr($next, 7)); // Ambil kondisi elseif berikutnya
                } elseif ($next === 'else') {
                    $conditions[] = ['condition' => $currentCondition, 'content' => $contentBlock];
                    $conditions[] = ['condition' => 'else', 'content' => $parts[$i + 2] ?? ''];
                    break;
                } else {
                    $conditions[] = ['condition' => $currentCondition, 'content' => $contentBlock];
                    break;
                }
            }

            // Evaluasi kondisi satu per satu
            foreach ($conditions as $cond) {
                if ($cond['condition'] === 'else' || $this->evaluateCondition($cond['condition'])) {
                    return $this->parse($cond['content']);
                }
            }

            return '';
        }, $content);
    }

    protected function evaluateCondition(string $condition): bool
    {
        // Regex untuk ambil variabel, operator, dan value
        if (preg_match('/^([\w\.]+)\s*(===|!==|==|!=|>=|<=|>|<)\s*(.+)$/', $condition, $matches)) {
            $leftKey = trim($matches[1]);
            $operator = trim($matches[2]);
            $rightValue = trim($matches[3]);

            $leftValue = $this->getDataValue($leftKey);
            $rightValue = $this->convertValue($rightValue);

            switch ($operator) {
                case '===': return $leftValue === $rightValue;
                case '!==': return $leftValue !== $rightValue;
                case '==': return $leftValue == $rightValue;
                case '!=': return $leftValue != $rightValue;
                case '>=': return $leftValue >= $rightValue;
                case '<=': return $leftValue <= $rightValue;
                case '>': return $leftValue > $rightValue;
                case '<': return $leftValue < $rightValue;
            }
        }

        // Fallback: jika hanya variabel, cek apakah truthy
        $value = $this->getDataValue($condition);
        return !empty($value);
    }

    protected function convertValue(string $value)
    {
        // Tangani array kosong
        if ($value === '[]') return [];

        // Tangani boolean
        if ($value === 'true') return true;
        if ($value === 'false') return false;

        // Tangani string dengan kutip
        if (preg_match('/^[\'"](.*)[\'"]$/', $value, $match)) {
            return $match[1];
        }

        // Tangani angka
        if (is_numeric($value)) {
            return $value + 0; // Convert to int or float
        }

        return $value;
    }
    
    protected function parseLoops(string $content): string
    {
        return preg_replace_callback('/\{\{foreach (.+?) in (.+?)\}\}(.*?)\{\{endforeach\}\}/s', function ($matches) {
            $itemName = trim($matches[1]);
            $listName = trim($matches[2]);
            $body = $matches[3];

            $list = $this->getDataValue($listName);

            if (!is_array($list)) {
                return '';
            }

            $output = [];
            // $num = 1;
            foreach ($list as $item) {
                $this->set($itemName, $item);
                // Hilangkan newline hanya di awal dan akhir blok item
                $parsed = preg_replace('/^[\r\n]+\s{4}|[\r\n]+$/', '', $this->parse($body));
                $output[] = $parsed;
                // $output[] = $num === count($list) ? preg_replace('/[\r\n]+/',"",$parsed) : $parsed;
                // $num++;
            }

            // Gabungkan semua item tanpa extra break line
            return implode('', $output); // << Perhatikan: Jangan pakai \n di sini
        }, $content);
    }

    protected function applyFilters($value, array $filters): string
    {
        foreach ($filters as $filter) {
            if (strpos($filter, ':') !== false) {
                [$name, $arg] = explode(':', $filter, 2);
                $arg = trim($arg, "\"'");
            } else {
                $name = $filter;
                $arg = null;
            }

            switch ($name) {
                case 'upper':
                    $value = strtoupper($value);
                    break;
                case 'lower':
                    $value = strtolower($value);
                    break;
                case 'ucwords':
                    $value = ucwords($value);
                    break;
                case 'date':
                    $timestamp = strtotime($value);
                    $value = $timestamp ? date($arg ?: 'Y-m-d', $timestamp) : '';
                    break;
                default:
                    // bisa tambahkan custom helper di sini
                    break;
            }
        }
        return $value;
    }

    protected function removeComments(string $content): string
    {
        return preg_replace('/\{\{\-\-.*?\-\-\}\}/s', '', $content);
    }

    protected function parse(string $content): string
    {
        $content = $this->removeComments($content);
        $content = $this->parseComponents($content);
        $content = $this->parseIncludes($content);
        $content = $this->parseConditionals($content);
        $content = $this->parseLoops($content);
        $content = $this->parseHelpers($content);
        $content = $this->parseVariables($content);
        
        return $content;
    }

    function render($layoutFile=null): string
    {
        if (!file_exists($layoutFile)) {
            return "<!-- Layout file not found: {$layoutFile} -->";
        }

        $cacheFile = $this->getCacheFilePath($layoutFile);
        $metaFile = $cacheFile . '.meta';

        // Gunakan cache jika file belum berubah
        if ($this->enableCache && file_exists($cacheFile) && file_exists($metaFile)) {
            $meta = json_decode(file_get_contents($metaFile), true);
            $expired = false;

            foreach ($meta['files'] as $file => $lastModified) {
                if (!file_exists($file) || filemtime($file) > $lastModified) {
                    $expired = true;
                    break;
                }
            }

            if (!$expired) {
                return file_get_contents($cacheFile);
            }
        }

        // --- Proses rendering seperti biasa ---
        $content = file_get_contents($layoutFile);
        $this->parseExtends($content);
        $this->parseSections($content);

        if ($this->parentLayout && file_exists($this->parentLayout)) {
            $layoutContent = file_get_contents($this->parentLayout);
            $layoutContent = $this->injectYields($layoutContent);
            $output = $this->parse($layoutContent);
        } else {
            $output = $this->parse($content);
        }

        // Simpan cache
        if ($this->enableCache) {
            // Catat file yang terlibat: layoutFile, parentLayout, includes
            $usedFiles = array_unique(array_merge(
                [$layoutFile],
                $this->parentLayout ? [$this->parentLayout] : [],
                $this->includedFiles
            ));

            $metaData = [
                'files' => []
            ];

            foreach ($usedFiles as $file) {
                $metaData['files'][$file] = file_exists($file) ? filemtime($file) : 0;
            }

            file_put_contents($cacheFile, $output);
            file_put_contents($metaFile, json_encode($metaData));
        }

        return $output;
    }

    // DB Connection Mysql
    protected $extension;
    function dbConnect(...$prms)
    {
        $data = isset($this->data['database'][$prms[0]]) ? $this->data['database'][$prms[0]] : [] ; 
        if(!$data)
            $data = isset($this->data['database']) ? $this->data['database'] : [] ; 
        if(!$data){
            if(isset($prms[0]) && $prms[0])
                $data['user'] = $prms[0];
            if(isset($prms[1]) && $prms[1])
                $data['pass'] = $prms[1];
            if(isset($prms[2]) && $prms[2])
                $data['name'] = $prms[2];
            if(isset($prms[3]) && $prms[3])
                $data['host'] = $prms[3];
            if(isset($prms[4]) && $prms[4])
                $data['port'] = $prms[4];
            if(isset($prms[5]) && $prms[5])
                $data['type'] = $prms[5];
        }

        $user = isset($data['user'])?$data['user']:'';
        $pass = isset($data['pass'])?$data['pass']:'';
        $name = isset($data['name'])?$data['name']:'';
        $host = isset($data['host'])?$data['host']:'localhost';
        $port = isset($data['port'])?$data['port']:'3306';
        $type = isset($data['type'])?$data['type']:'mysql';  

        try {
            $pdo = new \PDO(sprintf('%s:host=%s;port=%s%s',$type,$host,$port,$name?";dbname=$name":''),$user,$pass);
            $pdo->setAttribute(\PDO::ATTR_ERRMODE,\PDO::ERRMODE_EXCEPTION);
            return new \dbHandler($pdo,$this->extension);
        } catch (\PDOException $e) {
            var_export("Connection Error: " . $e->getMessage());
        }
    }

    function getExtension($mimeType):string
    {
        return \dbHandler::getExtension($mimeType);
    }
    
    function getMimeFile($filePath,$isfile=true):string
    {
        return \dbHandler::getMimeFile($filePath,$isfile);
    }
    
    // CLI Command
    protected string $cachesPath = 'caches';
    protected string $controllersPath = 'controllers';
    protected string $templatesPath = 'templates';
    protected static function getCLI($prms){
        if (isset($prms[1]) && $prms[1] == 'clear:caches') {
            $self = new self();
            if (!is_dir($self->cachesPath)) {
                echo "Cache directory not found.\n";
                exit;
            }
            $files = glob($self->cachesPath . '/*.html*');
            if (empty($files)) {
                echo "No cache files to delete.\n";
                exit;
            }
            foreach ($files as $file) {
                if (unlink($file)) {
                    echo "Deleted: " . basename($file) . "\n";
                } else {
                    echo "Failed to delete: " . basename($file) . "\n";
                }
            }
            echo "Cache cleared.\n";
            exit;
        }

        if (isset($prms[2]) && ($prms[1] === 'make:pwa' || $prms[1] === 'make:handlers')) {
            
            $self = new self("{$prms[2]}.ini");
            $routes = $self->getConfig()['router'] ?? [];
            $global = $self->getConfig()['global'] ?? [];
            $pwa = $self->getConfig()['pwa'] ?? [];
            $handlers = [];

            if(isset($global['error_handler']) && $global['error_handler']){
                $routes['error_handler'] = $global['error_handler'];
            }

            if($prms[1] == 'make:pwa'){
                if (empty($pwa)) {
                    echo "⚠️ [pwa] path not set on {$prms[2]}.ini\n";
                    exit;
                }

                $manifest["name"] = $pwa['name'] ?? 'PHP App iniStyle support';
                $manifest["short_name"] = $pwa['short_name'] ?? 'I-App';
                if(isset($pwa['description']) && trim($pwa['description']))
                    $manifest["description"] = $pwa['description'];
                $manifest["start_url"] = $pwa['start_url'] ?? './';
                $manifest["display"] = $pwa['display'] ?? 'standalone';
                $manifest["background_color"] = $pwa['background_color'] ?? '#ffffff';
                $manifest["theme_color"] = $pwa['theme_color'] ?? '#3367D6';
                $manifest["icons"] = [];

                if (!empty($pwa['icon_192'])) {
                    $manifest['icons'][] = [
                        "src" => $pwa['icon_192'],
                        "sizes" => "192x192",
                        "type" => "image/png"
                    ];
                }
                if (!empty($pwa['icon_512'])) {
                    $manifest['icons'][] = [
                        "src" => $pwa['icon_512'],
                        "sizes" => "512x512",
                        "type" => "image/png"
                    ];
                }

                file_put_contents("{$self->basename}/manifest.json", json_encode($manifest, JSON_PRETTY_PRINT));
                // file_put_contents("{$self->basename}/manifest.json", json_encode($manifest, JSON_PRETTY_echo | JSON_UNESCAPED_SLASHES));
                echo "✔ manifest.json success created based on {$prms[2]}.ini\n";

                // Service Worker
                $sw = <<<JS
self.addEventListener('install', function(e) {
    console.log('Service Worker: Installed');
    self.skipWaiting();
});
self.addEventListener('activate', function(e) {
    console.log('Service Worker: Activated');
});
self.addEventListener('fetch', function(e) {
    e.respondWith(fetch(e.request));
});
JS;

                file_put_contents("{$self->basename}/service-worker.js", $sw);
                echo "✔ service-worker.js success created!\n";

                echo "\n📌 Add this in your <script> HTML after:\n";
                echo "<link rel=\"manifest\" href=\"manifest.json\">\n";
                echo "<meta name=\"theme-color\" content=\"{$manifest['theme_color']}\">\n";
                if (!empty($pwa['icon_192']))
                    echo "<link rel=\"icon\" href=\"{$pwa['icon_192']}\" sizes=\"192x192\">\n";

                echo "\n📌 Add this in <script> HTML to register service worker:\n";
                echo "<script>\n";
                echo "if ('serviceWorker' in navigator) {\n";
                echo "  navigator.serviceWorker.register('service-worker.js')\n";
                echo "    .then(() => console.log('✅ Service Worker registered'))\n";
                echo "    .catch(err => console.error('⚠️ Fail register SW:', err));\n";
                echo "}\n";
                echo "</script>\n";
                exit;
            }

            if($prms[1] == 'make:handlers'){

                is_dir($self->cachesPath) || mkdir($self->cachesPath,0777);
                is_dir($self->controllersPath) || mkdir($self->controllersPath,0777);
                is_dir($self->templatesPath) || mkdir($self->templatesPath,0777);

                foreach ($routes as $key => $line) {
                    $handler = trim($line);
                    if (strpos($handler, '@') === false) continue;
                    [$controller, $method] = explode('@', $handler, 2);
                    $handlers[$controller][] = $method;
                }

                foreach ($handlers as $controller => $methods) {
                    $file = "{$self->controllersPath}/{$controller}.php";
                    $classDef = "<?php\n\nclass $controller\n{\n";

                    $uniqueMethods = array_unique($methods);

                    foreach ($uniqueMethods as $method) {
                        $classDef .= "    function $method(\$self,\$params)\n    {\n        // TODO: implement $method\n    }\n\n";
                    }

                    $classDef .= "}\n";

                    if (!file_exists($file)) {
                        file_put_contents($file, $classDef);
                        echo "✔ Handler created : {$self->controllersPath}/$controller.php\n";
                    } else {
                        // Append method if not exists
                        $content = file_get_contents($file);
                        $updated = false;
                        foreach ($uniqueMethods as $method) {
                            if (!preg_match('/function\\s+' . preg_quote($method, '/') . '\\s*\\(/', $content)) {
                                $append = "\n    function $method(\$self,\$params)\n    {\n        // TODO: implement $method\n    }\n";
                                $content = preg_replace('/\\}\\s*$/', $append . "\n}", $content);
                                $updated = true;
                            }
                        }
                        if ($updated) {
                            file_put_contents($file, $content);
                            echo "✔ Updated handler : {$self->controllersPath}/$controller.php\n";
                        } else {
                            echo "• Skipped (already exists) : {$self->controllersPath}/$controller.php\n";
                        }
                    }
                }
                exit;
            }
        }
        exit;
    }
}