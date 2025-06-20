<?php
$cacheDir = __DIR__ . '/cache';

if (!is_dir($cacheDir)) {
    echo "Cache directory not found.\n";
    exit;
}

$files = glob($cacheDir . '/*.html*');

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
