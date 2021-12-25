<?php
namespace SpotTest\Cases\Entity;

use Spot\Entity;

/**
 * Post
 *
 * @package Spot
 */
class Report extends Entity
{
    protected static $table = 'test_reports';

    public static function fields()
    {
        return [
            'id'           => ['type' => 'integer', 'autoincrement' => true, 'primary' => true],
            'date'         => ['type' => 'date', 'value' => new \DateTime(), 'required' => true, 'unique' => true],
            'result'       => ['type' => 'json_array', 'required' => true],
        ];
    }
}
