# PHP PDO Database Class
A lightweight, basic PDO database class written in PHP.

## Usage
```php
$db = new Database('localhost', 'database', 'user', 'pass');

// Create
$new = $this->database
    ->insert('table', $data)
    ->execute();

// Read
$read = $this->database
    ->select('table')
    ->where(['id' => $id])
    ->fetch(TRUE); // \PDO::FETCH_ASSOC
    
// Update
$update = $this->database
    ->update('table', $data)
    ->where(['id' => $id])
    ->execute();
    
// Delete
$delete = $this->database
    ->delete('table')
    ->where(['id' => $id])
    ->execute();  
    
// More Advanced
$delete = $this->database
    ->select(['groups', 'g'], $columns) // use table alias
    ->leftJoin(['groups', 'p'], 'parent', 'id') // use table alias
    ->orderBy('g.name', 'ASC')
    ->all(TRUE);
```
