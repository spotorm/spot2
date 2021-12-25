<?php
namespace SpotTest\Cases\Entity;

use Spot\Entity;

/**
 * Zipcodes
 *
 * @package Spot
 */
class Zip extends Entity
{
    public static $table = 'zipcodes';

    public static function fields()
    {
        return [
            'id'    => ['type' => 'integer', 'primary' => true, 'autoincrement' => true],
            'code'  => ['type' => 'string', 'required' => true, 'unique' => 'code_city_state'],
            'city'  => ['type' => 'string', 'required' => true, 'unique' => 'code_city_state'],
            'state' => ['type' => 'string', 'required' => true, 'unique' => 'code_city_state'],
            'lat'   => ['type' => 'decimal', 'precision' => '10', 'scale' => 8, 'required' => true, 'index' => 'location'],
            'lng'   => ['type' => 'decimal', 'precision' => '10', 'scale' => 8,  'required' => true, 'index' => 'location'],
        ];
    }
}
