<?php
namespace SpotTest\Entity;

/**
 * Types
 * Exists solely for the purpose of testing custom types
 *
 * @package Spot
 */
class Type extends \Spot\Entity
{
    protected static $_datasource = 'test_types';

    // Declared 'public static' here so they can be modified by tests - this is for TESTING ONLY
    public static $_fields = [
        'id'            => ['type' => 'integer', 'primary' => true, 'serial' => true],
        'serialized'    => ['type' => 'json_array'],
        'date_created'  => ['type' => 'datetime']
    ];

    public static function fields()
    {
        return self::$_fields;
    }
}
