<?php
namespace SpotTest\Entity\Post;

use DateTime;
use Spot\Entity;
use Spot\EntityInterface;
use Spot\MapperInterface;
use Spot\Query;

/**
 * Post Comment
 *
 * @package Spot
 */
class UserComment extends Entity
{
    protected static $table = 'test_post_user_comments';

    public static function fields()
    {
        return [
            'id'            => ['type' => 'integer', 'primary' => true, 'autoincrement' => true],
            'post_id'       => ['type' => 'integer', 'index' => true, 'required' => true],
            'user_id'       => ['type' => 'integer', 'index' => true, 'required' => true],
            'body'          => ['type' => 'text', 'required' => true],
            'date_created'  => ['type' => 'datetime']
        ];
    }

    public static function relations(MapperInterface $mapper, EntityInterface $entity)
    {
        return [
            'post' => $mapper->belongsTo($entity, 'SpotTest\Entity\Post', 'post_id'),
            'user' => $mapper->belongsTo($entity, 'SpotTest\Entity\User', 'user_id')
        ];
    }
}
