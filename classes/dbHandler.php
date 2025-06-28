<?php
class dbHandler
{
    protected $handler;
    protected $allowBlob=false;
    protected $extension='mp4|mp3|jpg|png|gif|webp|pdf|doc|docx|xls|xlsx|txt|csv|zip|rar|7z';
    protected $format;
    function __construct($handler,$extension=null) {
        $this->handler = $handler;
        if($extension)
            $this->extension = $extension;

        $this->format = $this->extension;
        return $this;
    }

    function prepare(...$query)
    {
        return $this->handler->prepare(...$query);
    }
    function query(...$query)
    {
        return $this->handler->query(...$query);
    }
    
    function quote(...$query)
    {
        return $this->handler->quote(...$query);
    }

    function exec(...$query)
    {
        return $this->handler->exec(...$query);
    }

    function blob($extension=null) {
        if($extension)
            $this->format = $extension;
        $this->allowBlob = true;
        return $this;
    }
    
    function insert(string $table, array $data): int {
        $keys = array_keys($data);
        $placeholders = array_map(fn($k) => ":$k", $keys);
        $sql = "INSERT INTO `$table` (" . implode(',', $keys) . ") VALUES (" . implode(',', $placeholders) . ")";
        $stmt = $this->handler->prepare($sql);

        foreach ($data as $key => $value) {
            $isBlob = $this->allowBlob && $this->checkFile($value);
            $vals = $isBlob ? $this->isAllowFile($value, $this->format) : $value;
            $type = $isBlob ? PDO::PARAM_LOB : ( is_numeric($vals) ? PDO::PARAM_INT : PDO::PARAM_STR );
            $stmt->bindValue(":$key", $vals, $type);
        }
        $this->format = $this->extension;
        $this->allowBlob = false;
        $stmt->execute();
        return (int) $this->handler->lastInsertId();
    } 

    // public function update(string $table, array $data, array $where): bool {
    //     $setParts = [];
    //     foreach ($data as $key => $_) {
    //         $setParts[] = "$key = :$key";
    //     }
    //     $whereParts = [];
    //     foreach ($where as $key => $_) {
    //         $whereParts[] = "$key = :w_$key";
    //     }

    //     $sql = "UPDATE `$table` SET " . implode(',', $setParts) . " WHERE " . implode(' AND ', $whereParts);
    //     $stmt = $this->handler->prepare($sql);

    //     foreach ($data as $key => $value) {
    //         $isBlob = $this->allowBlob && $this->checkFile($value);
    //         $vals = $isBlob ? $this->isAllowFile($value, $this->format) : $value;
    //         $type = $isBlob ? PDO::PARAM_LOB : ( is_numeric($vals) ? PDO::PARAM_INT : PDO::PARAM_STR );
    //         $stmt->bindValue(":$key", $vals, $type);
    //     }

    //     foreach ($where as $key => $value) {
    //         $param = is_numeric($value) ? PDO::PARAM_INT : PDO::PARAM_STR;
    //         $stmt->bindValue(":w_$key", $value, $param);
    //     }

    //     $this->format = $this->extension;
    //     $this->allowBlob = false;

    //     return $stmt->execute();
    // }

    public function update(string $table, array $data, array $where, bool $useLike = false, array $orWhere = []): bool {
        

        $setParts = [];
        foreach (array_keys($data) as $col) {
            $setParts[] = "$col = :set_$col";
        }

        $sql = "UPDATE `$table` SET " . implode(", ", $setParts);

        $params = [];
        $conditions = [];

        foreach ($where as $key => $value) {
            if ($useLike) {
                $conditions[] = "$key LIKE :$key";
                $params[":$key"] = "%$value%";
            } else {
                $conditions[] = "$key = :$key";
                $params[":$key"] = $value;
            }
        }

        if (!empty($orWhere)) {
            $orConditions = [];
            foreach ($orWhere as $key => $value) {
                if ($useLike) {
                    $orConditions[] = "$key LIKE :or_$key";
                    $params[":or_$key"] = "%$value%";
                } else {
                    $orConditions[] = "$key = :or_$key";
                    $params[":or_$key"] = $value;
                }
            }
            if (!empty($orConditions)) {
                $conditions[] = '( ' . implode(' OR ', $orConditions) . ' )';
            }
        }

        if (!empty($conditions)) {
            $sql .= ' WHERE ' . implode(' AND ', $conditions);
        }

        $stmt = $this->handler->prepare($sql);

        foreach ($data as $key => $value) {
            $useBlob = $this->allowBlob && $this->checkFile($value);
            $vals = $isBlob ? $this->isAllowFile($value, $this->format) : $value;
            $type = $isBlob ? PDO::PARAM_LOB : ( is_numeric($vals) ? PDO::PARAM_INT : PDO::PARAM_STR );
            $stmt->bindValue(":set_$key", $vals, $type);
            // if ($useBlob && is_file($value)) {
            //     $stmt->bindValue(":set_$key", file_get_contents($value), PDO::PARAM_LOB);
            // } else {
            //     $stmt->bindValue(":set_$key", $value);
            // }
        }

        foreach ($params as $paramKey => $paramValue) {
            $stmt->bindValue($paramKey, $paramValue);
        }

        return $stmt->execute();
    }

