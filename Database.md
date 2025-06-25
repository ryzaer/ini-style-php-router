# This is Databases Documentation
`select` function format `obj->select(string table,array where,bool where like, array where or)` on first variable is string pattern `table(tablename,...)[order]{limit}`
```
$rsl = $pdo->select('tbblob(id,name,filedata as file)[id DESC]{5}',['name'=>'dora'],true);

output:
SELECT id,name,filedata as file FROM `tbblob` WHERE name LIKE :name ORDER BY id DESC LIMIT 5
```