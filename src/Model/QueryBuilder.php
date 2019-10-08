<?php
declare(strict_types = 1);
/**
 * OriginPHP Framework
 * Copyright 2018 - 2019 Jamiel Sharief.
 *
 * Licensed under The MIT License
 * The above copyright notice and this permission notice shall be included in all copies or substantial
 * portions of the Software.
 *
 * @copyright   Copyright (c) Jamiel Sharief
 * @link        https://www.originphp.com
 * @license     https://opensource.org/licenses/mit-license.php MIT License
 */

namespace Origin\Model;

use Origin\Model\Exception\QueryBuilderException;

/*
## SQL Builder
SqlBuilder restored to just escaping table aliases and column aliases. This was changed
to work with Postgre and using reservered words such as a table called users then trying to select it
with an alias of User, which means it need to be quoted "User"."field" however in MySQL there is no need for that since it recognises aliases eg. User.count

This builds SQL statements easily in multiple ways.

## Select

$Builder = new QueryBuilder('users','User');

$Builder->select(['id','user_name','country'])
        ->where(['group_id'=>1024,'active'=>true])
        ->group(['role_id'])
        ->limit(100)
        ->order(['country','user_name asc'])

        OR using an array
          $params = array(
            'fields' => ['id','user_name','country'],
            'conditions' => ['group_id'=>1024,'active'=>true],
            'group' => ['role_id'],
            'limit' => 10,
            'order' => ['country','user_name asc'],
            );

$Builder->selectStatement($params);

To get the sql output
$sql = $Builder->write();

SELECT User.id, User.user_name, User.country FROM `users` AS User WHERE User.group_id = :u0 AND User.active = :u1 GROUP BY User.role_id ORDER BY User.country,user_name asc LIMIT 100

$Builder->writeFormatted();
SELECT
  User.id, User.user_name, User.country
FROM
  `users` AS User
WHERE
  User.group_id = :u0 AND User.active = :u1
GROUP BY
  User.role_id
ORDER BY
  User.country,user_name asc
LIMIT 100

## INSERT

$Builder = new SQLBuilder('users','User');
$data = array(
  'name' => 'tony',
  'email' => 'tony@example.com'
  );

$Builder->insert($data);

$sql = $Builder->write();

will produce :

INSERT INTO users ( name, email ) VALUES ( :u0, :u1 )

$values = $Builder->values();

Array
(
    [:u0] => tony
    [:u1] => tony@example.com
)


## Update

$Builder = new SQLBuilder('users','User');
$data = array(
  'name' => 'tony',
  'email' => 'tony@example.com'
  );

  $Builder->update($data)
    ->where(['id'=>1234]);

$sql = $Builder->write();

UPDATE users SET name = :u0, email = :u1 WHERE User.id = :u2

$values = $Builder->values();

Array
(
    [:u0] => tony
    [:u1] => tony@example.com
    [:u2] => 1234
)

# Delete
  $Builder = new SQLBuilder('users','User');

$conditions = ['id'=>1234];

  $Builder->delete($conditions)
    ->order(['created_date'=>'ASC'])
    ->limit(5);

$sql = $Builder->write();

DELETE FROM users WHERE User.id = :u0 ORDER BY User.created_date ASC LIMIT 5

$values = $Builder->values();
Array
(
    [:u0] => 1234
)
*/

/**
 * Builder assumes if field is not referencing an alias, then it
 * belongs to current table/alias.
 */
class QueryBuilder
{
    /**
     * Holds the query
     *
     * @var array
     */
    protected $query = [];
    /**
     * Holds the values
     *
     * @var array
     */
    protected $values = [];

    /**
     * The table name
     *
     * @var string
     */
    protected $table = null;
    /**
     * The alias e.g. user, User.
     *
     * @var string
     */
    protected $alias = null;

    /**
     * Holds the placeholder for this query
     *
     * @var string
     */
    protected $placeholder = null;

    /**
     * Placeholder counter
     *
     * @var integer
     */
    protected $i = 0;

