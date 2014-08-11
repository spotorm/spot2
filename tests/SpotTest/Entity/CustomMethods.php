<?php
namespace SpotTest\Entity;

/**
 * CustomMethods
 *
 * @package Spot
 */
class CustomMethods extends \Spot\Entity
{
    protected static $table = 'test_custom_methods';

    public static function fields()
    {
        return [
            'id' => ['type' => 'integer', 'primary' => true, 'autoincrement' => true],
            'test1' => ['type' => 'text'],
            'test2' => ['type' => 'text']
        ];
    }

    // Custom setter
    public function setTest1($value)
    {
        return $value . '_test';
    }

    // Custom getter
    public function getTest1()
    {
        return $this->get('test1') . '_gotten';
    }
}
