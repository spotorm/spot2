<?php

namespace Spot;

use Doctrine\DBAL\Types\Type;

/**
 * Query Object - Used to build adapter-independent queries PHP-style
 *
 * @package Spot
 * @author Vance Lucas <vance@vancelucas.com>
 */
class Query implements \Countable, \IteratorAggregate, \ArrayAccess, \JsonSerializable
{
    const ALL_FIELDS = '*';

    /**
     * @var \Spot\Mapper
     */
    protected $_mapper;

    /**
     * @var string
     */
    protected $_entityName;

    /**
     * @var string
     */
    protected $_tableName;

    /**
     * @var
     */
    protected $_queryBuilder;

    /**
     * @var boolean
     */
    protected $_noQuote;

    /**
     * Storage for query properties
     *
     * @var array
     */
    protected $with = [];

    /**
     * Storage for eager-loaded relations
     *
     * @var array
     */
    protected $_data = [];

    /**
     * Custom methods added by extensions or plugins
     *
     * @var array
     */
    protected static $_customMethods = [];

    /**
     * @var array
     */
    protected static $_whereOperators = [
        '<' => 'Spot\Query\Operator\LessThan',
        ':lt' => 'Spot\Query\Operator\LessThan',
        '<=' => 'Spot\Query\Operator\LessThanOrEqual',
        ':lte' => 'Spot\Query\Operator\LessThanOrEqual',
        '>' => 'Spot\Query\Operator\GreaterThan',
        ':gt' => 'Spot\Query\Operator\GreaterThan',
        '>=' => 'Spot\Query\Operator\GreaterThanOrEqual',
        ':gte' => 'Spot\Query\Operator\GreaterThanOrEqual',
        '~=' => 'Spot\Query\Operator\RegExp',
        '=~' => 'Spot\Query\Operator\RegExp',
        ':regex' => 'Spot\Query\Operator\RegExp',
        ':like' => 'Spot\Query\Operator\Like',
        ':notlike' => 'Spot\Query\Operator\NotLike',
        ':fulltext' => 'Spot\Query\Operator\FullText',
        ':fulltext_boolean' => 'Spot\Query\Operator\FullTextBoolean',
        'in' => 'Spot\Query\Operator\In',
        ':in' => 'Spot\Query\Operator\In',
        '<>' => 'Spot\Query\Operator\Not',
        '!=' => 'Spot\Query\Operator\Not',
        ':ne' => 'Spot\Query\Operator\Not',
        ':not' => 'Spot\Query\Operator\Not',
        '=' => 'Spot\Query\Operator\Equals',
        ':eq' => 'Spot\Query\Operator\Equals',
    ];

    /**
     * Already instantiated operator objects
     *
     * @var array
     */
    protected static $_whereOperatorObjects = [];

    /**
     * Constructor Method
     *
     * @param \Spot\Mapper $mapper
     * @throws Exception
     * @internal param $Spot_Mapper
     * @internal param string $entityName Name of the entity to query on/for
     */
    public function __construct(Mapper $mapper)
    {
        $this->_mapper = $mapper;
        $this->_entityName = $mapper->entity();
        $this->_tableName = $mapper->table();

        // Create Doctrine DBAL query builder from Doctrine\DBAL\Connection
        $this->_queryBuilder = $mapper->connection()->createQueryBuilder();
    }

    /**
     * Get current Doctrine DBAL query builder object
     *
     * @return \Doctrine\DBAL\Query\QueryBuilder
     */
    public function builder()
    {
        return $this->_queryBuilder;
    }

    /**
     * Set field and value quoting on/off - maily used for testing output SQL
     * since quoting is different per platform
     *
     * @param bool $noQuote
     * @return $this
     */
    public function noQuote($noQuote = true)
    {
        $this->_noQuote = $noQuote;

        return $this;
    }

    /**
     * Return DBAL Query builder expression
     *
     * @return \Doctrine\DBAL\Query\Expression\ExpressionBuilder
     */
    public function expr()
    {
        return $this->builder()->expr();
    }

    /**
     * Add a custom user method via closure or PHP callback
     *
     * @param  string $method Method name to add
     * @param  callable $callback Callback or closure that will be executed when missing method call matching $method is made
     * @throws \InvalidArgumentException
     */
    public static function addMethod($method, callable $callback)
    {
        if (method_exists(__CLASS__, $method)) {
            throw new \InvalidArgumentException("Method '" . $method . "' already exists on " . __CLASS__);
        }
        self::$_customMethods[$method] = $callback;
    }

