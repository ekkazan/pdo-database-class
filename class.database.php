<?php
class Database {
  private $database;
  private $statement;
  private $params = [];
  private $params_key = 0;

  public $table;

  /**
   * Initialize Database class and set the PDO database
   * class to run SQL queries properly.
   * 
   * @param class
   */
  public function __construct($database) {
    $this->database = $database;
  }

  /**
   * Insert method for all of the database insert actions.
   * It sets the SQL statement as INSERT INTO and implodes 
   * data and columns after that it sets parameters.
   * 
   * @param string $table
   * @param array $data
   * 
   * @return $this
   */
  public function insert(string $table, array $data) {
    $this->statement = 'INSERT INTO '.$table;
    $this->statement .= ' ('.implode(',', array_keys($data)).') VALUES ';
    $this->statement .= '('.implode(',', array_fill(0, count($data), '?')).')';

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
   * @param string $table
   * 
   * @return $this
   */
  public function select(string $table) {
    $this->statement = 'SELECT * FROM '.$table;

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
   * @return $this
   */
  public function update(string $table, array $data) {
    $this->statement = 'UPDATE  '.$table.' SET ';

    $set_clause = [];
    $i = 0;

    foreach($data as $column => $value) {
      $set_clause[$i] = $column.' = ?';
      $this->params[$i++] = $value;
    }

    $this->params_key = $i;

    $this->statement .= implode(',', $set_clause);

    return $this;
  }

  /**
   * Delete method for all of the database select actions.
   * It has a basic syntax, it just needs desired table name.
   * It sets the SQL statement as DELETE.
   * 
   * @param string $table
   * 
   * @return $this
   */
  public function delete(string $table) {
    $this->statement = 'DELETE FROM '.$table;

    return $this;
  }

  /**
   * An important method of Database class. Sets the WHERE clause
   * for select and delete methods. You can call it after
   * every other method but, you need to use it just with select and
   * delete methods. It supports '=', '>', '<', 'IN' and 'LIKE' operators.
   * 
   * Proper parameters usage: ['column_name' => ['type' => 'EQUAL'|'IN'|'LIKE', 'value' => 'VALUE']]
   * Proper parameters usage: VALUE must be array to use IN operator
   * 
   * Example IN usage: $where = ['id' => ['type' => 'IN', 'value' => [0,1,2]]]
   * 
   * @param array $where
   * @param array $type
   * 
   * @return $this
   */
  public function where(array $where, string $type = 'AND') {
    $this->statement .= ' WHERE ';

    $where_clause = [];
    $i = $this->params_key;

    foreach($where as $column => $inner) {
      switch($inner['type']) {
        case 'EQUAL':
          $where_clause[$i] = $column.' = ?';
          $this->params[$i++] = $inner['value'];

          break;
        case 'BIGGER':
          $where_clause[$i] = $column.' > ?';
          $this->params[$i++] = $inner['value'];
  
          break;
        case 'LOWER':
          $where_clause[$i] = $column.' < ?';
          $this->params[$i++] = $inner['value'];
    
          break;
        case 'IN':
          $qmarks = implode(',', array_fill(0, count($inner['value']), '?'));
          $where_clause[$i] = $column.' IN ('.$qmarks.')';

          foreach($inner['value'] as $value) {
            $this->params[$i++] = $value;
          }

          break;
        case 'LIKE':
          $where_clause[$i] = $column.' LIKE ?';
          $this->params[$i++] = '%'.$inner['value'].'%';
          
          break;
      }
    }

    $this->statement .= implode(' '.$type.' ', $where_clause);

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
   * @return $this
   */
  public function order_by(string $column, string $type) {
    $this->statement .= ' ORDER BY '.$column.' '.$type;
    
    return $this;
  }
  
  /**
   * If you want to limit your result after SELECT, use this method just after
   * select method (if you use where method too, after where method). It takes
   * 2 parameters, the first row and number of wanted results.
   * 
   * @param int $start
   * @param int $number
   * 
   * @return $this
   */
  public function limit(int $start, int $limit) {
    $this->statement .= ' LIMIT '.$start.', '.$limit;
    
    return $this;
  }

  /**
   * Count total rows that is affected by your query.
   * 
   * @return int $rowCount
   */
  public function row_count() {
    $query = $this->database->prepare($this->statement);
    $query->execute($this->params);

    $rowCount = $query->rowCount();

    $this->reset();

    return $rowCount;
  }

  /**
   * Basic execution of your SQL query. You should use it with update and delete
   * methods.
   * 
   * @return bool $query
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
   * @return mixed $query
   */
  public function fetch(bool $assoc = FALSE) {
    $query = $this->database->prepare($this->statement);
    $query->execute($this->params);

    $query = ($assoc) ? $query->fetch(\PDO::FETCH_ASSOC) : $query->fetch();
    
    $this->reset();

    return $query;
  }

  /**
   * Fetch all of the rows affected if used limited by orderBy and limit methods
   * on your SELECT actions. If you want to get result as and array use $assoc parameter
   * and set it TRUE.
   * 
   * @param bool $assoc
   * 
   * @return mixed $query
   */
  public function fetch_all(bool $assoc = FALSE) {
    $query = $this->database->prepare($this->statement);
    $query->execute($this->params);

    $query = ($assoc) ? $query->fetchAll(\PDO::FETCH_ASSOC) : $query;

    $this->reset();

    return $query;
  }

  /**
   * Return the last row inserted to the database.
   * 
   * @return int
   */
  public function last_insert() {
    return $this->database->lastInsertId();
  }
  
  /**
   * If capabilities of Database class is not suits your requirements, you can always
   * write and run your own SQL queries with this method. 
   * 
   * @param string $sql
   * @param array $parameters
   * 
   * @return mixed $query
   */
  public function run_sql(string $sql, array $params = []) {
    $query = $this->database->prepare($sql);
    $query->execute($params);

    return $query;
  }

  /**
   * Resets the initial properties of Database class to use it again safely.
   */
  private function reset() {
    $this->statement = NULL;
    $this->params = [];
    $this->params_key = 0;
    $this->table = NULL;
  }
}
