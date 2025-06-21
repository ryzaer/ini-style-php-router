<?php

// pastikan Router bisa di-load
// sintaks ;
//  php cli.php config make:handlers;
//  php cli.php config make:pwa;
//  php cli.php clear:caches;
require_once 'Router.php'; 

$cachesPath = __DIR__ . '/caches';
is_dir($cachesPath) || mkdir($cachesPath,0777);

$controllersPath = __DIR__ . '/controllers';
is_dir($controllersPath) || mkdir($controllersPath,0777);

if (isset($argv[2])) {

    $configFile = __DIR__ . "/{$argv[1]}.ini";
    if(!file_exists($configFile)){
        echo "File {$argv[1]}.ini, Not exist!";
        exit;
    }

    $router = new Router($configFile);
    $routes = $router->getConfig()['router'] ?? [];
    $global = $router->getConfig()['global'] ?? [];
    $pwa = $router->getConfig()['pwa'] ?? [];
    $handlers = [];

    if(isset($global['error_handler']) && $global['error_handler']){
        $routes['error_handler'] = $global['error_handler'];
    }
    if($argv[2] == 'make:pwa'){
        if (empty($pwa)) {
            echo "⚠️  Bagian [pwa] tidak ditemukan di {$argv[1]}.ini\n";
            exit;
        }

        $manifest = [
            "name" => $pwa['name'] ?? 'My PHP App',
            "short_name" => $pwa['short_name'] ?? 'PHPApp',
            "start_url" => $pwa['start_url'] ?? './',
            "display" => $pwa['display'] ?? 'standalone',
            "background_color" => $pwa['background_color'] ?? '#ffffff',
            "theme_color" => $pwa['theme_color'] ?? '#3367D6',
            "icons" => []
        ];

        // if (!is_dir(__DIR__ . 'assets/image/icons')) {
        //     mkdir(__DIR__ . 'assets/image/icons',0777,true);
        //     echo "✔ Folder 'assets/image/icons/' dibuat\n";
        // }

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

        file_put_contents('manifest.json', json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
        echo "✔ manifest.json berhasil dibuat berdasarkan {$argv[1]}.ini\n";

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

        file_put_contents('service-worker.js', $sw);
        echo "✔ service-worker.js berhasil dibuat\n";

        echo "\n📌 Tambahkan ini di <head> HTML kamu:\n";
        echo "<link rel=\"manifest\" href=\"manifest.json\">\n";
        echo "<meta name=\"theme-color\" content=\"{$manifest['theme_color']}\">\n";
        if (!empty($pwa['icon_192']))
            echo "<link rel=\"icon\" href=\"{$pwa['icon_192']}\" sizes=\"192x192\">\n";

        echo "\n📌 Tambahkan ini di <script> HTML untuk register service worker:\n";
        echo "<script>\n";
        echo "if ('serviceWorker' in navigator) {\n";
        echo "  navigator.serviceWorker.register('service-worker.js')\n";
        echo "    .then(() => console.log('✅ Service Worker registered'))\n";
        echo "    .catch(err => console.error('⚠️ Gagal register SW:', err));\n";
        echo "}\n";
        echo "</script>\n";
        exit;
    }

    if($argv[2] == 'make:handlers'){
        foreach ($routes as $key => $line) {
            $handler = trim($line);
            if (strpos($handler, '@') === false) continue;
            [$controller, $method] = explode('@', $handler, 2);
            $handlers[$controller][] = $method;
        }

        foreach ($handlers as $controller => $methods) {
            $file = "$controllersPath/{$controller}.php";
            $classDef = "<?php\n\nclass $controller\n{\n";

            $uniqueMethods = array_unique($methods);

            foreach ($uniqueMethods as $method) {
                $classDef .= "    public function $method(\$self,\$params,\$http_code)\n    {\n        // TODO: implement $method\n    }\n\n";
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
                        $append = "\n    public function $method(\$self,\$params,\$http_code)\n    {\n        // TODO: implement $method\n    }\n";
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
        exit;
    }

    
}
if (isset($argv[1]) && $argv[1] == 'clear:caches') {
    if (!is_dir($cachesPath)) {
        echo "Cache directory not found.\n";
        exit;
    }
    $files = glob($cachesPath . '/*.html*');
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
}



