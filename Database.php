<?php
class Database {
  private \PDO $database;

  private string $table;
  private array $config = [];
  private string $statement = '';
  private array $params = [];

  private bool $isWhereSet = FALSE;
  private bool $isOrderSet = FALSE;

  /**
   * Initialize Database class and run Database::connect
   * to initialize database connection.
   * 
   * @param mixed $DBHost
   * @param mixed $DBName
   * @param mixed $DBUser
   * @param mixed $DBPass
   * 
   * @return void
   */
  public function __construct($DBHost, $DBName, $DBUser, $DBPass) {
    $this->config['host'] = $DBHost;
    $this->config['name'] = $DBName;
    $this->config['user'] = $DBUser;
    $this->config['pass'] = $DBPass;

    $this->connect();
  }

  /**
   * Initialize database connection. Do nothing if config
   * are not set.
   * 
   * @return void
   */
  private function connect() {
    $DBHost = 'mysql:host='.$this->config['host'].';dbname='.$this->config['name'].';charset=utf8';
    $DBUsername = $this->config['user'];
    $DBPassword = $this->config['pass'];  

    try {
      $this->database = new \PDO($DBHost, $DBUsername, $DBPassword);
      $this->database->exec('SET NAMES `utf8`; SET CHARACTER SET `utf8`');
    } catch (\PDOException $error) {
      exit('DB connection could not established. Error: '.$error->getMessage());
    }
  }

  /**
   * Insert method for all of the database insert actions.
   * It sets the SQL statement as INSERT INTO and implodes data
   * and columns after that it sets parameters.
   * 
   * @param string $table
   * @param array $data
   * 
   * @return this
   */
  public function insert(string $table, array $data) {
    $this->table = $table;

    $this->statement = 'INSERT INTO '.$table;
    $this->statement .= ' ('.implode(',', array_keys($data)).') VALUES';
    $this->statement .= ' ('.implode(',', array_fill(0, count($data), '?')).')';

    foreach($data as $value) {
      $this->params[] = $value;
    }
    
    return $this;
  }

  /**
   * Select method for all of the database select actions.
   * It has a basic syntax, it just needs desired table name.
   * It sets the SQL statement as SELECT.
   * 
   * @param array|string $table
   * @param bool count
   * 
   * @return this
   */
  public function select($table, array $columns = []) {
    if(is_array($table)) {
      $this->table = $table[1];
      $table = implode(' ', $table);
    } else {
      $this->table = $table;
    }
    
    if($columns) {
      $this->statement = 'SELECT '.implode(', ', $columns).' FROM '.$table;
    } else {
      $this->statement = 'SELECT * FROM '.$table;
    }

    return $this;
  }

   /**
   * Select method for all of the database select actions.
   * It has a basic syntax, it just needs desired table name.
   * It sets the SQL statement as SELECT.
   * 
   * @param string $table
   * @param bool count
   * 
   * @return this
   */
  public function count(string $table, string $column = '') {
    $this->table = $table;
    
    if($column) {
      $this->statement = 'SELECT COUNT('.$column.') FROM '.$table;
    } else {
      $this->statement = 'SELECT COUNT(*) FROM '.$table;
    }

    return $this;
  }

  /**
   * Update method for all of the database select actions.
   * It sets the SQL statement as UPDATE and implodes data
   * and columns after that it sets parameters.
   * 
   * @param string $table
   * @param array $data
   * 
   * @return this
   */
  public function update(string $table, array $data) {
    $this->table = $table;
    
    $this->statement = 'UPDATE  '.$table.' SET ';

    $clauses = [];
    $i = 0;

    foreach($data as $column => $value) {
      $clauses[$i] = $column.' = ?';
      $this->params[$i++] = $value;
    }

    $this->statement .= implode(',', $clauses);

    return $this;
  }

  /**
   * Delete method for all of the database select actions.
   * It has a basic syntax, it just needs desired table name.
   * It sets the SQL statement as DELETE.
   * 
   * @param string $table
   * 
   * @return this
   */
  public function delete(string $table) {
    $this->table = $table;
    
    $this->statement = 'DELETE FROM '.$table;

    return $this;
  }

