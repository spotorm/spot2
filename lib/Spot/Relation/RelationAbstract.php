<?php
namespace Spot\Relation;

use Spot\Query;
use Spot\Entity;

/**
 * Abstract class for relations
 *
 * @package Spot
 */
abstract class RelationAbstract
{
    protected $query;

    /**
     * Constructor function
     *
     * @param object $query Spot\Query object to query on for relationship data
     */
    public function __construct(Query $query)
    {
        $this->query = $query;
    }

    /**
     * Get query object instance
     */
    public function query()
    {
        return $this->query;
    }

    /**
     * Execute query and return results
     */
    public function execute()
    {
        return $this->query()->execute();
    }

    /**
     * Passthrough for missing methods on expected object result
     */
    public function __call($func, $args)
    {
        $obj = $this->execute();
        if(is_object($obj)) {
            return call_user_func_array(array($obj, $func), $args);
        } else {
            return $obj;
        }
    }
}
