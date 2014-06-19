<?php
namespace Spot;

use Doctrine\DBAL\Types\Type;
use Sabre\Event\EventEmitter;

/**
 * Base DataMapper
 *
 * @package Spot
 */
class Mapper
{
    protected $_config;
    protected $_entityName;

    // Entity manager
    protected static $_entityManager = [];
    protected static $_eventEmitter;

    // Class Names for required classes - Here so they can be easily overridden
    protected $_collectionClass = '\\Spot\\Entity\\Collection';
    protected $_queryClass = '\\Spot\\Query';

    // Array of hooks
    protected $_hooks = [];

    /**
     *  Constructor Method
     */
    public function __construct(Config $config, $entityName)
    {
        $this->_config = $config;
        $this->_entityName = $entityName;
    }

    /**
     * Get config class mapper was instantiated with
     *
     * @return Spot\Config
     */
    public function config()
    {
        return $this->_config;
    }

    /**
     * Get config class mapper was instantiated with
     *
     * @return Spot\Config
     */
    public function entity($entityName = null)
    {
        if($entityName === null) {
            if($this->_entityName === null) {
                throw new Exception("Entity must be set with \$mapper->entity('<entityName>') first!");
            }
            return $this->_entityName;
        }
        $this->_entityName = $entityName;
        return $this;
    }

    /**
     * Get query class name to use
     *
     * @return string
     */
    public function queryClass()
    {
        return $this->_queryClass;
    }

    /**
     * Get collection class name to use
     *
     * @return string
     */
    public function collectionClass()
    {
        return $this->_collectionClass;
    }

    /**
     * Entity manager class for storing information and meta-data about entities
     */
    public function entityManager()
    {
        $entityName = $this->entity();
        if (!isset(self::$_entityManager[$entityName])) {
            self::$_entityManager[$entityName] = new Entity\Manager($entityName);
        }
        return self::$_entityManager[$entityName];
    }

    /**
     * Event emitter for this mapper
     */
    public function eventEmitter()
    {
        if (self::$_eventEmitter === null) {
            self::$_eventEmitter = new EventEmitter();
        }
        return self::$_eventEmitter;
    }

    /**
     * Query resolver class for perparing and executing queries, then returning the results
     */
    public function resolver()
    {
        return new Query\Resolver($this);
    }

    /**
     * Get table name
     *
     * @return string Name of table defined on entity class
     */
    public function table()
    {
        return $this->entityManager()->table();
    }

    /**
     * Get formatted fields with all neccesary array keys and values.
     * Merges defaults with defined field values to ensure all options exist for each field.
     *
     * @param string $entityName Name of the entity class
     * @return array Defined fields plus all defaults for full array of all possible options
     */
    public function fields()
    {
        return $this->entityManager()->fields();
    }

    /**
     * Get field information exactly how it is defined in the class
     *
     * @return array Defined fields plus all defaults for full array of all possible options
     */
    public function fieldsDefined()
    {
        return $this->entityManager()->fieldsDefined();
    }

    /**
     * Get defined relations
     */
    public function relations()
    {
        return $this->entityManager()->relations();
    }

    /**
     * Get value of primary key for given row result
     *
     * @param object $entity Instance of an entity to find the primary key of
     */
    public function primaryKey(Entity $entity)
    {
        $pkField = $this->entityManager()->primaryKeyField();
        return $entity->$pkField;
    }

    /**
     * Get value of primary key for given row result
     */
    public function primaryKeyField()
    {
        return $this->entityManager()->primaryKeyField();
    }

    /**
     * Check if field exists in defined fields
     *
     * @param string $field Field name to check for existence
     */
    public function fieldExists($field)
    {
        return array_key_exists($field, $this->fields($this->entity()));
    }

    /**
     * Return field type
     *
     * @param string $field Field name
     * @return mixed Field type string or boolean false
     */
    public function fieldType($field)
    {
        $fields = $this->fields($this->entity());
        return $this->fieldExists($this->entity(), $field) ? $fields[$field]['type'] : false;
    }

