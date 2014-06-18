<?php
namespace SpotTest\Entity;

/**
 * PostTag
 *
 * @package Spot
 */
class PostTag extends \Spot\Entity
{
    protected static $table = 'test_posttags';

    public static function fields()
    {
        return [
            'id'        => ['type' => 'integer', 'primary' => true, 'autoincrement' => true],
            'tag_id'    => ['type' => 'integer', 'required' => true, 'unique' => 'post_tag'],
            'post_id'   => ['type' => 'integer', 'required' => true, 'unique' => 'post_tag'],
            'random'    => ['type' => 'string'] // Totally unnecessary, but makes testing upserts easy
        ];
    }

    public static function relations()
    {
        return [
            // Each post tag entity 'HasOne' post and tag entity
            'post' => [
                'type' => 'HasOne',
                'entity' => 'SpotTest\Entity\Post',
                'where' => ['post_id' => ':entity.id'],
            ],
            'tag' => [
                'type' => 'HasOne',
                'entity' => 'SpotTest\Entity\Tag',
                'where' => ['tag_id' => ':entity.id'],
            ]
        ];
    }
}
