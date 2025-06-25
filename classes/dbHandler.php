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
            $isBlob = $this->allowBlob && $this->isAllowFile($value, $this->format);
            $val = $isBlob ? $this->readFile($value) : $value;
            $type = $isBlob ? PDO::PARAM_LOB : ( is_numeric($val) ? PDO::PARAM_INT : PDO::PARAM_STR );
            $stmt->bindValue(":$key", $val, $type);
        }
        $this->format = $this->extension;
        $this->allowBlob = false;
        $stmt->execute();
        return (int) $this->handler->lastInsertId();
    } 

    public function update(string $table, array $data, array $where): bool {
        $setParts = [];
        foreach ($data as $key => $_) {
            $setParts[] = "$key = :$key";
        }
        $whereParts = [];
        foreach ($where as $key => $_) {
            $whereParts[] = "$key = :w_$key";
        }

        $sql = "UPDATE `$table` SET " . implode(',', $setParts) . " WHERE " . implode(' AND ', $whereParts);
        $stmt = $this->handler->prepare($sql);

        foreach ($data as $key => $value) {
            $isBlob = $this->allowBlob && $this->isAllowFile($value, $this->format);
            $val = $isBlob ? $this->readFile($value) : $value;
            $type = $isBlob ? PDO::PARAM_LOB : (is_numeric($val) ? PDO::PARAM_INT : PDO::PARAM_STR);
            $stmt->bindValue(":$key", $val, $type);
        }

        foreach ($where as $key => $value) {
            $param = is_numeric($value) ? PDO::PARAM_INT : PDO::PARAM_STR;
            $stmt->bindValue(":w_$key", $value, $param);
        }

        $this->format = $this->extension;
        $this->allowBlob = false;

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

    function select(string $table,array $where = [],bool $useLike = false,array $orWhere = []): array {

        $columns = '*';
        preg_match('/\((.*?)\)/', $table, $matches);
        if($matches){
            $table = trim(str_replace($matches[0],'',$table));
            $rows = trim($matches[1]);
            $columns = $rows ? $rows : $columns ;
        }
        $order = null;
        preg_match('/\[(.*?)\]/', $table, $matches);
        if($matches){
            $table = trim(str_replace($matches[0],'',$table));
            $order = trim($matches[1]);
        }
        $limit = null;
        preg_match('/\{(.*?)\}/', $table, $matches);
        if($matches){
            $table = trim(str_replace($matches[0],'',$table));
            $limit = trim($matches[1]);
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
            if (!empty($orConditions)) {
                $conditions[] = '( ' . implode(' OR ', $orConditions) . ' )';
            }
        }

        if (!empty($conditions)) 
            $sql .= ' WHERE ' . implode(' AND ', $conditions);
        
        if($order)
            $sql .= " ORDER BY $order";
        
        if($limit)
            $sql .=" LIMIT $limit" ;
        
        $stmt = $this->handler->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    function create(string $table, array $columns, string $engine = 'InnoDB', string $charset = 'utf8mb4'): bool
    {
        $fields = [];
        foreach ($columns as $name => $definition) {
            $fields[] = "`$name` $definition";
        }

        $sql = "CREATE TABLE IF NOT EXISTS `$table` (" . implode(',', $fields) . ") ENGINE=$engine DEFAULT CHARSET=$charset;";

        return $this->handler->exec($sql) !== false;
    }

    private function readFile(string $path): ?string {
        return  is_readable($path) ? file_get_contents($path) : '';
    }

    private function isAllowFile(string $filename, string $formatList): bool {
        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        $allowed = explode('|', $formatList);
        return in_array($ext, $allowed);
    }  
}