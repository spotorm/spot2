<?php
namespace SpotTest\Entity;

use Spot\Entity;
use Spot\MapperInterface;
use Spot\EntityInterface;

/**
 * RecursiveEntity
 *
 * @package Spot
 */
class RecursiveEntity extends Entity
{
    protected static $table = 'test_recursive';

    public static function fields()
    {
        return [
            'id' => [
                'type' => 'integer', 'primary' => true, 'autoincrement' => true,
                'form' => false
            ],
            'priority' => [
                'type' => 'integer', 'index' => true,
                'form' => false,
            ],
            'status' => [
                'type' => 'smallint', 'required' => true, 'default' => 1,
                'options' => [1, 0],
            ],
            'date_publish' => [
                'type' => 'date',
            ],
            'name' => [
                'type' => 'string', 'required' => true, 'label' => true,
                'validation' => ['lengthMax' => 255],
            ],
            'description' => [
                'type' => 'text'
            ],
            'parent_id' => [
                'type' => 'integer', 'index' => true,
            ],
            'sibling_id' => [
                'type' => 'integer', 'index' => true,
            ],
        ];
    }

    public static function relations(MapperInterface $mapper, EntityInterface $entity)
    {
        return [
            'children' => $mapper->hasMany($entity, 'SpotTest\Entity\RecursiveEntity', 'parent_id'),
            'parent' => $mapper->belongsTo($entity, 'SpotTest\Entity\RecursiveEntity', 'parent_id'),
            'my_sibling' => $mapper->belongsTo($entity, 'SpotTest\Entity\RecursiveEntity', 'sibling_id'),
            'sibling' => $mapper->hasOne($entity, 'SpotTest\Entity\RecursiveEntity', 'sibling_id')
        ];
    }
}
