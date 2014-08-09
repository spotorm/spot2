<?php
namespace Spot\Query;
use Spot\Mapper;

/**
 * Main query resolver
 *
 * @package Spot
 */
class Resolver
{
    protected $mapper;

    /**
     *  Constructor Method
     */
    public function __construct(Mapper $mapper)
    {
        $this->mapper = $mapper;
    }

    /**
     * Migrate table structure changes to database
     */
    public function migrate()
    {
        // Mapper knows currently set entity
        $entity       = $this->mapper->entity();
        $table        = $entity::table();
        $fields       = $this->mapper->entityManager()->fields();
        $fieldIndexes = $this->mapper->entityManager()->fieldKeys();
        $connection   = $this->mapper->connection();

        $schemaManager = $this->mapper->connection()->getSchemaManager();
        $tableObject = $schemaManager->listTableDetails($table);
        $tableObjects[] = $tableObject;
        $schema = new \Doctrine\DBAL\Schema\Schema($tableObjects);

        $tableColumns = $tableObject->getColumns();
        $tableExists = !empty($tableColumns);
        if ($tableExists) {
            // Update existing table
            $existingTable = $schema->getTable($table);
            $newSchema = $this->migrateCreateSchema();
            $queries = $schema->getMigrateToSql($newSchema, $connection->getDatabasePlatform());
        } else {
            // Create new table
            $newSchema = $this->migrateCreateSchema();
            $queries = $newSchema->toSql($connection->getDatabasePlatform());
        }

        // Execute resulting queries
        $lastResult = false;
        foreach ($queries as $sql) {
            $lastResult = $connection->exec($sql);
        }

        return $lastResult;
    }

    /**
     * Migrate create schema
     */
    public function migrateCreateSchema()
    {
        $entityName   = $this->mapper->entity();
        $table        = $entityName::table();
        $fields       = $this->mapper->entityManager()->fields();
        $fieldIndexes = $this->mapper->entityManager()->fieldKeys();

        $schema = new \Doctrine\DBAL\Schema\Schema();
        $table  = $schema->createTable($table);

        foreach ($fields as $field => $fieldInfo) {
            $fieldType = $fieldInfo['type'];
            unset($fieldInfo['type']);
            $table->addColumn($field, $fieldType, $fieldInfo);
        }

        // PRIMARY
        if ($fieldIndexes['primary']) {
            $table->setPrimaryKey($fieldIndexes['primary']);
        }
        // UNIQUE
        foreach ($fieldIndexes['unique'] as $keyName => $keyFields) {
            $table->addUniqueIndex($keyFields, $keyName);
        }
        // INDEX
        foreach ($fieldIndexes['index'] as $keyName => $keyFields) {
            $table->addIndex($keyFields, $keyName);
        }

        return $schema;
    }

    /**
     * Find records with custom SQL query
     *
     * @param  string          $sql   SQL query to execute
     * @param  array           $binds Array of bound parameters to use as values for query
     * @throws \Spot\Exception
     */
    public function query($sql, array $binds = array())
    {
        // 1. Perform query
        // 2. Execute binds
    }

    /**
     * Find records with custom SQL query
     *
     * @param  string          $sql SQL query to execute
     * @throws \Spot\Exception
     */
    public function read(\Spot\Query $query)
    {
        $stmt = $query->builder()->execute();

        // Set PDO fetch mode
        $stmt->setFetchMode(\PDO::FETCH_ASSOC);

        $collection = $query->mapper()->collection($stmt, $query->with());

        // Ensure statement is closed
        $stmt->closeCursor();

        return $collection;
    }

    /**
     * Create new row object with set properties
     *
     * @param string $table Table name
     * @param array  $data  Array of data to save in 'field' => 'value' format
     */
    public function create($table, array $data)
    {
        $connection = $this->mapper->connection();
        $result = $connection->insert($table, $data);

        return $result;
    }

    /**
     * Update
     *
     * @param string $table Table name
     * @param array  $data  Array of data to save in 'field' => 'value' format
     * @param array  $data  Array of data for WHERE clause in 'field' => 'value' format
     */
    public function update($table, array $data, array $where)
    {
        $connection = $this->mapper->connection();

        return $connection->update($table, $data, $where);
    }

    /**
     * Execute provided query and return result
     *
     * @param  string          $sql SQL query to execute
     * @throws \Spot\Exception
     */
    public function exec(\Spot\Query $query)
    {
        return $query->builder()->execute();
    }

    /**
     * Truncate Table
     *
     * @param string $table Table name
     * @param array  $data  Array of data for WHERE clause in 'field' => 'value' format
     */
    public function truncate($table, $cascade = false)
    {
        $mapper = $this->mapper;
        $connection = $mapper->connection();

        // SQLite doesn't support TRUNCATE
        if ($mapper->connectionIs("sqlite")) {
            $sql = "DELETE FROM " . $table;
        } elseif ($mapper->connectionIs("pgsql")) {
            $sql = "TRUNCATE TABLE " . $table . ($cascade ? " CASCADE" : "");
        } else {
            $sql = "TRUNCATE TABLE " . $table . "";
        }

        return $connection->transactional(function ($conn) use ($sql) {
            $conn->exec($sql);
        });
    }

    /**
     * Drop Table
     *
     * @param string $table Table name
     */
    public function dropTable($table)
    {
        $result = false;
        $connection = $this->mapper->connection();
        try {
            $result = $connection->getSchemaManager()->dropTable($table);
        } catch (\Exception $e) {
            $result = false;
        }

        return $result;
    }
}
