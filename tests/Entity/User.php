<?php
namespace SpotTest\Entity;

/**
 * Author
 *
 * @package Spot
 */
class User extends \Spot\Entity
{
    protected static $table = 'test_users';

    public static function fields()
    {
        return [
            'id' => ['type' => 'integer', 'primary' => true, 'autoincrement' => true],
            'email' => ['type' => 'string', 'required' => true, 'unique' => true,
                'validation' => [
                    'email',
                    'length' => [4, 255]
                ]
            ], // Unique
            'password' => ['type' => 'text', 'required' => true],
            'date_created' => ['type' => 'datetime', 'value' => new \DateTime()]
        ];
    }
}
