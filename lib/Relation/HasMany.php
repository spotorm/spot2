<?php
namespace Spot\Relation;

use Spot\Mapper;
use Spot\Entity;
use Spot\EntityInterface;
use Spot\Entity\Collection;

/**
 * Relation object for HasMany relation
 *
 * @package Spot
 */
class HasMany extends RelationAbstract implements \Countable, \IteratorAggregate, \ArrayAccess
{
    /**
     * Constructor function
     */
    public function __construct(Mapper $mapper, $entityName, $foreignKey, $localKey, $identityValue)
    {
        $this->mapper = $mapper;

        $this->entityName = $entityName;
        $this->foreignKey = $foreignKey;
        $this->localKey = $localKey;

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
        $foreignMapper = $this->mapper()->getMapper($this->entityName());

        return $foreignMapper->where([$this->foreignKey() => $this->identityValue()]);
    }

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
        $collectionClass = $this->mapper()->getMapper($this->entityName())->collectionClass();

        // Divvy up related objects for each entity by foreign key value
        // ex. comment foreignKey 'post_id' will == entity primaryKey value
        $entityRelations = [];
        foreach ($collectionRelations as $relatedEntity) {
            $entityRelations[$relatedEntity->$relationForeignKey][] = $relatedEntity;
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
     * Save related entities
     *
     * @param EntityInterface $entity Entity to save relation from
     * @param string $relationName Name of the relation to save
     * @param array $options Options to pass to the mappers
     * @return boolean
     */
    public function save(EntityInterface $entity, $relationName, $options = [])
    {
        $relatedEntities = $entity->relation($relationName);
        $deletedIds = [];
        $lastResult = false;
        $relatedMapper = $this->mapper()->getMapper($this->entityName());
        if (is_array($relatedEntities) || $relatedEntities instanceof Entity\Collection) {
            $oldEntities = $this->execute();
            $relatedIds = [];
            foreach ($relatedEntities as $related) {
                if ($related->isNew() || $related->isModified() || $related->get($this->foreignKey()) !== $entity->primaryKey()) {
                    //Update the foreign key to match the main entity primary key
                    $related->set($this->foreignKey(), $entity->primaryKey());
                    $lastResult = $relatedMapper->save($related, $options);
                }
                $relatedIds[] = $related->id;
            }

            foreach ($oldEntities as $oldRelatedEntity) {
                if (!in_array($oldRelatedEntity, $relatedIds)) {
                    $deletedIds[] = $oldRelatedEntity->primaryKey();
                }
            }
        }
        if (count($deletedIds) || $relatedEntities === false) {
            $conditions = [$this->foreignKey() => $entity->primaryKey()];
            if (count($deletedIds)) {
                $conditions[$this->localKey().' :in'] = $deletedIds;
            }
            if ($relatedMapper->entityManager()->fields()[$this->foreignKey()]['notnull']) {
                $relatedMapper->delete($conditions);
            } else {
                $relatedMapper->queryBuilder()->builder()->update($relatedMapper->table())->set($this->foreignKey(), null)->where($conditions);
            }
        }

        return $lastResult;
    }

    /**
     * SPL Countable function
     * Called automatically when attribute is used in a 'count()' function call
     *
     * @return integer
     */
    public function count()
    {
        if ($this->result === null) {
            $count = $this->query()->count();
        } else {
            $count = count($this->result);
        }
        return $count;
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