    /**
     * All table and column aliases are escaped using the tilde sign (`) by default. This
     * is for MySQL or PostgreSQL using "
     *
     * @var string
     */
    protected $escape = '`';

    /**
     * List of operators
     *
     * @var array
     */
    protected $operators = [
        '=', '!=', '>', '<', '>=', '<=', 'BETWEEN',
        'NOT BETWEEN', 'LIKE', 'NOT LIKE', 'IN', 'NOT IN',
    ];

    /**
     * We dont want to add table alias to sql alias fields
     *
     * @var array
     */
    protected $specialFields = [];

    public function __construct($table = null, $alias = null, array $options = [])
    {
        $options += ['escape' => null];

        if (! empty($table)) {
            $this->from($table, $alias);
        }
        if ($options['escape']) {
            $this->escape = $options['escape'];
        }
    }

    /**
     * Basically a reset, used between queries
     *
     * @return void
     */
    protected function clear()
    {
        $this->query = [];
        $this->i = 0;
        $this->values = [];
        $this->specialFields = [];
    }

    /**
     * Returns the values used by the last query
     *
     * @return array
     */
    public function getValues() : array
    {
        return $this->values;
    }

    /**
     * Creates the table reference
     *
     * @param string  $table
     * @param string $alias
     * @return string
     */
    protected function tableReference(string $table = null, string $alias = null) : string
    {
        if ($table == null) {
            $table = $this->table;
            $alias = $this->alias;
        }
        $tableReference = "{$this->escape}{$table}{$this->escape}";
        if ($alias != $table) {
            $tableReference .= " AS {$this->escape}{$alias}{$this->escape}";
        }

        return $tableReference;
    }

    /**
     * The select part of the query
     *
     * $builder->select(['id', 'name', 'email']);
     *
     * @param array $fields
     * @param array $conditions
     * @return \Origin\Model\QueryBuilder
     */
    public function select(array $fields = [], array $conditions = []) : QueryBuilder
    {//SELECT users.* FROM `users`
        $this->query = [
            'type' => 'SELECT',
            'table' => $this->table,
            'fields' => $fields,
            'conditions' => $conditions,
            'joins' => null,
            'group' => null,
            'having' => null,
            'order' => null,
            'limit' => null,
        ];
        $this->i = 0;
        $this->values = [];

        return $this;
    }

    /**
     * The insert part of the statement
     *
     * @param array $data
    * @return \Origin\Model\QueryBuilder
     */
    public function insert(array $data) : QueryBuilder
    {
        $this->clear();

        $this->query = [
            'type' => 'INSERT',
            'data' => $data,
        ];

        return $this;
    }

    /**
     * Update part of statement
     *
     * @param array $data
     * @param array $conditions
     * @return \Origin\Model\QueryBuilder
     */
    public function update(array $data, array $conditions = []) : QueryBuilder
    {
        $this->clear();

        $this->query = [
            'type' => 'UPDATE',
            'data' => $data,
            'conditions' => $conditions,
            'order' => null,
            'limit' => null,
        ];

        return $this;
    }

    /**
     * Delete Statement
     *
     * @param array $conditions
     * @return \Origin\Model\QueryBuilder
     */
    public function delete(array $conditions = []) : QueryBuilder
    {
        $this->clear();

        $this->query = [
            'type' => 'DELETE',
            'conditions' => $conditions,
            'order' => null,
            'limit' => null,
        ];

        return $this;
    }

    /**
     * Sets the from part of the query, this is set when creating the builder
     *
     * @param string $table
     * @param string $alias
    * @return \Origin\Model\QueryBuilder
     */
    public function from(string $table, string $alias = null) : QueryBuilder
    {
        $this->table = $table;

        if ($alias === null) {
            $alias = $table;
        }
        $this->alias = $alias;

        return $this;
    }

    /**
     * Where part
     *
     * @param array $conditions
     * @return \Origin\Model\QueryBuilder
     */
    public function where(array $conditions) : QueryBuilder
    {
        $this->query['conditions'] = $conditions;

        return $this;
    }

