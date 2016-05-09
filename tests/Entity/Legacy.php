<?php
namespace SpotTest\Entity;

use Spot\Entity;
use Spot\EntityInterface;
use Spot\MapperInterface;
use Spot\EventEmitter;

/**
 * Legacy - A legacy database table with custom column mappings
 *
 * @package Spot
 */
class Legacy extends Entity
{
    protected static $table = 'test_legacy';

    public static function fields()
    {
        return [
            'id'           => ['type' => 'integer', 'autoincrement' => true, 'primary' => true, 'column' => self::getIdFieldColumnName()],
            'name'         => ['type' => 'string', 'required' => true, 'column' => self::getNameFieldColumnName()],
            'number'       => ['type' => 'integer', 'required' => true, 'column' => self::getNumberFieldColumnName()],
            'date_created' => ['type' => 'datetime', 'value' => new \DateTime(), 'column' => self::getDateCreatedColumnName()],
        ];
    }

    public static function relations(MapperInterface $mapper, EntityInterface $entity)
    {
        return [
            'polymorphic_comments' => $mapper->hasMany($entity, 'SpotTest\Entity\PolymorphicComment', 'item_id')->where(['item_type' => 'legacy'])
        ];
    }

    /**
     * Helpers for field/column names - methods with public access so we can avoid duplication in tests
     */
    public static function getIdFieldColumnName()
    {
        return 'obnoxiouslyObtuse_IdentityColumn';
    }

    public static function getNameFieldColumnName()
    {
        return 'string_54_LegacyDB_x8';
    }

    public static function getNumberFieldColumnName()
    {
        return 'xbf86_haikusInTheDark';
    }

    public static function getDateCreatedColumnName()
    {
        return 'dtCreatedAt';
    }
}
