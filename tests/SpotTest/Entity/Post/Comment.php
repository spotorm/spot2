<?php
namespace SpotTest\Entity\Post;

use Spot\MapperInterface;
use Spot\EntityInterface;

/**
 * Post Comment
 *
 * @package Spot
 */
class Comment extends \Spot\Entity
{
    protected static $table = 'test_post_comments';

    public static function fields()
    {
        return [
            'id'            => ['type' => 'integer', 'primary' => true, 'autoincrement' => true],
            'post_id'       => ['type' => 'integer', 'index' => true, 'required' => true],
            'name'          => ['type' => 'string', 'required' => true],
            'email'         => ['type' => 'string', 'required' => true],
            'body'          => ['type' => 'text', 'required' => true],
            'date_created'  => ['type' => 'datetime']
        ];
    }

    public static function relations(MapperInterface $mapper, EntityInterface $entity)
    {
        return [
            'post' => $mapper->belongsTo($entity, 'SpotTest\Entity\Post', 'post_id')
        ];
    }
}
