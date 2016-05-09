<?php
namespace Spot\Relation;

use Spot\Mapper;
use Spot\EntityInterface;
use Spot\Entity\Collection;

/**
 * BelongsTo Relation
 *
 * Only used so that the query can be lazy-loaded on demand
 *
 * @package Spot
 */
class BelongsTo extends RelationAbstract implements \ArrayAccess
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
        $this->identityValue($collection->toArray($this->localKey()));
    }

    /**
     * Get entity key field - for BelongsTo, this will be the local key instead
     * of the primary key.
     *
     * @return string
     */
    public function entityKey()
    {
        return $this->localKey();
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
     * Find first entity in the set
     *
     * @return \Spot\Entity
     */
    public function execute()
    {
        if ($this->result === null) {
            $this->result = $this->query()->execute()->first();
        }

        return $this->result;
    }

    /**
     * Helper function to return the entity
     */
    public function entity()
    {
        return $this->execute();
    }


    /**
     * Save related entities
     *
     * @param ntityInterface $entity Entity to save relation from
     * @param string $relationName Name of the relation to save
     * @param array $options Options to pass to the mappers
     * @return boolean
     */
    public function save(EntityInterface $entity, $relationName, $options = [])
    {
        $lastResult = 0;
        $relatedEntity = $entity->relation($relationName);

        if ($relatedEntity instanceof EntityInterface) {
            if ($relatedEntity->isNew() || $relatedEntity->isModified()) {
                $relatedMapper = $this->mapper()->getMapper($this->entityName());

                $lastResult = $relatedMapper->save($relatedEntity, $options);
                 //Update the local key to match the related entity primary key
                if ($entity->get($this->localKey()) !== $relatedEntity->primaryKey()) {
                    $relatedRelations = $entity->relations($relatedMapper, $relatedEntity);

                    //Check if it was a hasOne or a hasMany relation,
                    //if hasOne, we must unset old value
                    foreach ($relatedRelations as $relatedRelation) {
                        if ($relatedRelation instanceof Relation\HasOne && $relatedRelation->foreignKey() === $this->localKey()) {
                            if ($relatedMapper->entityManager()->fields()[$relatedRelation->foreignKey()]['notnull']) {
                                $lastResult = $relatedMapper->delete([$relatedRelation->foreignKey() => $entity->get($relatedRelation->foreignKey())]);
                            } else {
                                $lastResult = $relatedMapper->queryBuilder()->builder()->update($relatedMapper->table())->set($relatedRelation->foreignKey(), null)->where([$relatedRelation->foreignKey() => $entity->get($relatedRelation->foreignKey())]);
                            }
                        }
                    }
                    $entity->set($this->localKey(), $relatedEntity->primaryKey());
                }
            }
        }

        return $lastResult;
    }

    // Magic getter/setter passthru
    // ----------------------------------------------
    public function __get($key)
    {
        $entity = $this->execute();
        if ($entity) {
            return $entity->$key;
        }
        return null;
    }

    public function __set($key, $val)
    {
        $this->execute()->$key = $val;
    }

    // SPL - ArrayAccess functions
    // ----------------------------------------------
    public function offsetExists($key)
    {
        $entity = $this->execute();

        return isset($entity->$key);
    }

    public function offsetGet($key)
    {
        $entity = $this->execute();

        return $entity->$key;
    }

    public function offsetSet($key, $value)
    {
        $entity = $this->execute();

        if ($key === null) {
            return $entity[] = $value;
        } else {
            return $entity->$key = $value;
        }
    }

    public function offsetUnset($key)
    {
        $entity = $this->execute();
        unset($entity->$key);
    }
}
