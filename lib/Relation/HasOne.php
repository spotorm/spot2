<?php
namespace Spot\Relation;

use Spot\Mapper;
use Spot\EntityInterface;
use Spot\Entity\Collection;

/**
 * HasOne Relation
 *
 * Only used so that the query can be lazy-loaded on demand
 *
 * @package Spot
 */
class HasOne extends RelationAbstract implements \ArrayAccess
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
     * @param EntityInterface $entity Entity to save relation from
     * @param string $relationName Name of the relation to save
     * @param array $options Options to pass to the mappers
     * @return boolean
     */
    public function save(EntityInterface $entity, $relationName, $options = [])
    {
        $lastResult = false;
        $relatedEntity = $entity->relation($relationName);
        $relatedMapper = $this->mapper()->getMapper($this->entityName());
        //Autoloaded relation, no need to save
        if ($relatedEntity instanceof HasOne) {
            return 0;
        }
        
        if ($relatedEntity === false || $relatedEntity->get($this->foreignKey()) !== $entity->primaryKey()) {

            if ($relatedMapper->entityManager()->fields()[$this->foreignKey()]['notnull']) {
                $relatedMapper->delete([$this->foreignKey() => $entity->primaryKey()]);
            } else {
                $relatedMapper->queryBuilder()->builder()->update($relatedMapper->table())->set($this->foreignKey(), null)->where([$this->foreignKey() => $entity->primaryKey()]);
            }
            
            if ($relatedEntity instanceof EntityInterface) {
                //Update the foreign key to match the main entity primary key
                $relatedEntity->set($this->foreignKey(), $entity->primaryKey());
            }
        }
        
        if ($relatedEntity instanceof EntityInterface && ($relatedEntity->isNew() || $relatedEntity->isModified())) {
            $lastResult = $relatedMapper->save($relatedEntity, $options);
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
    public function offsetExists(mixed $offset): bool
    {
        $entity = $this->execute();

        return isset($entity->$offset);
    }

    public function offsetGet(mixed $offset): mixed
    {
        $entity = $this->execute();

        return $entity->$key;
    }

    public function offsetSet(mixed $offset, mixed $value): void
    {
        $entity = $this->execute();

        if ($offset === null) {
            $entity[] = $value;
        } else {
            $entity->$offset = $value;
        }
    }

    public function offsetUnset(mixed $offset): void
    {
        $entity = $this->execute();
        unset($entity->$offset);
    }
}
