<?php
namespace Spot\Relation;

use BadMethodCallException;
use Spot\Entity\Collection;
use Spot\EntityInterface;
use Spot\Mapper;
use Spot\Query;

/**
 * Abstract class for relations
 *
 * @package Spot
 */
abstract class RelationAbstract
{
    protected $mapper;
    protected $entityName;

    protected $foreignKey;
    protected $localKey;
    protected $identityValue;

    protected $query;
    protected $queryQueue = [];

    protected $result;

    /**
     * Get Mapper object
     *
     * @return Mapper
     */
    public function mapper()
    {
        return $this->mapper;
    }

    /**
     * Get entity name object
     *
     * @return string
     */
    public function entityName()
    {
        return $this->entityName;
    }

    /**
     * Get foreign key field
     *
     * @return string
     */
    public function foreignKey()
    {
        return $this->foreignKey;
    }

    /**
     * Get local key field
     *
     * @return string
     */
    public function localKey()
    {
        return $this->localKey;
    }

    /**
     * Get entity key field - defaults to the primary key
     *
     * @return string
     */
    public function entityKey()
    {
        return $this->mapper()->primaryKeyField();
    }

    /**
     * Get identity value
     *
     * @return mixed Array, string, or id
     */
    public function identityValue($identityValue = null)
    {
        if ($identityValue !== null) {
            $this->identityValue = $identityValue;
        }

        return $this->identityValue;
    }

    /**
     * Set identity values from given collection
     */
    abstract public function identityValuesFromCollection(Collection $collection);

    /**
     * Map relation results to original collection of entities
     *
     * @param string Relation name
     * @param \Spot\Collection Collection of original entities to map results of query back to
     *
     * @return \Spot\Collection
     */
    public function eagerLoadOnCollection($relationName, Collection $collection)
    {
        // Get relation object and change the 'identityValue' to an array
        // of all the identities in the current collection
        $this->identityValuesFromCollection($collection);
        $relationForeignKey = $this->foreignKey();
        $relationEntityKey = $this->entityKey();
        $collectionRelations = $this->query();

        // Divvy up related objects for each entity by foreign key value
        // ex. comment foreignKey 'post_id' will == entity primaryKey value
        $entityRelations = [];
        foreach ($collectionRelations as $relatedEntity) {
            // @todo Does this need to be an array?
            $entityRelations[$relatedEntity->$relationForeignKey] = $relatedEntity;
        }

        // Set relation collections back on each entity object
        foreach ($collection as $entity) {
            if (isset($entityRelations[$entity->$relationEntityKey])) {
                $entity->relation($relationName, $entityRelations[$entity->$relationEntityKey]);
            } else {
                $entity->relation($relationName, false);
            }
        }

        return $collection;
    }

    /**
     * Build query object
     *
     * @return Query
     */
    abstract protected function buildQuery();

    /**
     * Get query object instance
     */
    public function query()
    {
        if ($this->query === null) {
            $this->query = $this->buildQuery();
            foreach ($this->queryQueue as $closure) {
                $this->query = call_user_func($closure, $this->query);
            }
        }

        return $this->query;
    }

    /**
     * Execute query and return results
     */
    public function execute()
    {
        if ($this->result === null) {
            $this->result = $this->query()->execute();
        }

        return $this->result;
    }

    /**
     * Save related entities
     *
     * @param EntityInterface $entity Entity to save relation from
     * @param string $relationName Name of the relation to save
     * @param array $options Options to pass to the mappers
     * @return boolean
     */
    abstract public function save(EntityInterface $entity, $relationName, $options = []);

    /**
     * Passthrough for missing methods on expected object result
     */
    public function __call($func, $args)
    {
        if (method_exists('Spot\Query', $func) || in_array($func, array_keys($this->mapper()->getMapper($this->entityName())->scopes()))) {
            // See if method exists on Query object, and if it does, add query
            // modification to queue to be executed after query is built and
            // ready so that query is not executed immediately
            $this->queryQueue[] = function (Query $query) use ($func, $args) {
                return call_user_func_array([$query, $func], $args);
            };

            return $this;
        } else {
            // See if method exists on destination object after execution
            // (typically either Spot\Entity\Collection or Spot\Entity object)
            $result = $this->execute();
            if (method_exists(get_class($result), $func)) {
                return call_user_func_array([$result, $func], $args);
            }

            throw new BadMethodCallException("Method " . get_called_class() . "::$func does not exist");
        }
    }
}