    public function delete(string $table, array $where): bool {
        $parts = [];
        foreach ($where as $key => $_) {
            $parts[] = "$key = :$key";
        }
        $sql = "DELETE FROM `$table` WHERE " . implode(' AND ', $parts);
        $stmt = $this->handler->prepare($sql);

        foreach ($where as $key => $value) {
            $stmt->bindValue(":$key", $value);
        }
        return $stmt->execute();
    }

    function select(string $table, array $where = [], bool $useLike = false, array $orWhere = []): array {

        $columns = '*';
        $order = '';
        $limit = '';
        $groupBy = '';
        $having = '';

        if (preg_match_all('/\[~(.*?)~\]|\(~(.*?)~\)|\{~(.*?)~\}|<~(.*?)~>|:~(.*?)~:/', $table, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $match) {
                $table = trim(str_replace($match[0], '', $table));
                if (!empty($match[1])) $columns = trim($match[1])??$columns;
                if (!empty($match[2])) $order = trim($match[2]);
                if (!empty($match[3])) $limit = trim($match[3]);
                if (!empty($match[4])) $groupBy = trim($match[4]);
                if (!empty($match[5])) $having = trim($match[5]);
            }
        }

        $sql = "SELECT $columns FROM `$table`";
        $params = [];
        $conditions = [];

        if (!empty($where)) {
            foreach ($where as $key => $value) {
                if ($useLike) {
                    $conditions[] = "$key LIKE :$key";
                    $params[":$key"] = "%$value%";
                } else {
                    $conditions[] = "$key = :$key";
                    $params[":$key"] = $value;
                }
            }
        }

        if (!empty($orWhere)) {
            $orConditions = [];
            foreach ($orWhere as $key => $value) {
                if ($useLike) {
                    $orConditions[] = "$key LIKE :or_$key";
                    $params[":or_$key"] = "%$value%";
                } else {
                    $orConditions[] = "$key = :or_$key";
                    $params[":or_$key"] = $value;
                }
            }
            if (!empty($orConditions))
                $conditions[] = '( ' . implode(' OR ', $orConditions) . ' )';
        }

        if (!empty($conditions))
            $sql .= ' WHERE ' . implode(' AND ', $conditions);

        if (!empty($groupBy))
            $sql .= " GROUP BY $groupBy";

        if (!empty($having))
            $sql .= " HAVING $having";

        if (!empty($order))
            $sql .= " ORDER BY $order";

        if (!empty($limit)) {
            if (preg_match('/^(\d+)\s*,\s*(\d+)$/', $limit, $pages)) {
                $page = (int)$pages[1];
                $perPage = (int)$pages[2];
                $offset = ($page - 1) * $perPage;
                $sql .= " LIMIT $offset, $perPage";
            } else {
                $sql .= " LIMIT $limit";
            }
        }

        $stmt = $this->handler->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    function create(string $table, array $columns, string $engine = 'InnoDB', string $charset = 'utf8mb4'): bool
    {
        $fields = [];
        foreach ($columns as $name => $definition)
            $fields[] = "`$name` $definition";

        $sql = "CREATE TABLE IF NOT EXISTS `$table` (" . implode(',', $fields) . ") ENGINE=$engine DEFAULT CHARSET=$charset;";
        return $this->handler->exec($sql) !== false;
    }

    private function checkFile(string $path):bool {
        return  is_readable($path) ? true : false;
    }

    private function isAllowFile(string $filename, string $formatList):string {
        // Buka fileinfo
        $mime_type = $this->getMimeFile($filename);
        $extension = $this->getExtension($mime_type);
        $allowed = explode('|', $formatList);
        if(in_array($extension,$allowed)){
           return file_get_contents($filename);
        }else{
           return '';
        }
    }  

    // mime parse map
    private static function parseMimeFile($mimeFile)
    {
        $lines = file($mimeFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        $mimeMap = [];
        foreach ($lines as $line) {
            // Abai comment
            if (strpos($line, '#') === 0) continue;
            $parts = preg_split('/\s+/', trim($line));
            if (count($parts) > 1) {
                // Ambil tipe mime
                $mimeType = array_shift($parts); 
                foreach ($parts as $ext) 
                    // Tambahkan ke peta ekstensi
                    $mimeMap[$mimeType][] = $ext;
            }
        }
        return $mimeMap;
    }

    static function getMimeFile($filePath,$isfile=true):string
    {
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $fmime = '';
        if($isfile){
            $fmime = finfo_file($finfo,$filePath);
        }else{
            $fmime = finfo_buffer($finfo,$filePath);
        }
        finfo_close($finfo);
        return $fmime;
    }

    static function getExtension($mimeType):string
    {
        
        $mimeFile = __DIR__.'/mime.types';
        $mimeMap = [];

        if (!file_exists($mimeFile))
            throw new Exception("mime.types file is missing!: {$mimeFile}");

        $mimeMap = self::parseMimeFile($mimeFile);

        if(is_bool($mimeType)){
            if($mimeType)
                // Ambil ekstensi jika true
                return $mimeMap[$mimeType] ?? [];
            else
                // Ambil mimetype dan ekstensi jika false
                return $mimeMap;            
        }else{
            if (isset($mimeMap[$mimeType]))
                // Ambil ekstensi pertama yang ditemukan
                return $mimeMap[$mimeType][0];
        }
        
        return 'unknown';
    }
}