    /**
     * Adds a custom type to the type map.
     *
     * @param string $operator
     * @param callable|string $action
     */
    public static function addWhereOperator($operator, $action)
    {
        if (isset(self::$_whereOperators[$operator])) {
            throw new \InvalidArgumentException("Where operator '" . $operator . "' already exists");
        }

        static::$_whereOperators[$operator] = $action;
    }

    /**
     * Get current adapter object
     *
     * @return \Spot\Mapper
     */
    public function mapper()
    {
        return $this->_mapper;
    }

    /**
     * Get current entity name query is to be performed on
     *
     * @return string
     */
    public function entityName()
    {
        return $this->_entityName;
    }

    /**
     * Select (passthrough to DBAL QueryBuilder)
     *
     * @return $this
     */
    public function select()
    {
        call_user_func_array([$this->builder(), 'select'], $this->escapeIdentifier(func_get_args()));

        return $this;
    }

    /**
     * Delete (passthrough to DBAL QueryBuilder)
     *
     * @return $this
     */
    public function delete()
    {
        call_user_func_array([$this->builder(), 'delete'], $this->escapeIdentifier(func_get_args()));

        return $this;
    }

    /**
     * From (passthrough to DBAL QueryBuilder)
     *
     * @return $this
     */
    public function from()
    {
        call_user_func_array([$this->builder(), 'from'], $this->escapeIdentifier(func_get_args()));

        return $this;
    }

    /**
     * Get all bound query parameters (passthrough to DBAL QueryBuilder)
     *
     * @return mixed
     */
    public function getParameters()
    {
        return call_user_func_array([$this->builder(), __FUNCTION__], func_get_args());
    }

    /**
     * Set query parameters (passthrough to DBAL QueryBuilder)
     *
     * @return $this
     */
    public function setParameters()
    {
        call_user_func_array([$this->builder(), __FUNCTION__], func_get_args());

        return $this;
    }

    /**
     * WHERE conditions
     *
     * @param array $where Array of conditions for this clause
     * @param string $type Keyword that will separate each condition - "AND", "OR"
     * @return $this
     */
    public function where(array $where, $type = 'AND')
    {
        if (!empty($where)) {
            $whereClause = implode(' ' . $type . ' ', $this->parseWhereToSQLFragments($where));
            $this->builder()->andWhere($whereClause);
        }

        return $this;
    }

    /**
     * WHERE OR conditions
     *
     * @param array $where Array of conditions for this clause
     * @param string $type Keyword that will separate each condition - "AND", "OR"
     * @return $this
     */
    public function orWhere(array $where, $type = 'AND')
    {
        if (!empty($where)) {
            $whereClause = implode(' ' . $type . ' ', $this->parseWhereToSQLFragments($where));
            $this->builder()->orWhere($whereClause);
        }

        return $this;
    }

    /**
     * WHERE AND conditions
     *
     * @param array $where Array of conditions for this clause
     * @param string $type Keyword that will separate each condition - "AND", "OR"
     * @return $this|Query
     */
    public function andWhere(array $where, $type = 'AND')
    {
        return $this->where($where, $type);
    }

    /**
     * WHERE field + raw SQL
     *
     * @param string $field Field name for SQL statement (will be quoted)
     * @param string $sql SQL string to put in WHERE clause
     * @param array $params
     * @return $this
     * @throws Exception
     */
    public function whereFieldSql($field, $sql, array $params = [])
    {
        $builder = $this->builder();
        $placeholderCount = substr_count($sql, '?');
        $paramCount = count($params);
        if ($placeholderCount !== $paramCount) {
            throw new Exception("Number of supplied parameters (" . $paramCount . ") does not match the number of provided placeholders (" . $placeholderCount . ")");
        }

        $sql = preg_replace_callback('/\?/', function ($match) use ($builder, &$params) {
            $param = array_shift($params);

            return $builder->createPositionalParameter($param);
        }, $sql);
        $builder->andWhere($this->escapeIdentifier($field) . ' ' . $sql);

        return $this;
    }