    /**
     * Get connection to use
     *
     * @param string $connectionName Named connection or entity class name
     * @return Spot_Adapter
     * @throws Spot_Exception
     */
    public function connection($connectionName = null)
    {
        // Try getting connection based on given name
        if($connectionName === null) {
            return $this->config()->defaultConnection();
        } elseif($connection = $this->config()->connection($connectionName)) {
            return $connection;
        } elseif($connection = $this->config()->defaultConnection()) {
            return $connection;
        }

        throw new Exception("Connection '" . $connectionName . "' does not exist. Please setup connection using Spot_Config::addConnection().");
    }

    /**
     * Create collection
     */
    public function collection($cursor, $with = [])
    {
        $entityName = $this->entity();
        $results = [];
        $resultsIdentities = [];

        // Ensure PDO only gives key => value pairs, not index-based fields as well
        // Raw PDOStatement objects generally only come from running raw SQL queries or other custom stuff
        if($cursor instanceof \PDOStatement) {
            $cursor->setFetchMode(\PDO::FETCH_ASSOC);
        }

        // Fetch all results into new entity class
        // @todo Move this to collection class so entities will be lazy-loaded by Collection iteration
        $entityFields = $this->fields();
        foreach($cursor as $data) {
            // Do type conversion
            $data = $this->convertToPHPValues($entityName, $data);

            $entity = new $entityName($data);
            $entity->isNew(false);

            // Store in array for Collection
            $results[] = $entity;

            // Store primary key of each unique record in set
            $pk = $this->primaryKey($entity);
            if(!in_array($pk, $resultsIdentities) && !empty($pk)) {
                $resultsIdentities[] = $pk;
            }
        }

        $collectionClass = $this->collectionClass();
        $collection = new $collectionClass($results, $resultsIdentities, $entityName);
        return $this->with($collection, $entityName, $with);
    }

    /**
     * Pre-emtively load associations for an entire collection
     */
    public function with($collection, $entityName, $with = [])
    {
        $return = $this->triggerStaticHook($entityName, 'beforeWith', array($collection, $with, $this));
        if (false === $return) {
            return $collection;
        }

        foreach($with as $relationName) {
            $return = $this->triggerStaticHook($entityName, 'loadWith', array($collection, $relationName, $this));
            if (false === $return) {
                continue;
            }

            $relationObj = $this->loadRelation($collection, $relationName);

            // double execute() to make sure we get the
            // \Spot\Entity\Collection back (and not just the \Spot\Query)
            $related_entities = $relationObj->execute()->limit(null)->execute();

            // Load all entities related to the collection
            foreach ($collection as $entity) {
                $collectedEntities = [];
                $collectedIdentities = [];
                foreach ($related_entities as $related_entity) {
                    $resolvedConditions = $relationObj->resolveEntityConditions($entity, $relationObj->unresolvedConditions());

                    // @todo this is awkward, but $resolvedConditions['where'] is returned as an array
                    foreach ($resolvedConditions as $key => $value) {
                        if ($related_entity->$key == $value) {
                            $pk = $this->primaryKey($related_entity);
                            if(!in_array($pk, $collectedIdentities) && !empty($pk)) {
                                $collectedIdentities[] = $pk;
                            }
                            $collectedEntities[] = $related_entity;
                        }
                    }
                }
                if ($relationObj instanceof \Spot\Relation\HasOne) {
                    $relation_collection = array_shift($collectedEntities);
                } else {
                    $relation_collection = new \Spot\Entity\Collection(
                        $collectedEntities, $collectedIdentities, $entity->$relationName->entityName()
                    );
                }
                $entity->$relationName->assignCollection($relation_collection);
            }
        }

        $resultAfter = $this->triggerStaticHook($entityName, 'afterWith', array($collection, $with, $this));
        return $collection;
    }

    /**
     * Get array of entity data
     */
    public function data(Entity $entity, array $data = [])
    {
        // SET data
        if(count($data) > 0) {
            return $entity->data($data);
        }

        // GET data
        return $entity->data();
    }

