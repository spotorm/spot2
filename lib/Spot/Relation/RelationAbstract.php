<?php
namespace Spot\Relation;

use Spot\Query;
use Spot\Entity;
use Spot\Entity\Collection;

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

    protected $queryObject;
    protected $queryClosure;

    protected $result;

    /**
     * Get Mapper object
     *
     * @return Spot\Mapper
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
     * Closure to alter query on execution
     */
    public function query(\Closure $closure)
    {
        $this->queryClosure = $closure;
        return $this;
    }

    /**
     * Build query object
     *
     * @return \Spot\Query
     */
    abstract protected function buildQuery();

    /**
     * Get query object instance
     */
    public function queryObject()
    {
        if ($this->queryObject === null) {
            $this->queryObject = $this->buildQuery();
            if ($this->queryClosure) {
                $this->queryObject = call_user_func($this->queryClosure, $this->queryObject);
            }
        }
        return $this->queryObject;
    }

    /**
     * Execute query and return results
     */
    public function execute()
    {
        $this->_collection = $this->queryObject()->execute();
        return $this->_collection;
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
            throw new \BadMethodCallException("Method " . get_called_class() . "::$func does not exist");
        }
    }
}