    /**
     * Group
     *
     * @param array $group
    * @return \Origin\Model\QueryBuilder
     */
    public function group(array $group) : QueryBuilder
    {
        $this->query['group'] = $group;

        return $this;
    }

    /**
     * Having (when you can use where conditions on aggregate columns)
     *
     * @param array $having
    * @return \Origin\Model\QueryBuilder
    */
    public function having(array $having) : QueryBuilder
    {
        $this->query['having'] = $having;

        return $this;
    }

    /**
     * Undocumented function
     *
     * @param array $order array('field1','field2 ASC') or array('field'=>'ASC')
    * @return \Origin\Model\QueryBuilder
     */
    public function order(array $order) : QueryBuilder
    {
        $this->query['order'] = $order;

        return $this;
    }

    /**
     * Limit
     *
     * @param integer $limit
     * @param integer $offset
    * @return \Origin\Model\QueryBuilder
     */
    public function limit(int $limit, int $offset = null) : QueryBuilder
    {
        $this->query['limit'] = $limit;
        if ($offset !== null) {
            $this->query['offset'] = $offset;
        }

        return $this;
    }

    /**
     * Sets pages
     *
     * @param integer $page
     * @return \Origin\Model\QueryBuilder
     */
    public function page(int $page) : QueryBuilder
    {
        $this->query['page'] = $page;

        return $this;
    }

    /**
     * Join
     *
     * @param array $params
    * @return \Origin\Model\QueryBuilder
     */
    public function join(array $params) : QueryBuilder
    {
        $params += [
            'table' => null,
            'alias' => null,
            'type' => 'LEFT',
            'conditions' => [],
        ];
        
        if (! isset($this->query['joins']) or $this->query['joins'] == null) {
            $this->query['joins'] = [];
        }

        if (empty($params['alias'])) {
            $params['alias'] = $params['table'];
        }

        $this->query['joins'][] = $params;

        return $this;
    }
    /**
      * Left Join
      *
      * @param array $params
     * @return \Origin\Model\QueryBuilder
      */
    public function leftJoin(array $params) : QueryBuilder
    {
        $params['type'] = 'LEFT';

        return $this->join($params);
    }
    /**
      * Inner Join
      *
      * @param array $params
     * @return \Origin\Model\QueryBuilder
      */
    public function innerJoin(array $params) : QueryBuilder
    {
        $params['type'] = 'INNER';

        return $this->join($params);
    }

    /**
     * Right Join
     *
     * @param array $params
    * @return \Origin\Model\QueryBuilder
     */
    public function rightJoin(array $params) : QueryBuilder
    {
        $params['type'] = 'RIGHT';

        return $this->join($params);
    }
    
    /**
      * Full Join
      *
      * @param array $params
     * @return \Origin\Model\QueryBuilder
      */
    public function fullJoin(array $params) : QueryBuilder
    {
        $params['type'] = 'FULL';

        return $this->join($params);
    }

    /**
     * Converts an array of options into a insert statement.
     *
     * @param array $params (data)
     * @return string $sql
     */
    public function insertStatement(array $params) : string
    {
        if (empty($params) or ! isset($params['data']) or empty($params['data'])) {
            throw new QueryBuilderException('Data is empty');
        }
        $this->clear();

        $fields = array_keys($params['data']);
        $values = array_values($params['data']);

        foreach ($values as $value) {
            if ($value === '') {
                $value = null;
            }
            $this->values[$this->nextPlaceholder()] = $value;
        }

        $fields = implode(', ', $fields);
        $values = $this->placeholdersToString(array_keys($this->values));

        return "INSERT INTO {$this->table} ( {$fields} ) VALUES ( {$values} )";
    }

