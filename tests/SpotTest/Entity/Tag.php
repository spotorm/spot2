<?php
namespace SpotTest\Entity;

use Spot\Entity;
use Spot\Mapper;

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

    public static function relations(Mapper $mapper, Entity $entity)
    {
        return [
            'posts' => $mapper->hasManyThrough($entity, 'SpotTest\Entity\Post', 'SpotTest\Entity\PostTag', 'tag_id', 'post_id')
        ];
    }
}
