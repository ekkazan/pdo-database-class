# PHP PDO Database Class
A lightweight, basic PDO database class written in PHP.

## Usage
```php
$db = new Database('localhost', 'database', 'user', 'pass');

// Create
$new = $db->database
    ->insert('table', $data)
    ->execute();

// Read
$read = $db->database
    ->select('table')
    ->where(['id' => $id])
    ->fetch(TRUE); // \PDO::FETCH_ASSOC
    
// Update
$update = $db->database
    ->update('table', $data)
    ->where(['id' => $id])
    ->execute();
    
// Delete
$delete = $db->database
    ->delete('table')
    ->where(['id' => $id])
    ->execute();  
    
// More Advanced
$delete = $db->database
    ->select(['groups', 'g'], $columns) // use table alias
    ->leftJoin(['groups', 'p'], 'parent', 'id') // use table alias
    ->orderBy('g.name', 'ASC')
    ->all(TRUE);
```