    /**
     * Get a new entity object, or an existing
     * entity from identifiers
     *
     * @param mixed $identifier Primary key or array of key/values
     * @return mixed Depends on input
     *         false If $identifier is scalar and no entity exists
     */
    public function get($identifier = false)
    {
        $entityClass = $this->entity();
        $pkField = $this->primaryKeyField();

        if(false === $identifier) {
            // No parameter passed, create a new empty entity object
            $entity = new $entityClass();
            $entity->data([$pkField => null]);
        } else if(is_array($identifier)) {
            // An array was passed, create a new entity with that data
            $entity = new $entityClass($identifier);
            $entity->data([$pkField => null]);
        } else {
            // Scalar, find record by primary key
            $entity = $this->first([$pkField => $identifier]);
            if(!$entity) {
                return false;
            }
        }

        // Set default values if entity not loaded
        if(!$this->primaryKey($entity)) {
            $entityDefaultValues = $this->entityManager()->fieldDefaultValues();
            if(count($entityDefaultValues) > 0) {
                $entity->data($entityDefaultValues);
            }
        }

        return $entity;
    }

    /**
     * Get a new entity object, set given data on it
     *
     * @param array $data array of key/values to set on new Entity instance
     * @return object Instance of $entityClass with $data set on it
     */
    public function build(array $data)
    {
        $className = $this->entity();
        return new $className($data);
    }

    /**
     * Get a new entity object, set given data on it, and save it
     *
     * @param array $data array of key/values to set on new Entity instance
     * @return object Instance of $entityClass with $data set on it
     */
    public function create(array $data)
    {
        $entity = $this->build($data);
        if($this->insert($entity)) {
            return $entity;
        }
        return false;
    }

    /**
     * Find records with custom query
     *
     * @param string $sql Raw query or SQL to run against the datastore
     * @param array Optional $conditions Array of binds in column => value pairs to use for prepared statement
     */
    public function query($sql, array $params = [])
    {
        $result = $this->connection()->query($sql, $params);
        if($result) {
            return $this->collection($result);
        }
        return false;
    }

    /**
     * Find all records
     *
     * @return Spot\Query
     */
    public function all()
    {
        return $this->select();
    }

    /**
     * Find records with given conditions
     * If all parameters are empty, find all records
     *
     * @param array $conditions Array of conditions in column => value pairs
     * @return Spot\Query
     */
    public function where(array $conditions = [])
    {
        return $this->select()->where($conditions);
    }

    /**
     * Find first record matching given conditions
     *
     * @param array $conditions Array of conditions in column => value pairs
     */
    public function first(array $conditions = [])
    {
        $query = $this->where($conditions)->limit(1);
        $collection = $query->execute();
        if($collection) {
            return $collection->first();
        } else {
            return false;
        }
    }

    /**
     * Begin a new database query - get query builder
     * Acts as a kind of factory to get the current adapter's query builder object
     *
     * @return \Spot\Query
     */
    public function queryBuilder()
    {
        $query = new $this->_queryClass($this);
        return $query;
    }

    /**
     * Begin a new database query - get query builder
     * Acts as a kind of factory to get the current adapter's query builder object
     *
     * @param string $entityName Name of the entity class
     * @param mixed $fields String for single field or array of fields
     *
     * @return \Spot\Query
     */
    public function select($fields = "*")
    {
        $table = $this->table();
        return $this->queryBuilder()->select($fields)->from($table, $table);
    }

    /**
     * Save record
     * Will update if primary key found, insert if not
     * Performs validation automatically before saving record
     *
     * @param \Spot\Entity $entity Entity object
     */
    public function save(Entity $entity)
    {
        // Set entity name
        $this->entity(get_class($entity));

        // Run beforeSave to know whether or not we can continue
        if (false === $this->triggerInstanceHook($entity, 'beforeSave', $this)) {
            return false;
        }

        if($entity->isNew()) {
            $result = $this->insert($entity);
        } else {
            $result = $this->update($entity);
        }

        // Use return value from 'afterSave' method if not null
        $resultAfter = $this->triggerInstanceHook($entity, 'afterSave', array($this, $result));
        return (null !== $resultAfter) ? $resultAfter : $result;
    }

