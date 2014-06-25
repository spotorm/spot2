<?php
namespace Spot\Relation;

use Spot\Mapper;
use Spot\Entity;

/**
 * Relation object for HasMany relation
 *
 * @package Spot
 */
class HasMany implements \Countable, \IteratorAggregate, \ArrayAccess
{
    protected $mapper;
    protected $entity;
    protected $queryObject;
    protected $queryClosure;

    /**
     * Constructor function
     */
    public function __construct(Mapper $mapper, Entity $entity, $entityName, $foreignKey, $identityValue)
    {
        $this->mapper = $mapper;
        $this->entity = $entity;

        $this->entityName = $entityName;
        $this->foreignKey = $foreignKey;

        $this->identityValue = $identityValue;
    }

    /**
     * Get Mapper object
     */
    public function mapper()
    {
        return $this->mapper;
    }

    /**
     * Get entity name object
     */
    public function entityName()
    {
        return $this->entityName;
    }

    /**
     * Get foreign key field
     */
    public function foreignKey()
    {
        return $this->foreignKey;
    }

    /**
     * Get identity value
     */
    public function identityValue($identityValue = null)
    {
        if ($identityValue !== null) {
            $this->identityValue = $identityValue;
        }
        return $this->identityValue;
    }

    /**
     * Closure to alter query on execution
     */
    public function query(\Closure $closure)
    {
        $this->queryClosure = $closure;
        return $this;
    }

    /**
     * Get query object instance
     */
    public function queryObject()
    {
        if ($this->queryObject === null) {
            $foreignMapper = $this->mapper()->getMapper($this->entityName());
            $this->queryObject = $foreignMapper->where([$this->foreignKey => $this->identityValue()]);
            // if ($this->queryClosure) {
            //     $this->queryObject = call_user_func($this->queryClosure, $this->queryObject);
            // }
        }
        return $this->queryObject;
    }

    /**
     * Execute query and return results
     */
    public function execute()
    {
        return $this->queryObject()->execute();
    }

    /**
     * Passthrough for missing methods on expected object result
     */
    public function __call($func, $args)
    {
        $obj = $this->queryObject();
        if (is_object($obj)) {
            return call_user_func_array([$obj, $func], $args);
        } else {
            return $obj;
        }
    }

    /**
     * SPL Countable function
     * Called automatically when attribute is used in a 'count()' function call
     *
     * @return int
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
        return isset($this->_collection[$key]);
    }

    public function offsetGet($key)
    {
        $this->execute();
        return $this->_collection[$key];
    }

    public function offsetSet($key, $value)
    {
        $this->execute();

        if ($key === null) {
            return $this->_collection[] = $value;
        } else {
            return $this->_collection[$key] = $value;
        }
    }

    public function offsetUnset($key)
    {
        $this->execute();
        unset($this->_collection[$key]);
    }
}
