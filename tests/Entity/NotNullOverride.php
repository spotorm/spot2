<?php
namespace SpotTest\Entity;

use Spot\Entity;

/**
 * Entity with no serial/autoincrement
 *
 * @package Spot
 */
class NotNullOverride extends \Spot\Entity
{
    protected static $table = 'test_notnulloverride';

    public static function fields()
    {
        return [
            'id'     => ['type' => 'integer', 'primary' => true],
            'data1'  => ['type' => 'string', 'required' => true],
            'data2'  => ['type' => 'string', 'required' => true, 'notnull' => true],
            'data3'  => ['type' => 'string', 'required' => true, 'notnull' => false],
        ];
    }
}
