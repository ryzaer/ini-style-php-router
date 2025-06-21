<?php

require_once 'Router.php'; // pastikan Router bisa di-load

if ($argc < 2 || $argv[1] !== 'make:handlers') {
    echo "Gunakan: php cli.php make:handlers\n";
    exit;
}

$configFile = 'config.ini';
$controllersPath = __DIR__ . '/controllers';
is_dir($controllersPath) || mkdir($controllersPath,0777);

$router = new Router($configFile);
$routes = $router->getConfig()['router'] ?? [];
$global = $router->getConfig()['global'] ?? [];
$handlers = [];

if(isset($global['error_handler']) && $global['error_handler']){
    $routes['error_handler'] = $global['error_handler'];
}

foreach ($routes as $key => $line) {
    $handler = trim($line);
    if (strpos($handler, '@') === false) continue;
    var_dump($handler);

    [$controller, $method] = explode('@', $handler, 2);
    $handlers[$controller][] = $method;
}

foreach ($handlers as $controller => $methods) {
    $file = "$controllersPath/{$controller}.php";
    $classDef = "<?php\n\nclass $controller\n{\n";

    $uniqueMethods = array_unique($methods);

    foreach ($uniqueMethods as $method) {
        $classDef .= "    public function $method(\$params, \$self)\n    {\n        // TODO: implement $method\n    }\n\n";
    }

    $classDef .= "}\n";

    if (!file_exists($file)) {
        file_put_contents($file, $classDef);
        echo "✔ Controller created: $file\n";
    } else {
        // Append method if not exists
        $content = file_get_contents($file);
        $updated = false;
        foreach ($uniqueMethods as $method) {
            if (!preg_match('/function\\s+' . preg_quote($method, '/') . '\\s*\\(/', $content)) {
                $append = "\n    public function $method(\$params, \$self)\n    {\n        // TODO: implement $method\n    }\n";
                $content = preg_replace('/\\}\\s*$/', $append . "\n}", $content);
                $updated = true;
            }
        }
        if ($updated) {
            file_put_contents($file, $content);
            echo "✔ Updated controller: $file\n";
        } else {
            echo "• Skipped (already exists): $file\n";
        }
    }
}
