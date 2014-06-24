<?php
namespace Spot\Relation;

/**
 * Relation for single entity
 *
 * Only used so that the query can be lazy-loaded on demand
 *
 * @package Spot
 */
class Single extends RelationAbstract implements \ArrayAccess
{
    /**
     * Find first entity in the set
     *
     * @return \Spot\Entity
     */
    public function execute()
    {
        return $this->query()->first();
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
