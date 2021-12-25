<?php
namespace SpotTest\Entity;

use Spot\Entity;

/**
 * Entity with a mix of default and value definitions in fields
 *
 * @package Spot
 */
class MultipleUniques extends \Spot\Entity
{
    protected static $table = 'test_multipleuniques';

    public static function fields()
    {
        return [
            'id'     => ['type' => 'integer', 'autoincrement' => true, 'primary' => true],
            'data1'  => ['type' => 'string', 'required' => true, 'unique' => ['uniq1', 'uniq2']],
            'data2'  => ['type' => 'integer', 'required' => true, 'unique' => 'uniq1'],
            'data3'  => ['type' => 'string', 'required' => true, 'unique' => ['uniq2']],
        ];
    }
}