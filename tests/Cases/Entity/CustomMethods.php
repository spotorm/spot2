<?php
namespace SpotTest\Cases\Entity;

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
            'test2' => ['type' => 'text'],
            'test3' => ['type' => 'text']
        ];
    }

    // Custom setter
    public function setTest1($value)
    {
        return $value . '_test';
    }

    public function setTest2($value)
    {
        $this->test3 = $value . '_copy';
        return $value;
    }

    // Custom getter
    public function getTest1()
    {
        return $this->get('test1') . '_gotten';
    }
}