  /**
   * Inner join statement to query multiple tables that are connected with
   * each other via foreign keys.
   * 
   * @param array|string $targetTable
   * @param string $sourceColumn
   * @param string $targetColumn
   * @param array $where
   * @param string $wtype
   * 
   * @return this
   */
  public function join($targetTable, string $sourceColumn, string $targetColumn, array $where = [], string $wtype = 'AND') {
    $source = $this->table.'.'.$sourceColumn;

    if(is_array($targetTable)) {
      $target = $targetTable[1].'.'.$targetColumn;
      $targetTable = implode(' ', $targetTable);
    } else {
      $target = $targetTable.'.'.$targetColumn;
    }

    $this->statement .= ' INNER JOIN '.$targetTable.' ON '.$source.' = '.$target;

    if(count($where)) {
      foreach($where as $type => $value) {
        switch($type) {
          case '=':
            $whereClauses[] = $target.' = "'.$value.'"';
            break;
          case '>':
            $whereClauses[] = $target.' > "'.$value.'"';
            break;
          case '<':
            $whereClauses[] = $target.' < "'.$value.'"';
            break;
          case 'IN':
            $in = '"'.implode('","', $value).'"';
            $whereClauses[] = $target.' IN ('.$in.')';
            break;
          case 'LIKE':
            $whereClauses[] = $target.' LIKE "'.$value.'"';
            break;
        }
      }
  
      $this->statement .= ' AND '.implode(' '.$wtype.' ', $whereClauses);
    }

    return $this;
  }

  /**
   * Left join statement to query multiple tables that are connected with
   * each other via foreign keys.
   * 
   * @param array|string $targetTable
   * @param array $sourceColumn
   * @param string $targetColumn
   * @param array $where
   * @param string $wtype
   * 
   * @return this
   */
  public function leftJoin($targetTable, string $sourceColumn, string $targetColumn, array $where = [], string $wtype = 'AND') {
    $source = $this->table.'.'.$sourceColumn;

    if(is_array($targetTable)) {
      $target = $targetTable[1].'.'.$targetColumn;
      $targetTable = implode(' ', $targetTable);
    } else {
      $target = $targetTable.'.'.$targetColumn;
    }

    $this->statement .= ' LEFT JOIN '.$targetTable.' ON '.$source.' = '.$target;

    if(count($where)) {
      foreach($where as $type => $value) {
        switch($type) {
          case '=':
            $whereClauses[] = $target.' = "'.$value.'"';
            break;
          case '>':
            $whereClauses[] = $target.' > "'.$value.'"';
            break;
          case '<':
            $whereClauses[] = $target.' < "'.$value.'"';
            break;
          case 'IN':
            $in = '"'.implode('","', $value).'"';
            $whereClauses[] = $target.' IN ('.$in.')';
            break;
          case 'LIKE':
            $whereClauses[] = $target.' LIKE "'.$value.'"';
            break;
        }
      }
  
      $this->statement .= ' AND '.implode(' '.$wtype.' ', $whereClauses);
    }

    return $this;
  }

  /**
   * Right join statement to query multiple tables that are connected with
   * each other via foreign keys.
   * 
   * @param array|string $targetTable
   * @param array $sourceColumn
   * @param string $targetColumn
   * @param array $where
   * @param string $wtype
   * 
   * @return this
   */
  public function rightJoin($targetTable, string $sourceColumn, string $targetColumn, array $where = [], string $wtype = 'AND') {
    $source = $this->table.'.'.$sourceColumn;

    if(is_array($targetTable)) {
      $target = $targetTable[1].'.'.$targetColumn;
      $targetTable = implode(' ', $targetTable);
    } else {
      $target = $targetTable.'.'.$targetColumn;
    }

    $this->statement .= ' RIGHT JOIN '.$targetTable.' ON '.$source.' = '.$target;

    if(count($where)) {
      foreach($where as $type => $value) {
        switch($type) {
          case '=':
            $whereClauses[] = $target.' = "'.$value.'"';
            break;
          case '>':
            $whereClauses[] = $target.' > "'.$value.'"';
            break;
          case '<':
            $whereClauses[] = $target.' < "'.$value.'"';
            break;
          case 'IN':
            $in = '"'.implode('","', $value).'"';
            $whereClauses[] = $target.' IN ('.$in.')';
            break;
          case 'LIKE':
            $whereClauses[] = $target.' LIKE "'.$value.'"';
            break;
        }
      }
  
      $this->statement .= ' AND '.implode(' '.$wtype.' ', $whereClauses);
    }

    return $this;
  }

  /**
   * Cross join statement to query multiple tables.
   * 
   * @param array $table
   * 
   * @return this
   */
  public function crossJoin(string $table) {
    $this->statement .= ' CROSS JOIN '.$table;

    return $this;
  }