    /**
     * Converts an array of options into an update statement
     *
     * @param array $params (data,conditions,order,limit)
     * @return string
     */
    public function updateStatement(array $params) : string
    {
        if (empty($params) or ! isset($params['data']) or empty($params['data'])) {
            throw new QueryBuilderException('Data is empty');
        }
        $this->clear();

        $statement = [];
        $setValues = [];
        foreach ($params['data'] as $key => $value) {
            $placeHolder = $this->nextPlaceholder();
            $setValues[] = "{$key} = :{$placeHolder}";
            if ($value === '') {
                $value = null;
            }
            $this->values[$placeHolder] = $value;
        }
        $setValues = implode(', ', $setValues);
        $statement[] = "UPDATE {$this->table} SET {$setValues}";

        if (! empty($params['conditions'])) {
            $statement[] = "WHERE {$this->conditions($this->alias, $params['conditions'])}";
        }

        if (! empty($params['order'])) {
            $statement[] = "ORDER BY {$this->orderToString($params['order'])}";
        }
        if (! empty($params['limit'])) {
            $statement[] = "LIMIT {$this->limitToString($params)}";
        }

        return implode(' ', $statement);
    }

    /**
     * Converts an array of options into a delete statement
     *
     * @param array $params (conditions,order,limit)
     * @return string
     */
    public function deleteStatement(array $params) : string
    {
        if (empty($params) or ! isset($params['conditions'])) {
            throw new QueryBuilderException('Data is empty');
        }
        $this->clear();

        $statement = [];

        $statement[] = "DELETE FROM {$this->table}";

        if (! empty($params['conditions'])) {
            $statement[] = "WHERE {$this->conditions($this->alias, $params['conditions'])}";
        }

        if (! empty($params['order'])) {
            $statement[] = "ORDER BY {$this->orderToString($params['order'])}";
        }
        if (! empty($params['limit'])) {
            $statement[] = "LIMIT {$this->limitToString($params)}";
        }

        return implode(' ', $statement);
    }

    /**
     * Converts an array of options into a statement.
     *
     * @param array $params (fields|conditions|joins|group|having|order|limit|page)
     * @return string
     */
    public function selectStatement(array $params) : string
    {
        if (empty($params) or ! isset($params['fields'])) {
            throw new QueryBuilderException('No Fields.');
        }
        $statement = [];
        // SELECT
        $statement[] = "SELECT {$this->fieldsToString($params['fields'])} FROM {$this->tableReference()}";

        if (! empty($params['joins'])) {
            foreach ($params['joins'] as $join) {
                $statement[] = $this->joinToString($join);
            }
        }

        if (! empty($params['conditions'])) {
            $statement[] = "WHERE {$this->conditions($this->alias, $params['conditions'])}";
        }

        if (! empty($params['group'])) {
            $statement[] = "GROUP BY {$this->groupToString($params['group'])}";
        }
        if (! empty($params['having'])) {
            $statement[] = "HAVING {$this->havingToString($params['having'])}";
        }
        if (! empty($params['order'])) {
            $statement[] = "ORDER BY {$this->orderToString($params['order'])}";
        }
        if (! empty($params['limit'])) {
            $statement[] = "LIMIT {$this->limitToString($params)}";
        }

        return implode(' ', $statement);
    }

    /**
     * Taks an array of fields, adds alaias, and then converts to string
     * for use in a statement.
     *
     * @param array $fields (id / user_name /email)
     * @return string User.id, User.user_name, User.email
     */
    protected function fieldsToString(array $fields) : string
    {
        if (empty($fields)) {
            $fields = [$this->alias.'.*'];
        }

        foreach ($fields as $field) {
            $position = stripos($field, ' AS ') ;
            if ($position) {
                $this->specialFields[] = substr($field, $position + 4); // e.g. SUM(quantity) AS items
            }
        }

        return implode(', ', $this->addAliases($fields));
    }

    /**
     * Takes a join.
     *
     * @param array $params (type,table,alias,conditions)
     * @return string LEFT JOIN users as User ON lead.owner_id = User.id
     */
    protected function joinToString(array $params) : string
    {
        $params += ['type' => 'LEFT','table' => null,'alias' => null, 'conditions' => null];

        $params['type'] = strtoupper($params['type']);
        $tableReference = $this->tableReference($params['table'], $params['alias']);

        return "{$params['type']} JOIN {$tableReference} ON ({$this->conditions($params['alias'], $params['conditions'])})";
    }

    protected function groupToString($fields) : string
    {
        return implode(', ', $this->addAliases((array) $fields));
    }

