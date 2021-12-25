<?php
namespace SpotTest\Entity;

use Spot\Entity;

/**
 * Entity with a mix of default and value definitions in fields
 *
 * @package Spot
 */
class DefaultValue extends \Spot\Entity
{
    protected static $table = 'test_defaultvalue';

    public static function fields()
    {
        return [
            'id'     => ['type' => 'integer', 'autoincrement' => true, 'primary' => true],
            'data1'  => ['type' => 'integer', 'value' => 2],
            'data2'  => ['type' => 'integer', 'default' => 3],
            'data3'  => ['type' => 'integer', 'default' => 4, 'value' => 5],
        ];
    }
}
