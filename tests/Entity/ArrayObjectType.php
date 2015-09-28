<?php

namespace SpotTest\Entity;

/**
 * ArrayObjectType
 * An entity using all built-in array and object field types
 *
 * @package Spot
 */
class ArrayObjectType extends \Spot\Entity
{

    protected static $table = 'test_array_object';

    public static function fields()
    {
        return [
            'id' => ['type' => 'integer', 'primary' => true, 'autoincrement' => true],
            'fld_array' => ['type' => 'array', 'value' => []],
            'fld_simple_array' => ['type' => 'simple_array', 'value' => []],
            'fld_json_array' => ['type' => 'json_array', 'value' => []],
            'fld_object' => ['type' => 'object', 'value' => new \stdClass()],
        ];
    }

}
