<?php
namespace Spot\Relation;

use Spot\Mapper;
use Spot\Entity;
use Spot\Entity\Collection;

/**
 * Relation object for HasMany relation
 *
 * @package Spot
 */
class HasManyThrough extends RelationAbstract implements \Countable, \IteratorAggregate, \ArrayAccess
{
    protected $throughCollection;

    /**
     * Constructor function
     */
    public function __construct(Mapper $mapper, $entityName, $throughEntityName, $foreignKey, $localKey, $identityValue)
    {
        $this->mapper = $mapper;

        $this->entityName = $entityName;
        $this->throughEntityName = $throughEntityName;
        $this->foreignKey = $foreignKey; // selecht
        $this->localKey = $localKey; // where

        $this->identityValue = $identityValue;
    }

    /**
     * Set identity values from given collection
     *
     * @param \Spot\Entity\Collection
     */
    public function identityValuesFromCollection(Collection $collection)
    {
        $this->identityValue($collection->resultsIdentities());
    }

    /**
     * Build query object
     *
     * @return \Spot\Query
     */
    protected function buildQuery()
    {
        $selectField = $this->foreignKey();
        $whereField = $this->localKey();

        $hasManyMapper = $this->mapper()->getMapper($this->entityName());
        $hasManyPkField = $hasManyMapper->primaryKeyField();

        $throughMapper = $this->mapper()->getMapper($this->throughEntityName());
        $throughQuery = $throughMapper->select()->where([$whereField => $this->identityValue()]);

        // Save results of through collection to map resulting entities back to original objects
        $this->throughCollection = $throughQuery->execute();
        $throughEntityIds = $this->throughCollection->toArray($selectField);

        // Use resulting IDs to get desired entity relations
        $query = $hasManyMapper->select()->where([$hasManyPkField => $throughEntityIds]);

        return $query;
    }

    /**
     * Map relation results to original collection of entities
     *
     * @param string Relation name
     * @param Spot\Collection Collection of original entities to map results of query back to
     *
     * @return Spot\Collection
     */
    public function eagerLoadOnCollection($relationName, Collection $collection)
    {
        // Get relation object and change the 'identityValue' to an array
        // of all the identities in the current collection
        $this->identityValuesFromCollection($collection);
        $relationForeignKey = $this->foreignKey(); // tag_id
        $relationLocalKey = $this->localKey();     // post_id
        $relationEntityKey = $this->entityKey();
        $relatedMapper = $this->mapper()->getMapper($this->entityName());
        $relationRelatedForeignKey = $relatedMapper->primaryKeyField();
        $collectionRelations = $this->query()->execute();
        $collectionClass = $relatedMapper->collectionClass();

        // HasManyThrough has to map out resulting key to original collection
        // keys since resulting relation objects won't have any reference to
        // the entities in the original collection
        $entityRelations = [];
        foreach ($this->throughCollection as $throughEntity) {
            $throughForeignKey = $throughEntity->$relationForeignKey;
            $throughLocalKey = $throughEntity->$relationLocalKey;
            /* $throughKeys[$throughLocalKey][] = $throughForeignKey; */

            foreach ($collectionRelations as $relatedEntity) {
                $relatedEntityPk = $relatedEntity->$relationRelatedForeignKey;
                if ($relatedEntityPk == $throughForeignKey) {
                    $entityRelations[$throughLocalKey][] = $relatedEntity;
                }
            }
        }

        // Set relation collections back on each entity object
        foreach ($collection as $entity) {
            if (isset($entityRelations[$entity->$relationEntityKey])) {
                $entityCollection = new $collectionClass($entityRelations[$entity->$relationEntityKey]);
                $entity->relation($relationName, $entityCollection);
            } else {
                $entity->relation($relationName, new $collectionClass());
            }
        }

        return $collection;
    }

    /**
     * Get through entity name
     */
    public function throughEntityName()
    {
        return $this->throughEntityName;
    }

    /**
     * SPL Countable function
     * Called automatically when attribute is used in a 'count()' function call
     *
     * @return integer
     */
    public function count()
    {
        $results = $this->execute();

        return $results ? count($results) : 0;
    }

    /**
     * SPL IteratorAggregate function
     * Called automatically when attribute is used in a 'foreach' loop
     *
     * @return \Spot\Entity\Collection
     */
    public function getIterator()
    {
        // Load related records for current row
        $data = $this->execute();

        return $data ? $data : [];
    }

    // SPL - ArrayAccess functions
    // ----------------------------------------------
    public function offsetExists($key)
    {
        $this->execute();

        return isset($this->result[$key]);
    }

    public function offsetGet($key)
    {
        $this->execute();

        return $this->result[$key];
    }

    public function offsetSet($key, $value)
    {
        $this->execute();

        if ($key === null) {
            return $this->result[] = $value;
        } else {
            return $this->result[$key] = $value;
        }
    }

    public function offsetUnset($key)
    {
        $this->execute();
        unset($this->result[$key]);
    }
}
