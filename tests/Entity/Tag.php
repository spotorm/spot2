<?php
namespace SpotTest\Entity;

use Spot\MapperInterface;
use Spot\EntityInterface;

/**
 * Post
 *
 * @package Spot
 */
class Tag extends \Spot\Entity
{
    protected static $table = 'test_tags';

    public static function fields()
    {
        return [
            'id'    => ['type' => 'integer', 'primary' => true, 'autoincrement' => true],
            'name'  => ['type' => 'string', 'required' => true],
        ];
    }

    public static function relations(MapperInterface $mapper, EntityInterface $entity)
    {
        return [
            'posts' => $mapper->hasManyThrough($entity, 'SpotTest\Entity\Post', 'SpotTest\Entity\PostTag', 'tag_id', 'post_id')
        ];
    }
}
