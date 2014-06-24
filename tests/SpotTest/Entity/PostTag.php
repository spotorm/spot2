<?php
namespace SpotTest\Entity;

use Spot\Entity;
use Spot\Mapper;

/**
 * PostTag
 *
 * @package Spot
 */
class PostTag extends Entity
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

    public static function relations(Mapper $mapper, Entity $entity)
    {
        return [
            'post' => $mapper->belongsTo($entity, 'SpotTest\Entity\Post', 'post_id'),
            'tag'  => $mapper->belongsTo($entity, 'SpotTest\Entity\Tag', 'tag_id')
        ];
    }
}
