# 🗄️ Database Features

This document describes the structure and usage of the `Database` class, which provides a flexible and secure CRUD (Create `create` table, Read `select`, Update `update`, Delete `delete`) interface using PDO for MySQL databases. It includes advanced SQL parsing with support for complex queries, including pagination, grouping, file management (LONGBLOB) and filtering.

---

## 📌 Class Initialization
Sintax:
```php
$self->dbConnect(string $username,string $password,string $dbname, string $host, string $port, string $type):object
```
On your page handler put code below
```php
public function method($self,$params) {
    //---> here....
    $db = $self->dbConnect('john','123','mydatabase');
}
```
You can add custom `[database]` section in your .ini configuration file, after `[pwa]` section like this:
```ini
[database]
user = root
pass = 123
name = dbident
host = localhost
```
then put in handler like this:
```php
$db = $self->dbConnect();
```
Or you can manage multiple server in you config file like this:
```ini
[database:server1]
user = user1
pass = p@s51
name = dbserver1
host = www.domain1.com
port = 3307

[database:server2]
user = user2
pass = p@s52
name = dbserver2
host = www.domain2.com
port = 3308
```
then put in handler like this:
```php
$db1 = $self->dbConnect('server1');
// to do here....
$db2 = $self->dbConnect('server2');
// to do here....
```
Also you can change `allow_extension` on configuration .ini file as default, exp:
```ini
[global]
allow_extension = pdf|doc|docx.....
```
Alternatively, you can use the specific method `$db->blob(string $extensions, ...)->[insert|update]` for a single execution.

---

## 📋 Method: `select`

### Description:
Flexible `SELECT` query builder with support for:
- Custom column selection
- Ordering
- Pagination
- Grouping
- Having clauses
- WHERE and OR WHERE with parameter binding (secure)

### Syntax:
```php
$db->select(string $table, array $where = [], bool $useLike = false, array $orWhere = []): array
```

### 🧩  Special Syntax in Table `$table` Parameter:
| Symbol | Purpose | Example |
|--------|---------|---------|
| `[~columns~]` | Select specific columns | `users[~id, name~]` |
| `(~order~)` | Order by clause | `users(~id DESC~)` |
| `{~page,perPage~}` | Pagination | `users{~1,10~}` (page 1, 10 records per page) |
| `<~group~>` | Group by clause | `users<~role~>` |
| `:~having~:` | Having clause | `users:~COUNT(id) > 1~:` |

### Parameters:
- `$table` (string) : Table name with optional inline query parameters.
- `$where` (array) : Associative array of WHERE conditions.
- `$useLike` (bool) : Use `LIKE` instead of `=` in WHERE clauses.
- `$orWhere` (array) : Associative array of OR WHERE conditions.

### Example:
```php
$result = $db->select('users[~name, COUNT(id) as total~](~total DESC~){~1,10~}<~role~>:~total > 1:');
```
This translates to:
```sql
SELECT name, COUNT(id) as total FROM users ORDER BY total DESC LIMIT 0,10 GROUP BY role HAVING total > 1
```

### Notes:
- WHERE and OR WHERE values are safely bound to prevent SQL injection.
- Pagination is automatically calculated from `{~page,perPage~}` format.

---

## 📋 Method: `insert`

### Description:
Insert data into a table with optional BLOB file support.

### Syntax: Output is last insert id (primary key)
```php
$db->insert(string $table, array $data): int
```
### Parameters:
- `$table` (string) : Table name.
- `$data` (array) : Associative array of columns and their values.
### Contain File Syntax (default filter extensions in configurtion):
```php
$db->blob()->insert(string $table, array $data): int
```
### Contain File Syntax (for one-time execution):
```php
$db->blob('pdf|doc')->insert(string $table, array $data): int
```

### Example:
```php
$id = $db->blob()->insert('users', ['name' => 'John', 'avatar' => 'path/to/file.jpg']);
```

---

## 📋 Method: `update`

### Description:
Update data in a table with optional BLOB file support and flexible WHERE conditions.

### Syntax:
```php
$db->update(string $table, array $data, array $where, bool $useLike = false, array $orWhere = []): bool
```
### Parameters:
- `$data` : Data to update.
- `$where` : Conditions in AND format.
- `$useLike` : Enables LIKE comparison.
- `$orWhere` : Additional OR conditions.

### Contain File Syntax (default filter extensions in configurtion):
```php
$db->blob()->update(string $table, array $data, array $where, bool $useLike = false, array $orWhere = []): bool
```
### Contain File Syntax (for one-time execution):
```php
$db->blob('pdf|doc')->update(string $table, array $data, array $where, bool $useLike = false, array $orWhere = []): bool
```

### Example:
```php
$check = $db->blob()->update('users', ['name' => 'John', 'avatar' => 'path/to/file.jpg'], ['id' => 1]);
```

---

## 📋 Method: `delete`

### Description:
Delete data from a table securely.

### Syntax:
```php
$db->delete(string $table, array $where): bool
```

### Example:
```php
$db->delete('users', ['id' => 1]);
```

---

## 📋 Method: `create` Table

### Description:
Create a new table if it does not already exist.

### Syntax:
```php
$db->create(string $table, array $columns, string $engine = 'InnoDB', string $charset = 'utf8mb4'): bool
```

### Example:
```php
$db->create('users', [
    'id' => 'INT AUTO_INCREMENT PRIMARY KEY',
    'name' => 'VARCHAR(100) NOT NULL',
    'email' => 'VARCHAR(100) NOT NULL'
]);
```
---

## 🛡️ Security Notes
- All queries use prepared statements with parameter binding to prevent SQL injection.
- Filter BLOB for `insert` and `update`, if the file path is invalid, the field will be left empty.
- Make sure you do not exceed MySQL's max_allowed_packet and PHP's memory_limit.