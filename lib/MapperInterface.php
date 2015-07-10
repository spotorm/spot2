<?php
namespace Spot;

/**
 * Base DataMapper Interface
 *
 * @package Spot
 */
interface MapperInterface
{
    public function __construct(Locator $locator, $entityName);

    /**
     * Get config class from locator
     *
     * @return \Spot\Config
     */
    public function config();

    /**
     * Get mapper for specified entity
     *
     * @param  string      $entityName Name of Entity object to load mapper for
     * @return \Spot\Mapper
     */
    public function getMapper($entityName);

    /**
     * Get name of the Entity class mapper was instantiated with
     *
     * @return string $entityName
     */
    public function entity();

    /**
     * Get query class name to use
     *
     * @return string
     */
    public function queryClass();

    /**
     * Get collection class name to use
     *
     * @return string
     */
    public function collectionClass();

    /**
     * Entity manager class for storing information and meta-data about entities
     */
    public function entityManager();

    /**
     * Event emitter for this mapper
     */
    public function eventEmitter();

    /**
     * Reset and load Events for mapped entity
     */
    public function loadEvents();

    /**
     * Load Relations for mapped entity
     */
    public function loadRelations(EntityInterface $entity);

    /**
     * Relation: HasMany
     */
    public function hasMany(EntityInterface $entity, $entityName, $foreignKey, $localValue = null);

    /**
     * Relation: HasManyThrough
     */
    public function hasManyThrough(EntityInterface $entity, $hasManyEntity, $throughEntity, $selectField, $whereField);

    /**
     * Relation: HasOne
     *
     * HasOne assumes that the foreignKey will be on the foreignEntity.
     */
    public function hasOne(EntityInterface $entity, $foreignEntity, $foreignKey);

    /**
     * Relation: BelongsTo
     *
     * BelongsTo assumes that the localKey will reference the foreignEntity's
     * primary key. If this is not the case, you probably want to use the
     * 'hasOne' relationship instead.
     */
    public function belongsTo(EntityInterface $entity, $foreignEntity, $localKey);

    /**
     * Prepare entity and load necessary objects on it
     */
    public function prepareEntity(EntityInterface $entity);

    /**
     * Query resolver class for perparing and executing queries, then returning the results
     */
    public function resolver();

    /**
     * Get table name
     *
     * @return string Name of table defined on entity class
     */
    public function table();

    /**
     * Get formatted fields with all neccesary array keys and values.
     * Merges defaults with defined field values to ensure all options exist for each field.
     *
     * @param  string $entityName Name of the entity class
     * @return array  Defined fields plus all defaults for full array of all possible options
     */
    public function fields();

    /**
     * Get field information exactly how it is defined in the class
     *
     * @return array Defined fields plus all defaults for full array of all possible options
     */
    public function fieldsDefined();

    /**
     * Get defined relations
     */
    public function relations();

    /**
     * Get defined scopes
     */
    public function scopes();

    /**
     * Get value of primary key for given row result
     *
     * @param object $entity Instance of an entity to find the primary key of
     */
    public function primaryKey(EntityInterface $entity);

    /**
     * Get value of primary key for given row result
     */
    public function primaryKeyField();

    /**
     * Check if field exists in defined fields
     *
     * @param string $field Field name to check for existence
     */
    public function fieldExists($field);

    /**
     * Check if field exists in defined fields
     *
     * @param string $field Field name to check for existence
     */
    public function fieldInfo($field);

    /**
     * Return field type
     *
     * @param  string $field Field name
     * @return mixed  Field type string or boolean false
     */
    public function fieldType($field);

    /**
     * Get connection to use
     *
     * @param  string         $connectionName Named connection or entity class name
     * @return \Doctrine\DBAL\Connection
     * @throws \Spot\Exception
     */
    public function connection($connectionName = null);