  /**
   * An important method of Database class. Sets the WHERE clause
   * for select and delete methods. You can call it after
   * every other method but, you need to use it just with select and
   * delete methods. It supports '=', '>', '<' 'IN' and 'LIKE' operators.
   * 
   * Proper parameters usage
   *    ['column_name' => [
   *        '=|>|<|IN|LIKE', 
   *        'VALUE'
   *      ]
   *    ]
   * or only second parameter which is value. It will assign '='
   * automatically.
   * 
   * Proper parameters usage: VALUE must be array to use IN operator
   * 
   * Example IN usage
   *   ['id' => [
   *      'IN',
   *      [0,1,2]
   *    ]
   *  ]
   * 
   * @param array $where
   * @param string $type
   * 
   * @return this
   */
  public function where(array $where, string $type = 'AND') {
    $i = count($this->params);

    foreach($where as $column => $inner) {
      if(is_array($inner)) {
        $type = $inner[0];
        $value = $inner[1];
      } else {
        $type = '=';
        $value = $inner;
      }

      switch($type) {
        case '=':
          $clause = $column.' = ?';
          $this->params[$i++] = $value;
          break;
        case '>':
          $clause = $column.' > ?';
          $this->params[$i++] = $value;
          break;
        case '<':
          $clause = $column.' < ?';
          $this->params[$i++] = $value;
          break;
        case 'IN':
          $qmarks = implode(',', array_fill(0, count($value), '?'));
          $clause = $column.' IN ('.$qmarks.')';

          foreach($value as $value) {
            $this->params[$i++] = $value;
          }
          break;
        case 'LIKE':
          $clause = $column.' LIKE ?';
          $this->params[$i++] = $value;
          break;
      }
    }

    if(!$this->isWhereSet) {
      $this->isWhereSet = TRUE;
      $this->statement .= ' WHERE ';
    } else {
      $this->statement .= ' '.$type.' ';
    }

    $this->statement .= $clause;

    return $this;
  }

  /**
   * Shorthand for where statements with OR.
   * 
   * @param array $where
   * 
   * @return this
   */
  public function orWhere(array $where) {
    $i = count($this->params);

    foreach($where as $column => $inner) {
      if(is_array($inner)) {
        $type = $inner[0];
        $value = $inner[1];
      } else {
        $type = '=';
        $value = $inner;
      }

      switch($type) {
        case '=':
          $clause = $column.' = ?';
          $this->params[$i++] = $value;
          break;
        case '>':
          $clause = $column.' > ?';
          $this->params[$i++] = $value;
          break;
        case '<':
          $clause = $column.' < ?';
          $this->params[$i++] = $value;
          break;
        case 'IN':
          $qmarks = implode(',', array_fill(0, count($value), '?'));
          $clause = $column.' IN ('.$qmarks.')';

          foreach($value as $value) {
            $this->params[$i++] = $value;
          }
          break;
        case 'LIKE':
          $clause = $column.' LIKE ?';
          $this->params[$i++] = $value;
          break;
      }
    }

    if(!$this->isWhereSet) {
      $this->isWhereSet = TRUE;
      $this->statement .= ' WHERE ';
    } else {
      $this->statement .= ' OR ';
    }

    $this->statement .= $clause;

    return $this;
  }

  /**
   * Where with columns.
   * 
   * @param string $column
   * @param string $type
   * @param mixed $value
   * 
   * @return this
   */
  public function whereColumn(string $first, string $second, string $type = '=') {
    switch($type) {
      case '=':
        $clause = $first.' = '.$second;
        break;
      case '>':
        $clause = $first.' > '.$second;
        break;
      case '<':
        $clause = $first.' < '.$second;
        break;
    }

    if(!$this->isWhereSet) {
      $this->isWhereSet = TRUE;
      $this->statement .= ' WHERE ';
    } else {
      $this->statement .= ' '.$type.' ';
    }

    $this->statement .= $clause;

    return $this;
  }

  /**
   * If you want to group your result, use this method just after select method
   * (if you use where method too, after where method). It takes 1 parameter,
   * the column that is going to be grouped.
   * 
   * @param string $column
   * 
   * @return this
   */
  public function groupBy(string $column) {
    $this->statement .= ' GROUP BY '.$column;
    
    return $this;
  }

