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
