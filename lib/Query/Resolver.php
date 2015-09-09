<?php
namespace Spot\Query;

use Spot\Mapper;
use Spot\Query;

/**
 * Main query resolver
 *
 * @package Spot
 * @author Vance Lucas <vance@vancelucas.com>
 */
class Resolver
{
    /**
     * @var \Spot\Mapper
     */
    protected $mapper;

    protected $_noQuote;

    /**
     * Constructor Method
     *
     * @param \Spot\Mapper $mapper
     */
    public function __construct(Mapper $mapper)
    {
        $this->mapper = $mapper;
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
     * Migrate table structure changes to database
     *
     * @return bool
     * @throws \Doctrine\DBAL\Schema\SchemaException
     * @throws \Spot\Exception
     */
    public function migrate()
    {
        // Mapper knows currently set entity
        $entity = $this->mapper->entity();
        $table = $entity::table();
        $fields = $this->mapper->entityManager()->fields();
        $fieldIndexes = $this->mapper->entityManager()->fieldKeys();
        $connection = $this->mapper->connection();

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
     *
     * @return \Doctrine\DBAL\Schema\Schema
     */
    public function migrateCreateSchema()
    {
        $entityName = $this->mapper->entity();
        $table = $entityName::table();
        $fields = $this->mapper->entityManager()->fields();
        $fieldIndexes = $this->mapper->entityManager()->fieldKeys();

        $schema = new \Doctrine\DBAL\Schema\Schema();
        $table = $schema->createTable($this->escapeIdentifier($table));

        foreach ($fields as $field) {
            $fieldType = $field['type'];
            unset($field['type']);
            $table->addColumn($this->escapeIdentifier($field['column']), $fieldType, $field);
        }

        // PRIMARY
        if ($fieldIndexes['primary']) {
            $resolver = $this;
            $primaryKeys = array_map(function($value) use($resolver) { return $resolver->escapeIdentifier($value); }, $fieldIndexes['primary']);
            $table->setPrimaryKey($primaryKeys);
        }
        // UNIQUE
        foreach ($fieldIndexes['unique'] as $keyName => $keyFields) {
            $table->addUniqueIndex($keyFields, $this->escapeIdentifier($keyName));
        }
        // INDEX
        foreach ($fieldIndexes['index'] as $keyName => $keyFields) {
            $table->addIndex($keyFields, $this->escapeIdentifier($keyName));
        }

        return $schema;
    }

    /**
     * Find records with custom SQL query
     *
     * @param \Spot\Query $query SQL query to execute
     * @return \Spot\Entity\Collection
     * @throws \Spot\Exception
     */
    public function read(Query $query)
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
     * @param array $data Array of data to save in 'field' => 'value' format
     */
    public function create($table, array $data)
    {
        $connection = $this->mapper->connection();
        return $connection->insert($this->escapeIdentifier($table), $this->dataWithFieldAliasMappings($data));
    }

    /**
     * Update
     *
     * @param string $table Table name
     * @param array $data Array of data for WHERE clause in 'field' => 'value' format
     * @param array $where
     * @return
     * @throws \Spot\Exception
     */
    public function update($table, array $data, array $where)
    {
        $connection = $this->mapper->connection();
        return $connection->update($this->escapeIdentifier($table), $this->dataWithFieldAliasMappings($data), $this->dataWithFieldAliasMappings($where));
    }

    /**
     * Taken given field name/value inputs and map them to their aliased names
     */
    public function dataWithFieldAliasMappings(array $data)
    {
        $fields = $this->mapper->entityManager()->fields();
        $fieldMappings = [];
        foreach($data as $field => $value) {
            $fieldAlias = $this->escapeIdentifier(isset($fields[$field]) ? $fields[$field]['column'] : $field);
            $fieldMappings[$fieldAlias] = $value;
        }
        return $fieldMappings;
    }

    /**
     * Execute provided query and return result
     *
     * @param  \Spot\Query $query SQL query to execute
     * @return \Doctrine\DBAL\Driver\Statement|int
     * @throws \Spot\Exception
     */
    public function exec(Query $query)
    {
        return $query->builder()->execute();
    }

    /**
     * Truncate Table
     *
     * @param string $table Table name
     * @param bool $cascade
     * @return
     * @throws \Spot\Exception
     * @internal param array $data Array of data for WHERE clause in 'field' => 'value' format
     */
    public function truncate($table, $cascade = false)
    {
        $mapper = $this->mapper;
        $connection = $mapper->connection();

        $table = $this->escapeIdentifier($table);

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
     * @return bool
     */
    public function dropTable($table)
    {
        $result = false;
        $connection = $this->mapper->connection();
        try {
            $result = $connection->getSchemaManager()->dropTable($this->escapeIdentifier($table));
        } catch (\Exception $e) {
            $result = false;
        }

        return $result;
    }

    /**
     * Escape/quote identifier
     *
     * @param string $identifier
     * @return string
     */
    public function escapeIdentifier($identifier)
    {
        if($this->_noQuote) {
            return $identifier;
        }

        return $this->mapper->connection()->quoteIdentifier(trim($identifier));
    }
}
