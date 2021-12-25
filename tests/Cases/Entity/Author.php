<?php
namespace SpotTest\Cases\Entity;

/**
 * Author
 *
 * @package Spot
 */
class Author extends \Spot\Entity
{
    protected static $table = 'test_authors';

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
            'is_admin' => ['type' => 'boolean', 'value' => false],
            'date_created' => ['type' => 'datetime', 'value' => new \DateTime()]
        ];
    }
}