    /**
     * Insert record
     *
     * @param mixed $entity Entity object or array of field => value pairs
     * @params array $options Array of adapter-specific options
     */
    public function insert($entity, array $options = [])
    {
        if(is_object($entity)) {
            $entityName = get_class($entity);
            $this->entity($entityName);
        } elseif(is_array($entity)) {
            $entityName = $this->entity();
            $entity = $this->get()->data($entity);
        } else {
            throw new Exception(__METHOD__ . " Accepts either an entity object or entity data array");
        }

        // Run beforeInsert to know whether or not we can continue
        $resultAfter = null;
        if (false === $this->triggerInstanceHook($entity, 'beforeInsert', $this)) {
            return false;
        }

        // Run validation
        if(!$this->validate($entity)) {
            return false;
        }

        // Ensure there is actually data to update
        $data = $entity->data();
        if(count($data) > 0) {
            $pkField = $this->primaryKeyField();

            // Save only known, defined fields
            $entityFields = $this->fields();
            $data = array_intersect_key($data, $entityFields);

            // Do type conversion
            $data = $this->convertToDatabaseValues($entityName, $data);

            // Don't pass NULL for "serial" columns (causes issues with PostgreSQL + others)
            if(array_key_exists($pkField, $data) && empty($data[$pkField])) {
                unset($data[$pkField]);
            }

            // Send to adapter via named connection
            $result = $this->resolver()->create($this->table(), $data);

            // Update primary key on entity object
            $pkField = $this->primaryKeyField();
            $entity->$pkField = $result;
            $entity->isNew(false);

            // Run afterInsert
            $resultAfter = $this->triggerInstanceHook($entity, 'afterInsert', array($this, $result));
        } else {
            $result = false;
        }

        return (null !== $resultAfter) ? $resultAfter : $result;
    }

    /**
     * Update given entity object
     *
     * @param object $entity Entity object
     * @params array $options Array of adapter-specific options
     */
    public function update($entity, array $options = [])
    {
        if(is_object($entity)) {
            $entityName = get_class($entity);
        } else {
            throw new Exception(__METHOD__ . " Requires an entity object as the first parameter");
        }

        // Run beforeUpdate to know whether or not we can continue
        $resultAfter = null;
        if (false === $this->triggerInstanceHook($entity, 'beforeUpdate', $this)) {
            return false;
        }

        // Run validation
        if(!$this->validate($entity)) {
            return false;
        }

        // Prepare data
        $data = $entity->dataModified();
        // Save only known, defined fields
        $entityFields = $this->fields();
        $data = array_intersect_key($data, $entityFields);

        // Do type conversion
        $data = $this->convertToDatabaseValues($entityName, $data);

        if(count($data) > 0) {
            $result = $this->connection()->update($this->table(), $data, array($this->primaryKeyField() => $this->primaryKey($entity)));

            // Run afterUpdate
            $resultAfter = $this->triggerInstanceHook($entity, 'afterUpdate', array($this, $result));
        } else {
            $result = true;
        }

        return (null !== $resultAfter) ? $resultAfter : $result;
    }

    /**
     * Upsert save entity - insert or update on duplicate key. Intended to be
     * used in conjunction with fields that are marked 'unique'
     *
     * @param array $data array of key/values to set on new Entity instance
     * @param array $where array of keys to select record by for updating if it already exists
     * @return object Instance of $entityClass with $data set on it
     */
    public function upsert(array $data, array $where)
    {
        $entityClass = $this->entity();
        $entity = new $entityClass($data);
        $result = $this->insert($entity);
        // Unique constraint produces a validation error
        if($result === false && $entity->hasErrors()) {
            $dataUpdate = array_diff_key($data, $where);
            $existingEntity = $this->first($where);
            if(!$existingEntity) {
                return $entity;
            }
            $existingEntity->data($dataUpdate);
            $entity = $existingEntity;
            $result = $this->update($entity);
        }
        return $entity;
    }