    /**
     * WHERE conditions
     *
     * @param string $sql SQL string to put in WHERE clause
     * @return $this
     */
    public function whereSql($sql)
    {
        $this->builder()->andWhere($sql);

        return $this;
    }

    /**
     * Parse array-syntax WHERE conditions and translate them to DBAL QueryBuilder syntax
     *
     * @param array $where Array of conditions for this clause
     * @param bool $useAlias
     * @return array SQL fragment strings for WHERE clause
     * @throws Exception
     */
    private function parseWhereToSQLFragments(array $where, $useAlias = true)
    {
        $builder = $this->builder();

        $sqlFragments = [];
        foreach ($where as $column => $value) {
            // Column name with comparison operator
            $colData = explode(' ', $column);
            $operator = isset($colData[1]) ? $colData[1] : '=';
            if (count($colData) > 2) {
                $operator = array_pop($colData);
                $colData = [implode(' ', $colData), $operator];
            }

            $operatorCallable = $this->getWhereOperatorCallable(strtolower($operator));
            if (!$operatorCallable) {
                throw new \InvalidArgumentException("Unsupported operator '" . $operator . "' "
                    . "in WHERE clause. If you want to use a custom operator, you "
                    . "can add one with \Spot\Query::addWhereOperator('" . $operator . "', "
                    . "function (QueryBuilder \$builder, \$column, \$value) { ... }); ");
            }

            $col = $colData[0];

            // Handle DateTime value objects
            if ($value instanceof \DateTime) {
                $mapper = $this->mapper();
                $convertedValues = $mapper->convertToDatabaseValues($mapper->entity(), [$col => $value]);
                $value = $convertedValues[$col];
            }

            // Prefix column name with alias
            if ($useAlias === true) {
                $col = $this->fieldWithAlias($col);
            }

            $sqlFragments[] = $operatorCallable($builder, $col, $value);
        }

        return $sqlFragments;
    }

    /**
     * @param string $operator
     * @return callable|false
     */
    private function getWhereOperatorCallable($operator)
    {
        if (!isset(static::$_whereOperators[$operator])) {
            return false;
        }

        if (is_callable(static::$_whereOperators[$operator])) {
            return static::$_whereOperators[$operator];
        }

        if (!isset(static::$_whereOperatorObjects[$operator])) {
            static::$_whereOperatorObjects[$operator] = new static::$_whereOperators[$operator]();
        }

        return static::$_whereOperatorObjects[$operator];
    }

    /**
     * Relations to be eager-loaded
     *
     * @param mixed|null $relations Array/string of relation(s) to be loaded.
     * @return $this|array
     */
    public function with($relations = null)
    {
        if ($relations === null) {
            return $this->with;
        }

        $this->with = array_unique(array_merge((array)$relations, $this->with));

        return $this;
    }

    /**
     * Search criteria (FULLTEXT, LIKE, or REGEX, depending on storage engine and driver)
     *
     * @param  mixed $fields Single string field or array of field names to use for searching
     * @param  string $query Search keywords or query
     * @param  array $options Array of options for search
     * @return $this
     */
    public function search($fields, $query, array $options = [])
    {
        $fields = (array)$fields;
        $entityDatasourceOptions = $this->mapper()->entityManager()->datasourceOptions($this->entityName());
        $fieldString = '`' . implode('`, `', $fields) . '`';
        $fieldTypes = $this->mapper()->fields($this->entityName());

        // See if we can use FULLTEXT search
        $whereType = ':like';
        $connection = $this->mapper()->connection($this->entityName());
        // Only on MySQL
        if ($connection instanceof \Spot\Adapter\Mysql) {
            // Only for MyISAM engine
            if (isset($entityDatasourceOptions['engine'])) {
                $engine = $entityDatasourceOptions['engine'];
                if ('myisam' == strtolower($engine)) {
                    $whereType = ':fulltext';
                    // Only if ALL included columns allow fulltext according to entity definition
                    if (in_array($fields, array_keys($this->mapper()->fields($this->entityName())))) {
                        // FULLTEXT
                        $whereType = ':fulltext';
                    }

                    // Boolean mode option
                    if (isset($options['boolean']) && $options['boolean'] === true) {
                        $whereType = ':fulltext_boolean';
                    }
                }
            }
        }

        // @todo Normal queries can't search mutliple fields, so make them separate searches instead of stringing them together

        // Resolve search criteria
        return $this->where([$fieldString . ' ' . $whereType => $query]);
    }

