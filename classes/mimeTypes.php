<?php

class mimeTypes
{
    protected $mimeMap = [];

    public function __construct()
    {
        $mimeFile = __DIR__.'/mime.types';
        if (!file_exists($mimeFile)) {
            throw new Exception("File mime.types tidak ditemukan: {$mimeFile}");
        }
        $this->parseMimeFile($mimeFile);
    }

    protected function parseMimeFile($mimeFile)
    {
        $lines = file($mimeFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

        foreach ($lines as $line) {
            // Abaikan baris komentar
            if (strpos($line, '#') === 0) {
                continue;
            }

            // Pisahkan tipe dan ekstensi
            $parts = preg_split('/\s+/', trim($line));

            if (count($parts) > 1) {
                $mimeType = array_shift($parts); // Ambil tipe mime
                foreach ($parts as $ext) {
                    // Tambahkan ke peta ekstensi
                    $this->mimeMap[$mimeType][] = $ext;
                }
            }
        }
    }

    public function getExtension($mimeType)
    {
        if (isset($this->mimeMap[$mimeType])) {
            // Ambil ekstensi pertama yang ditemukan
            return $this->mimeMap[$mimeType][0];
        }
        return 'unknown';
    }

    public function getAllExtensions($mimeType)
    {
        return $this->mimeMap[$mimeType] ?? [];
    }

    public function getMimeMap()
    {
        return $this->mimeMap;
    }
}