    /**
     * Delete items matching given conditions
     *
     * @param mixed $entityName Name of the entity class or entity object
     * @param array $conditions Optional array of conditions in column => value pairs
     */
    public function delete($entityName, array $conditions = [])
    {
        if(is_object($entityName)) {
            $entity = $entityName;
            $entityName = get_class($entityName);
            $conditions = array($this->primaryKeyField() => $this->primaryKey($entity));
            // @todo Clear entity from identity map on delete, when implemented

            // Run beforeDelete to know whether or not we can continue
            $resultAfter = null;
            if (false === $this->triggerInstanceHook($entity, 'beforeDelete', $this)) {
                return false;
            }

            $query = $this->queryBuilder()->delete($this->table())->where($conditions);
            $result = $this->resolver()->exec($query);

            // Run afterDelete
            $resultAfter = $this->triggerInstanceHook($entity, 'afterDelete', array($this, $result));
            return (null !== $resultAfter) ? $resultAfter : $result;

        // Passed array of conditions
        } elseif (is_array($entityName) && empty($conditions)) {
            $conditions = $entityName;
        }

        $query = $this->queryBuilder()->delete($this->table());
        if (!empty($conditions)) {
            $query->where($conditions);
        }

        return $this->resolver()->exec($query);
    }

    /**
     * Prepare data to be dumped to the data store
     */
    protected function convertToDatabaseValues($entityName, array $data)
    {
        $dbData = array();
        $fields = $entityName::fields();
        $platform = $this->connection()->getDatabasePlatform();
        foreach($data as $field => $value) {
            $typeHandler = Type::getType($fields[$field]['type']);
            $dbData[$field] = $typeHandler->convertToDatabaseValue($value, $platform);
        }
        return $dbData;
    }

    /**
     * Retrieve data from the data store
     */
    protected function convertToPHPValues($entityName, array $data)
    {
        $phpData = array();
        $fields = $entityName::fields();
        $platform = $this->connection()->getDatabasePlatform();
        $entityData = array_intersect_key($data, $fields);
        foreach($data as $field => $value) {
            // Field is in the Entity definitions
            if(isset($entityData[$field])) {
                $typeHandler = Type::getType($fields[$field]['type']);
                $phpData[$field] = $typeHandler->convertToPHPValue($value, $platform);
            // Extra data returned with query (like calculated valeus, etc.)
            } else {
                $phpData[$field] = $value;
            }
        }
        return $phpData;
    }

    /**
     * Transaction with closure
     */
    public function transaction(\Closure $work, $entityName = null)
    {
        $connection = $this->connection($entityName);

        try {
            $connection->beginTransaction();

            // Execute closure for work inside transaction
            $result = $work($this);

            // Rollback on boolean 'false' return
            if($result === false) {
                $connection->rollback();
            } else {
                $connection->commit();
            }
        } catch(\Exception $e) {
            // Rollback on uncaught exception
            $connection->rollback();

            // Re-throw exception so we don't bury it
            throw $e;
        }
    }

    /**
     * Truncate table
     * Should delete all rows and reset serial/auto_increment keys
     */
    public function truncateTable()
    {
        return $this->resolver()->truncate($this->table());
    }

    /**
     * Drop/delete table
     * Destructive and dangerous - drops entire data source and all data
     *
     * @param string $entityName Name of the entity class
     */
    public function dropTable()
    {
        return $this->resolver()->dropTable($this->table());
    }

    /**
     * Migrate table structure changes from model to database
     *
     * @param string $entityName Name of the entity class
     */
    public function migrate()
    {
        return $this->resolver()->migrate();
    }

