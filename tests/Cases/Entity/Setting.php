<?php
namespace SpotTest\Cases\Entity;

/**
 * Setting
 *
 * @package Spot
 */
class Setting extends \Spot\Entity
{
    protected static $table = 'test_settings';

    public static function fields()
    {
        return [
            'id'     => ['type' => 'integer', 'primary' => true, 'autoincrement' => true],
            'skey'   => ['type' => 'string', 'required' => true, 'unique' => true],
            'svalue' => ['type' => 'encrypted',  'required' => true]
        ];
    }
}

// Add encrypted type
\SpotTest\Cases\Type\Encrypted::$key = 'SOUPER-SEEKRET1!';
\Doctrine\DBAL\Types\Type::addType('encrypted', 'SpotTest\Type\Encrypted');