    /**
     * ORDER BY columns
     *
     * @param  array $order Array of field names to use for sorting
     * @return $this
     */
    public function order(array $order)
    {
        foreach ($order as $field => $sorting) {
            $this->builder()->addOrderBy($this->fieldWithAlias($field), $sorting);
        }

        return $this;
    }

    /**
     * GROUP BY clause
     *
     * @param  array $fields Array of field names to use for grouping
     * @return $this
     */
    public function group(array $fields = [])
    {
        foreach ($fields as $field) {
            $this->builder()->addGroupBy($this->fieldWithAlias($field));
        }

        return $this;
    }

    /**
     * Having clause to filter results by a calculated value
     *
     * @param array $having Array (like where) for HAVING statement for filter records by
     * @param string $type
     * @return $this
     */
    public function having(array $having, $type = 'AND')
    {
        $this->builder()->having(implode(' ' . $type . ' ', $this->parseWhereToSQLFragments($having, false)));

        return $this;
    }

    /**
     * Limit executed query to specified amount of records
     * Implemented at adapter-level for databases that support it
     *
     * @param int $limit Number of records to return
     * @param int $offset Record to start at for limited result set
     * @return $this
     */
    public function limit($limit, $offset = null)
    {
        $this->builder()->setMaxResults($limit);
        if ($offset !== null) {
            $this->offset($offset);
        }

        return $this;
    }

    /**
     * Offset executed query to skip specified amount of records
     * Implemented at adapter-level for databases that support it
     *
     * @param int $offset Record to start at for limited result set
     * @return $this
     */
    public function offset($offset)
    {
        $this->builder()->setFirstResult($offset);

        return $this;
    }

    // ===================================================================

    /**
     * SPL Countable function
     * Called automatically when attribute is used in a 'count()' function call
     *
     * Executes separate query with COUNT(*), and drops and ordering (not
     * important for counting)
     *
     * @return int
     */
    public function count(): int
    {
        $countCopy = clone $this->builder();
        $stmt = $countCopy->select('COUNT(*)')->resetQueryPart('orderBy')->execute();

        return (int)$stmt->fetchColumn(0);
    }

    /**
     * SPL IteratorAggregate function
     * Called automatically when attribute is used in a 'foreach' loop
     *
     * @return \Spot\Entity\Collection
     */
    public function getIterator(): \Traversable
    {
        // Execute query and return result set for iteration
        $result = $this->execute();

        return ($result !== false) ? $result : [];
    }

    /**
     * Convenience function passthrough for Collection
     *
     * @param string|null $keyColumn
     * @param string|null $valueColumn
     * @return array
     */
    public function toArray($keyColumn = null, $valueColumn = null)
    {
        $result = $this->execute();

        return ($result !== false) ? $result->toArray($keyColumn, $valueColumn) : [];
    }

    /**
     * JsonSerializable
     *
     * @inheritdoc
     */
    public function jsonSerialize(): mixed
    {
        return $this->toArray();
    }

    /**
     * Return the first entity matched by the query
     *
     * @return mixed Spot_Entity on success, boolean false on failure
     */
    public function first()
    {
        $result = $this->limit(1)->execute();

        return ($result !== false) ? $result->first() : false;
    }

    /**
     * Execute and return query as a collection
     *
     * @return mixed Collection object on success, boolean false on failure
     */
    public function execute()
    {
        // @TODO Add caching to execute based on resulting SQL+data so we don't execute same query w/same data multiple times
        return $this->mapper()->resolver()->read($this);
    }

    /**
     * Get raw SQL string from built query
     *
     * @return string
     */
    public function toSql()
    {
        if ($this->_noQuote) {
            $escapeCharacter = $this->mapper()->connection()->getDatabasePlatform()->getIdentifierQuoteCharacter();
            return str_replace($escapeCharacter, '', $this->builder()->getSQL());
        }

        return $this->builder()->getSQL();
    }

    /**
     * Escape/quote direct user input
     *
     * @param string $string
     * @return string
     */
    public function escape($string)
    {
        if ($this->_noQuote) {
            return $string;
        }

        return $this->mapper()->connection()->quote($string);
    }

