<?php
namespace SpotTest\Entity;

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

    public static function relations()
    {
        return [
            // Each tag entity 'hasManyThrough' post entities
            'posts' => [
                'type' => 'HasManyThrough',
                'entity' => 'SpotTest\Entity\Post',
                'throughEntity' => 'SpotTest\Entity\PostTag',
                'throughWhere' => ['tag_id' => ':entity.id'],
                'where' => ['id' => ':throughEntity.post_id'],
            ]
        ];
    }
}
