<?php
namespace SpotTest\Entity;

use Spot\Entity;
use Spot\Mapper;

/**
 * Entity with no serial/autoincrement
 *
 * @package Spot
 */
class NoSerial extends \Spot\Entity
{
    protected static $table = 'test_noserial';

    public static function fields()
    {
        return [
            'id'    => ['type' => 'integer', 'primary' => true],
            'data'  => ['type' => 'string', 'required' => true],
        ];
    }
}