    /**
     * Removes escape/quote character
     *
     * @param string $identifier
     * @return string
     */
    public function unescapeIdentifier($identifier)
    {
        if (strpos($identifier, ".") !== false) {
            $parts = array_map(array($this, "unescapeIdentifier"), explode(".", $identifier));

            return implode(".", $parts);
        }

        return trim($identifier, $this->mapper()->connection()->getDatabasePlatform()->getIdentifierQuoteCharacter());
    }

    /**
     * Escape/quote identifier
     *
     * @param string|array $identifier
     * @return string|array
     */
    public function escapeIdentifier($identifier)
    {
        if (is_array($identifier)) {
            array_walk($identifier, function(&$identifier) {
                $identifier = $this->escapeIdentifier($identifier);
            });
            return $identifier;
        }

        if ($this->_noQuote || $identifier === self::ALL_FIELDS) {
            return $identifier;
        }

        if (strpos($identifier, ' ') !== false || strpos($identifier, '(') !== false) {
            return $identifier; // complex expression, ain't quote it, do it manually!
        }

        return $this->mapper()->connection()->quoteIdentifier(trim($identifier));
    }

    /**
     * Get field name with table alias appended
     * @param string $field
     * @param bool $escaped
     * @return string
     */
    public function fieldWithAlias($field, $escaped = true)
    {
        $fieldInfo = $this->_mapper->entityManager()->fields();
        
        // Detect function in field name
        $field = trim($field);
        $function = strpos($field, '(');
        if ($function) {
            foreach ($fieldInfo as $key => $currentField) {
                $fieldFound = strpos($field, $key);
                if ($fieldFound) {
                    $functionStart = substr($field, 0, $fieldFound);
                    $functionEnd = substr($field, $fieldFound + strLen($key));
                    $field = $key;
                    break;
                }
            }
        }

        // Determine real field name (column alias support)
        if (isset($fieldInfo[$field])) {
            $field = $fieldInfo[$field]['column'];
        }

        $field = $this->_tableName . '.' . $field;
        $field = $escaped ? $this->escapeIdentifier($field) : $field;
        
        $result = $function ? $functionStart : '';
        $result .= $field;
        $result .= $function ? $functionEnd : '';

        return $result;
    }

    /**
     * SPL - ArrayAccess
     *
     * @inheritdoc
     */
    public function offsetExists(mixed $offset): bool
    {
        $results = $this->getIterator();

        return isset($results[$offset]);
    }

    /**
     * SPL - ArrayAccess
     *
     * @inheritdoc
     */
    public function offsetGet(mixed $offset): mixed
    {
        $results = $this->getIterator();

        return $results[$offset];
    }

    /**
     * SPL - ArrayAccess
     *
     * @inheritdoc
     */
    public function offsetSet(mixed $offset, mixed $value): void
    {
        $results = $this->getIterator();
        if ($offset === null) {
            $results[] = $value;
        } else {
            $results[$offset] = $value;
        }
    }

    /**
     * SPL - ArrayAccess
     *
     * @inheritdoc
     */
    public function offsetUnset(mixed $offset): void
    {
        $results = $this->getIterator();
        unset($results[$offset]);
    }

    /**
     * Run user-added callback
     *
     * @param  string $method Method name called
     * @param  array $args Array of arguments used in missing method call
     * @return mixed
     * @throws \BadMethodCallException
     */
    public function __call($method, $args)
    {
        $scopes = $this->mapper()->scopes();

        // Custom methods
        if (isset(self::$_customMethods[$method]) && is_callable(self::$_customMethods[$method])) {
            $callback = self::$_customMethods[$method];
            // Pass the current query object as the first parameter
            array_unshift($args, $this);

            return call_user_func_array($callback, $args);

            // Scopes
        } elseif (isset($scopes[$method])) {
            // Pass the current query object as the first parameter
            array_unshift($args, $this);

            return call_user_func_array($scopes[$method], $args);

            // Methods on Collection
        } elseif (method_exists('\\Spot\\Entity\\Collection', $method)) {
            return call_user_func_array([$this->execute(), $method], $args);

            // Error
        } else {
            throw new \BadMethodCallException("Method '" . __CLASS__ . "::" . $method . "' not found");
        }
    }
}
