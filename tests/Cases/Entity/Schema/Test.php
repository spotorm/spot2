<?php
namespace SpotTest\Cases\Entity\Schema;

use Spot\Entity;
use Spot\EntityInterface;
use Spot\MapperInterface;
use Spot\EventEmitter;

/**
 * Post
 *
 * @package Spot
 */
class Test extends Entity
{
    protected static $table = 'spot_test.test_schema_test';

    public static function fields()
    {
        return [
            'id'           => ['type' => 'integer', 'autoincrement' => true, 'primary' => true],
            'unique'       => ['type' => 'integer', 'default' => 0, 'unique' => true],
            'index'        => ['type' => 'integer', 'default' => 0, 'index' => true]
        ];
    }
}
