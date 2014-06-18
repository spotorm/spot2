<?php
namespace Spot\Entity;

/**
 * Collection of Spot_Entity objects
 *
 * @package Spot
 * @link http://spot.os.ly
 */
class Collection implements \Iterator, \Countable, \ArrayAccess
{
    protected $_results = array();
    protected $_resultsIdentities = array();
    protected $_entityName = null;


    /**
     * Constructor function
     *
     * @param array $results Array of pre-loaded Spot_Entity objects or Iterator that will fetch them lazily
     * @param array $resultsIdentities Array of key values for given result set primary key
     */
    public function __construct(array $results = array(), array $resultsIdentities = array(), $entityName = null)
    {
        $this->_results = $results;
        $this->_resultsIdentities = $resultsIdentities;
        $this->_entityName = $entityName;
    }


    public function entityName()
    {
        return $this->_entityName;
    }


    /**
     * Returns first result in set
     *
     * @return The first result in the set
     */
    public function first()
    {
        $this->rewind();
        return $this->current();
    }

    /**
    * Add a single entity to the collection
    *
    * @param object $entity to add
    */
    public function add($entity)
    {
        $this->_results[] = $entity;
    }

    /**
    * Merge another collection into this collections set of entities
    * This will only add entitys that don't already exist in the current
    * collection
    *
    * @param Spot_Entity_Collection $collection
    * @todo Implement faster uniqueness checking by hash, entity manager, primary key field, etc.
    */
    public function merge(\Spot\Entity\Collection $collection, $onlyUnique = true)
    {
        foreach($collection as $entity) {
            if($onlyUnique && in_array($entity, $this->_results)) {
                continue; // Skip - entity already exists in collection
            }
            $this->add($entity);
        }
        return $this;
    }

    /**
     * Return an array representation of the Collection.
     *  
     * @param mixed $keyColumn
     * @param mixed $valueColumn
     * @return array    If $keyColumn and $valueColumn are not set, or are both null
     *                      then this will return the array of entity objects
     * @return array    If $keyColumn is not null, and the value column is null or undefined
     *                      then this will return an array of the values of the entities in the column defined
     * @returns array   If $keyColumn and $valueColumn are both defined and not null
     *                      then this will return an array where the key is defined by each entities value in $keyColumn
     *                      and the value will be the value of the each entity in $valueColumn
     * 
     * @todo Write unit tests for this function
     */
    public function toArray($keyColumn = null, $valueColumn = null)
    {
        // Both empty
        if(null === $keyColumn && null === $valueColumn) {
            $return = array();
            foreach($this->_results as $row) {
                $return[] = $row->toArray();
            }

        // Key column name
        } elseif(null !== $keyColumn && null === $valueColumn) {
            $return = array();
            foreach($this->_results as $row) {
                $return[] = $row->$keyColumn;
            }

        // Both key and valud columns filled in
        } else {
            $return = array();
            foreach($this->_results as $row) {
                $return[$row->$keyColumn] = $row->$valueColumn;
            }
        }

        return $return;
    }

    /**
    * Run a function on the set of entities
    *
    * @param string|array $function A callback of the function to run
    */
    public function run($callback)
    {
         return call_user_func_array($callback, array($this->_results));
    }
    


    /**
     * Runs a function on every object in the query, returning the resulting array
     * 
     * @param function The function to run
     * @return mixed An array containing the result of running the passed function
     *  on each member of the collect
     */
    public function map($func)
    {
        $ret = array();
        foreach ($this as $obj) {
            $ret[] = $func($obj);
        }
        return $ret;
    }


    /**
     * Runs a function on every object in the query, returning an array containing every
     *  object for which the function returns true.
     *
     * @param function The function to run
     * @return mixed An array of Entity objects
     */
    public function filter($func)
    {
        $ret = new static();
        foreach ($this as $obj) {
            if ($func($obj)) {
                $ret->add($obj);
            }
        }
        return $ret;
    }
    
    /**
    * Provides a string representation of the class
    * Brackets contain the number of elements contained
    * in the collection
    *
    */
    public function __toString()
    {
        return __CLASS__ . "[".$this->count()."]";
    }


    // SPL - Countable functions
    // ----------------------------------------------
    /**
     * Get a count of all the records in the result set
     */
    public function count()
    {
        return count($this->_results);
    }
    // ----------------------------------------------


    // SPL - Iterator functions
    // ----------------------------------------------
    public function current()
    {
        return current($this->_results);
    }

    public function key()
    {
        return key($this->_results);
    }

    public function next()
    {
        next($this->_results);
    }

    public function rewind()
    {
        reset($this->_results);
    }

    public function valid()
    {
        return (current($this->_results) !== FALSE);
    }
    // ----------------------------------------------


    // SPL - ArrayAccess functions
    // ----------------------------------------------
    public function offsetExists($key) {
        return isset($this->_results[$key]);
    }

    public function &offsetGet($key) {
        return $this->_results[$key];
    }

    public function offsetSet($key, $value) {
        if($key === null) {
            return $this->_results[] = $value;
        } else {
            return $this->_results[$key] = $value;
        }
    }

    public function offsetUnset($key) {
        if(is_int($key)) {
            array_splice($this->_results, $key, 1);
        } else {
            unset($this->_results[$key]);
        }
    }
    // ----------------------------------------------
}