  /**
   * Sets the HAVING clause.
   * 
   * @param string $column
   * @param string $type
   * @param mixed $value
   * 
   * @return this
   */
  public function having(string $column, $value, string $type = '=') {
    $i = count($this->params);
    
    switch($type) {
      case '=':
        $clause = $column.' = ?';
        $this->params[$i++] = $value;

        break;
      case '>':
        $clause = $column.' > ?';
        $this->params[$i++] = $value;
        break;
      case '<':
        $clause = $column.' < ?';
        $this->params[$i++] = $value;
        break;
      case 'IN':
        $qmarks = implode(',', array_fill(0, count($value), '?'));
        $clause = $column.' IN ('.$qmarks.')';

        foreach($value as $value) {
          $this->params[$i++] = $value;
        }
        break;
      case 'LIKE':
        $clause = $column.' LIKE ?';
        $this->params[$i++] = $value;
        break;
    }

    $this->statement .= ' HAVING ';

    $this->statement .= $clause;

    return $this;
  }

  /**
   * If you want to order your result after SELECT, use this method just after
   * select method (if you use where method too, after where method). It takes
   * 2 parameters, the column that is going to be sorted and sorting type.
   * 
   * @param string $column
   * @param string $type
   * 
   * @return this
   */
  public function orderBy(string $column, string $type = 'DESC') {
    if(!$this->isOrderSet) {
      $this->statement .= ' ORDER BY '.$column.' '.$type;
      $this->isOrderSet = TRUE;
    } else {
      $this->statement .= ', '.$column.' '.$type;
    }
   
    
    return $this;
  }
  
  /**
   * If you want to limit your result after SELECT, use this method just after
   * select method (if you have used where method too, after where method). 
   * It takes 2 parameters, the first row and number of wanted results.
   * 
   * @param int $start
   * @param int $number
   * 
   * @return this
   */
  public function limit(int $start, int $limit) {
    $this->statement .= ' LIMIT '.$start.', '.$limit;
    
    return $this;
  }

  /**
   * Count total rows that is affected by your query.
   * 
   * @return int
   */
  public function rowCount() {
    $query = $this->database->prepare($this->statement);
    $query->execute($this->params);

    $count = $query->rowCount();

    $this->reset();

    return $count;
  }

  /**
   * Basic execution of your SQL query. You should use it with update and delete
   * methods.
   * 
   * @return bool
   */
  public function execute() {
    $query = $this->database->prepare($this->statement);
    $query = $query->execute($this->params);

    $this->reset();

    return $query;
  }

  /**
   * Fetch single row if you want to get a single user, article, post etc.
   * It would be more useful with unique SELECT actions like user_id.
   * 
   * @param bool $assoc
   * 
   * @return mixed
   */
  public function fetch(bool $assoc = FALSE) {
    $query = $this->database->prepare($this->statement);
    $query->execute($this->params);

    $query = ($assoc) ? $query->fetch(\PDO::FETCH_ASSOC) : $query->fetch();
    
    $this->reset();

    return $query;
  }

  /**
   * Fetch a single column. Useful for statements with COUNT on
   * SELECT.
   * 
   * @param int $column
   * 
   * @return mixed
   */
  public function fetchColumn(int $column = 0) {
    $query = $this->database->prepare($this->statement);
    $query->execute($this->params);

    $query = $query->fetchColumn($column);
    
    $this->reset();

    return $query;
  }

  /**
   * Fetch all of the rows affected if used limited by orderBy and limit methods
   * on your SELECT actions. If you want to get result as an array use $assoc parameter
   * and set it TRUE.
   * 
   * @param bool $assoc
   * 
   * @return mixed
   */
  public function all(bool $assoc = FALSE) {
    $query = $this->database->prepare($this->statement);
    $query->execute($this->params);

    $query = ($assoc) ? $query->fetchAll(\PDO::FETCH_ASSOC) : $query;

    $this->reset();

    return $query;
  }

  /**
   * Return the last inserted row to the database.
   * 
   * @return int
   */
  public function lastInsert() {
    return $this->database->lastInsertId();
  }
  
  /**
   * If capabilities of Database class is not suits your requirements, you can always
   * write and run your own SQL queries with this method. 
   * 
   * @param string $sql
   * @param array $parameters
   * 
   * @return mixed
   */
  public function sql(string $sql, array $params = []) {
    $query = $this->database->prepare($sql);
    $query->execute($params);

    return $query;
  }

  /**
   * Get the built statement.
   * 
   * @return string
   */
  public function getQuery() {
    return $this->statement;
  }

  /**
   * Resets the initial properties of Database class to use it again safely.
   * 
   * @return void
   */
  private function reset() {
    $this->statement = '';
    $this->params = [];
    $this->isWhereSet = FALSE;
    $this->isOrderSet = FALSE;
  }
}