    /**
     * Test to see if collection is of the given type
     *
     * @param string Database type, something like "mysql", "sqlite", "pgsql", etc.
     * @return boolean
     */
    public function connectionIs($type);

    /**
     * Create collection from \Spot\Query object
     */
    public function collection($cursor, $with = []);

    /**
     * Get a new entity object, or an existing
     * entity from identifiers
     *
     * @param  mixed $identifier Primary key or array of key/values
     * @return mixed Depends on input
     *                          false If $identifier is scalar and no entity exists
     */
    public function get($identifier = false);

    /**
     * Get a new entity object, set given data on it
     *
     * @param  array  $data array of key/values to set on new Entity instance
     * @return object Instance of $entityClass with $data set on it
     */
    public function build(array $data);

    /**
     * Get a new entity object, set given data on it, and save it
     *
     * @param  array          $data array of key/values to set on new Entity instance
     * @return object         Instance of $entityClass with $data set on it
     * @throws \Spot\Exception
     */
    public function create(array $data);

    /**
     * Find records with custom query
     *
     * @param string         $sql        Raw query or SQL to run against the datastore
     * @param array Optional $conditions Array of binds in column => value pairs to use for prepared statement
     */
    public function query($sql, array $params = []);

    /**
     * Find all records
     *
     * @return \Spot\Query
     */
    public function all();

    /**
     * Find records with given conditions
     * If all parameters are empty, find all records
     *
     * @param  array      $conditions Array of conditions in column => value pairs
     * @return \Spot\Query
     */
    public function where(array $conditions = []);

    /**
     * Find first record matching given conditions
     *
     * @param array $conditions Array of conditions in column => value pairs
     */
    public function first(array $conditions = []);

    /**
     * Begin a new database query - get query builder
     * Acts as a kind of factory to get the current adapter's query builder object
     *
     * @return \Spot\Query
     */
    public function queryBuilder();

    /**
     * Begin a new database query - get query builder
     * Acts as a kind of factory to get the current adapter's query builder object
     *
     * @param string $entityName Name of the entity class
     * @param mixed  $fields     String for single field or array of fields
     *
     * @return \Spot\Query
     */
    public function select($fields = "*");

    /**
     * Save record
     * Will update if primary key found, insert if not
     * Performs validation automatically before saving record
     *
     * @param \Spot\Entity $entity Entity object
     * @param array optional Array of save options
     */
    public function save(EntityInterface $entity, array $options = []);

    /**
     * Insert record
     *
     * @param mixed $entity  Entity object or array of field => value pairs
     * @param array $options Array of adapter-specific options
     */
    public function insert($entity, array $options = []);

    /**
     * Update given entity object
     *
     * @param object $entity Entity object
     * @params array $options Array of adapter-specific options
     */
    public function update(EntityInterface $entity, array $options = []);

    /**
     * Upsert save entity - insert or update on duplicate key. Intended to be
     * used in conjunction with fields that are marked 'unique'
     *
     * @param  array  $data  array of key/values to set on new Entity instance
     * @param  array  $where array of keys to select record by for updating if it already exists
     * @return object Instance of $entityClass with $data set on it
     */
    public function upsert(array $data, array $where);

    /**
     * Delete items matching given conditions
     *
     * @param mixed $conditions Optional array of conditions in column => value pairs
     */
    public function delete($conditions = []);

    /**
     * Transaction with closure
     */
    public function transaction(\Closure $work, $entityName = null);

    /**
     * Truncate table
     * Should delete all rows and reset serial/auto_increment keys
     */
    public function truncateTable($cascade = false);

    /**
     * Drop/delete table
     * Destructive and dangerous - drops entire data source and all data
     *
     * @param string $entityName Name of the entity class
     */
    public function dropTable();

    /**
     * Migrate table structure changes from model to database
     *
     * @param string $entityName Name of the entity class
     */
    public function migrate();

    /**
     * Run set validation rules on fields
     */
    public function validate(EntityInterface $entity);
}
