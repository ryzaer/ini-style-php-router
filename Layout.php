<?php
// ✔ Variabel biasa dan nested (user.name)
// ✔ Include file statis dan dinamis ('file/' ~ var ~ '.html')
// ✔ Loop dan kondisi
// ✔ Komentar template yang tidak tampil

class Layout
{
    protected string $layoutFile;
    protected array $data = [];
    protected array $sections = [];
    protected ?string $parentLayout = null;
    protected string $cacheDir = 'cache';
    protected bool $enableCache = false;
    protected array $includedFiles = [];

    public function __construct(string $layoutFile)
    {
        $this->layoutFile = $layoutFile;
    }

    protected function getCacheFilePath(): string
    {
        $hash = md5($this->layoutFile . serialize($this->data));
        return $this->cacheDir . '/tpl_' . $hash . '.html';
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
            foreach ($pairs as $pair) {
                $data[$pair[1]] = $pair[2];
            }

            if (!file_exists($path)) {
                return "<!-- Component not found: $path -->";
            }

            $component = new self($path);
            foreach ($data as $key => $val) {
                $component->set($key, $val);
            }
            return $component->render();
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

    public function set(string $key, $value): void
    {
        $this->data[$key] = $value;
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
        return preg_replace_callback('/\{\{if\s+([\w\.]+)\}\}(.*?)\{\{endif\}\}/s', function ($matches) {
            return $this->getDataValue($matches[1]) ? $this->parse($matches[2]) : '';
        }, $content);
    }

    protected function parseLoops(string $content): string
    {
        return preg_replace_callback('/\{\{foreach\s+(\w+)\s+in\s+([\w\.]+)\}\}(.*?)\{\{endforeach\}\}/s', function ($matches) {
            $itemVar = $matches[1];
            $dataKey = $matches[2];
            $block = $matches[3];

            $list = $this->getDataValue($dataKey);
            if (!is_array($list)) return '';

            $result = '';
            foreach ($list as $item) {
                $tempBlock = str_replace('{{@' . $itemVar . '}}', $item, $block);
                $result .= $this->parse($tempBlock);
            }
            return $result;
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

    public function render(): string
    {
        if (!file_exists($this->layoutFile)) {
            return "<!-- Layout file not found: {$this->layoutFile} -->";
        }

        $cacheFile = $this->getCacheFilePath();
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
        $content = file_get_contents($this->layoutFile);
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
                [$this->layoutFile],
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
}