    /**
     * Run set validation rules on fields
     */
    public function validate(\Spot\Entity $entity)
    {
        $entityName = get_class($entity);

        $v = new \Valitron\Validator($entity->data());

        // Check validation rules on each feild
        $uniqueWhere = [];
        foreach($this->fields($entityName) as $field => $fieldAttrs) {
            // Required field
            if(isset($fieldAttrs['required']) && true === $fieldAttrs['required']) {
                $v->rule('required', $field);
            }

            // Unique field
            if($entity->isNew() && isset($fieldAttrs['unique']) && !empty($fieldAttrs['unique'])) {
                if(is_string($fieldAttrs['unique'])) {
                    // Named group
                    $fieldKeyName = $fieldAttrs['unique'];
                    $uniqueWhere[$fieldKeyName][$field] = $entity->$field;
                } else {
                    $uniqueWhere[$field] = $entity->$field;
                }
            }

            // Field with 'options'
            if(isset($fieldAttrs['options']) && is_array($fieldAttrs['options'])) {
                $v->rule('in', $field, $fieldAttrs['options']);
            }

            // Valitron validation rules
            if(isset($fieldAttrs['validation']) && is_array($fieldAttrs['validation'])) {
                foreach($fieldAttrs['validation'] as $rule => $ruleName) {
                    $params = [];
                    if(is_string($rule)) {
                        $params = (array) $ruleName;
                        $ruleName = $rule;
                    }
                    $params = array_merge(array($ruleName, $field), $params);
                    call_user_func_array(array($v, 'rule'), $params);
                }
            }
        }

        // Unique validation
        if(!empty($uniqueWhere)) {
            foreach($uniqueWhere as $field => $value) {
                if(!is_array($value)) {
                    $value = [$field => $entity->$field];
                }
                if($this->first($value) !== false) {
                    $entity->error($field, "" . ucwords(str_replace('_', ' ', $field)) . " '" . implode('-', $value) . "' is already taken.");
                }
            }
        }

        if(!$v->validate()) {
            $entity->errors($v->errors(), false);
        }

        // Return error result
        return !$entity->hasErrors();
    }

    /**
     * Add event listener
     */
    public function on($entityName, $hook, callable $callable)
    {
        $this->_hooks[$entityName][$hook][] = $callable;
        return $this;
    }

    /**
     * Remove event listener
     */
    public function off($entityName, $hooks, $callable = null)
    {
        if (isset($this->_hooks[$entityName])) {
            foreach ((array)$hooks as $hook) {
                if (true === $hook) {
                    unset($this->_hooks[$entityName]);
                } else if (isset($this->_hooks[$entityName][$hook])) {
                    if (null !== $callable) {
                        if ($key = array_search($this->_hooks[$entityName][$hook], $callable, true)) {
                            unset($this->_hooks[$entityName][$hook][$key]);
                        }
                    } else {
                        unset($this->_hooks[$entityName][$hook]);
                    }
                }
            }
        }
        return $this;
    }

    /**
     * Get all hooks added on a model
     */
    public function getHooks($entityName, $hook)
    {
        $hooks = [];
        if (isset($this->_hooks[$entityName]) && isset($this->_hooks[$entityName][$hook])) {
            $hooks = $this->_hooks[$entityName][$hook];
        }
        if (is_callable(array($entityName, 'hooks'))) {
            $entityHooks = $entityName::hooks();
            if (isset($entityHooks[$hook])) {
                // If you pass an object/method combination
                if (is_callable($entityHooks[$hook])) {
                    $hooks[] = $entityHooks[$hook];
                } else {
                    $hooks = array_merge($hooks, $entityHooks[$hook]);
                }
            }
        }
        return $hooks;
    }

    /**
     * Trigger an instance hook on the passed object.
     */
    protected function triggerInstanceHook($object, $hook, $arguments = [])
    {
        if (is_object($arguments) || !is_array($arguments)) {
            $arguments = array($arguments);
        }
        $ret = null;
        $hooks = $this->getHooks(get_class($object), $hook);
        foreach($hooks as $callable) {
            if (is_callable(array($object, $callable))) {
                $ret = call_user_func_array(array($object, $callable), $arguments);
            } else {
                $args = array_merge(array($object), $arguments);
                $ret = call_user_func_array($callable, $args);
            }
            if (false === $ret) {
                return false;
            }
        }
        return $ret;
    }

    /**
     * Trigger a static hook.  These pass the $object as the first argument
     * to the hook, and expect that as the return value.
     */
    protected function triggerStaticHook($objectClass, $hook, $arguments)
    {
        if (is_object($arguments) || !is_array($arguments)) {
            $arguments = array($arguments);
        }
        array_unshift($arguments, $objectClass);
        foreach ($this->getHooks($objectClass, $hook) as $callable) {
            $return = call_user_func_array($callable, $arguments);
            if (false === $return) {
                return false;
            }
        }
    }
}
