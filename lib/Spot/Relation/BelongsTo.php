<?php
namespace Spot\Relation;

use Spot\Mapper;
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
            $this->result = $this->queryObject()->execute()->first();
        }
        return $this->result;
    }

    // Magic getter/setter passthru
    // ----------------------------------------------
    public function __get($key)
    {
        $this->execute()->$key;
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
        return isset($entity[$key]);
    }

    public function offsetGet($key)
    {
        $entity = $this->execute();
        return $entity[$key];
    }

    public function offsetSet($key, $value)
    {
        $entity = $this->execute();

        if($key === null) {
            return $entity[] = $value;
        } else {
            return $entity[$key] = $value;
        }
    }

    public function offsetUnset($key)
    {
        $entity = $this->execute();
        unset($entity[$key]);
    }
}