    protected function havingToString(array $conditions) : string
    {
        return $this->conditions($this->alias, $conditions);
    }

    /**
     * @param array $order [description]
     * @return string clause ORDER BY Country,UserName ASC
     */
    protected function orderToString($order) : string
    {
        $array = [];
        foreach ((array) $order as $key => $value) {
            if (is_int($key)) {
                $array[] = $this->addAlias($value); //user_name}
            } else {
                $array[] = "{$this->addAlias($key)} {$value}"; //user_name => ASC
            }
        }

        return implode(',', $array);
    }

    /**
     * @param array $data (limit,offset or page)
     * @return string LIMIT 10,12
     */
    protected function limitToString(array $data) : ? string
    {
        if (isset($data['page'])) {
            $data['offset'] = ($data['page'] * $data['limit']) - $data['limit'];
        }

        if (isset($data['offset'])) {
            return "{$data['limit']} OFFSET {$data['offset']}";
        }

        return "{$data['limit']}";
    }

    /**
     * Generates the Sql Statement
     *
     * @return string
     */
    public function write()
    {
        $type = null;
        if (! empty($this->query)) {
            $type = $this->query['type'];
        }
        switch ($type) {
            case 'SELECT':
              return $this->selectStatement($this->query);
            break;
            case 'INSERT':
              return $this->insertStatement($this->query);
            break;
            case 'UPDATE':
              return $this->updateStatement($this->query);
            break;
            case 'DELETE':
              return $this->deleteStatement($this->query);
            break;
          }
        throw new QueryBuilderException('Nothing to write.');
    }

    /**
     * Writes a formatted SQL string
     *
     * @return string
     */
    public function writeFormatted() : string
    {
        $haystack = $this->write();
        $statements = ['SELECT', 'FROM', 'WHERE', 'GROUP BY', 'ORDER BY', 'HAVING', 'LIMIT'];
        foreach ($statements as $needle) {
            $position = strpos($haystack, $needle);
            $replace = "\n".$needle."\n ";
            if ($needle == 'LIMIT') {
                $replace = "\n".$needle;
            }
            if ($position !== false) {
                $haystack = substr_replace($haystack, $replace, $position, strlen($needle));
            }
        }

        return $haystack;
    }

    /**
     * Adds aliases to fields.
     *
     * @param array $fields
     * @return array
     */
    protected function addAliases(array $fields) : array
    {
        foreach ($fields as $index => $column) {
            $fields[$index] = $this->addAlias($column);
            if (is_string($index)) {
                $fields[$index] = "{$this->alias}.{$index} AS {$this->escape}{$column}{$this->escape}";
            }
        }

        return $fields;
    }

    /**
     * Changes id to User.id.
     *
     * @param string $field id
     * @param string $alias User
     * @return string $aliasedField User.id
     */
    protected function addAlias(string $field, $alias = null) : string
    {
        if ($alias == null) {
            $alias = $this->alias;
        }
 
        // Ignore formulas, existing aliases or virtual fields by ensuring
        // it starts with letter, only contains letters, underscore and number
        if (! preg_match('/^[a-zA-Z_][a-zA-Z0-9_]*$/', $field)) {
            return $field;
        }
        // these are adds we dont need to stress
        if (in_array($field, $this->specialFields)) {
            return $field;
        }

        return "{$alias}.{$field}";
    }

    /**
     * Parses conditions to string, and adds aliases to the fields.
     *
     * @param string $alias alias which conditions are for
     * @param array $conditions
     * @param string $join AND,OR,NOT
     * @return string $sql
     */
    protected function conditions(string $alias, array $conditions, string $join = 'AND') : string
    {
        $block = [];
       
        foreach ($conditions as $key => $value) {
            //array("Post.created = Post.modified")
            if (is_int($key) and is_string($value)) {
                $block[] = $value;
                continue;
            }
            //array("NOT" => array("Post.title" => array("First post", "Second post", "Third post")  ))
            if (is_string($key) and in_array($key, ['AND', 'OR', 'NOT'])) {
                $buffer = [];
                $start = '(';
                $end = ')';
                foreach ($value as $k => $v) {
                    $data = [$k => $v];
                    if (is_integer($k) and is_string($v)) {
                        $data = [$v]; // e.g ['Post.due_date >= NOW()']
                    } elseif (is_integer($k) and is_array($v)) {
                        $data = $v;
                    }

                    if ($key === 'NOT') {
                        $start = 'NOT (';
                    }
                    $pre = $post = '';
                    if (count($data) > 1) {
                        $pre = '(';
                        $post = ')';
                    }
                    $buffer[] = $pre. $this->conditions($alias, $data, $join) . $post;
                }
                $block[] = $start.implode(' ' . $key . ' ', $buffer).$end;
                continue;
            }
            
            // array('id'=>1234)
            if (is_string($key)) {
                if (strpos($key, ' ') === false) {
                    $field = $key;
                    $expression = '=';
                } else {
                    list($field, $expression) = explode(' ', $key, 2); //['id !=' => 1]
                }
                if (! in_array($expression, $this->operators)) {
                    throw new QueryBuilderException('Invalid Operator '.$expression);
                }

                $block[] = $this->expression($this->addAlias($field, $alias), $expression, $value);
                continue;
            }
            // array(0=>array('tenant_id'=>123))
            if (is_integer($key) and is_array($value)) {
                $block[] = $this->conditions($alias, $value, $join);
            }
        }

        return implode(' '.$join.' ', $block);
    }

    /**
     * Gets the next placeholder, placeholders are unique to the table name is created on.
     *
     * @example contact_tasks becomes :ct
     * @return string placeholder
     */
    protected function nextPlaceholder() : string
    {
        if (! $this->placeholder) {
            preg_match_all('/(?<=\s|_|^)[a-zA-Z]/i', $this->table, $matches);
            $this->placeholder = implode('', $matches[0]);
        }

        return $this->placeholder.$this->i++;
    }

    /**
     * Undocumented function
     *
     * @param string $field
     * @param string $expression
     * @param mixed $value
     * @return string
     */
    protected function expression(string $field, string $expression, $value) : string
    {
        // Handle Null Values
        if ($value === null) {
            // Handle Null Values
            if ($expression == '=') {
                return "{$field} IS NULL";
            } elseif ($expression == '!=') {
                return "{$field} IS NOT NULL";
            }
        }
        //(SELECT STATEMENT) or (value1, value2)
        if (($expression == 'IN' or $expression == 'NOT IN') and is_string($value)) {
            return "{$field} {$expression} ( {$value} )";
        }

        if ($expression == 'BETWEEN' or $expression == 'NOT BETWEEN') {
            if (! is_array($value) or count($value) !== 2) {
                throw new QueryBuilderException('Bad paramaters');
            }
            $placeholder = $this->nextPlaceholder();
            $this->values[$placeholder] = $value[0];

            $placeholder2 = $this->nextPlaceholder();
            $this->values[$placeholder2] = $value[1];

            return "( {$field} {$expression} :{$placeholder} AND :{$placeholder2} )";
        }

        if (! is_array($value)) {
            $placeholder = $this->nextPlaceholder();
            $this->values[$placeholder] = $value;

            return "{$field} {$expression} :{$placeholder}";
        }
        // We should not have array here
        if (! in_array($expression, ['=', '!=', 'IN', 'NOT IN'])) {
            throw new QueryBuilderException('Bad paramaters');
        }
        $placeholders = [];
        foreach ($value as $key => $v) {
            $placeholders[] = $placeholder = $this->nextPlaceholder();
            $this->values[$placeholder] = $v;
        }
        if ($expression == '=') {
            $expression = 'IN';
        } elseif ($expression == '!=') {
            $expression = 'NOT IN';
        }

        // Reassign Arrays
        return "{$field} {$expression} ( ".$this->placeholdersToString(array_values($placeholders)).' )';
    }

    protected function placeholdersToString(array $placeholders)
    {
        array_walk($placeholders, function (&$value, &$key) {
            $value = ":{$value}";
        });

        return implode(', ', $placeholders);
    }
}
