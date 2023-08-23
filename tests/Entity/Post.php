<?php
namespace SpotTest\Entity;

use Spot\Entity;
use Spot\EntityInterface;
use Spot\MapperInterface;
use Spot\EventEmitter;
use Spot\Query;
use SpotTest\Entity\Post\UserComment;

/**
 * Post
 *
 * @package Spot
 */
class Post extends Entity
{
    protected static $table = 'test_posts';

    // For testing purposes only
    public static $events = [];

    public static function fields()
    {
        return [
            'id'           => ['type' => 'integer', 'autoincrement' => true, 'primary' => true],
            'title'        => ['type' => 'string', 'required' => true],
            'body'         => ['type' => 'text', 'required' => true],
            'status'       => ['type' => 'integer', 'default' => 0, 'index' => true],
            'date_created' => ['type' => 'datetime', 'value' => new \DateTime()],
            'data'         => ['type' => 'json_array'],
            'author_id'    => ['type' => 'integer', 'required' => true],
        ];
    }

    public static function relations(MapperInterface $mapper, EntityInterface $entity)
    {
        return [
            'tags' => $mapper->hasManyThrough($entity, 'SpotTest\Entity\Tag', 'SpotTest\Entity\PostTag', 'tag_id', 'post_id'),
            'comments' => $mapper->hasMany($entity, 'SpotTest\Entity\Post\Comment', 'post_id')->order(['date_created' => 'ASC']),
            'polymorphic_comments' => $mapper->hasMany($entity, 'SpotTest\Entity\PolymorphicComment', 'item_id')->where(['item_type' => 'post']),
            'user_comments' => $mapper->hasMany($entity, 'SpotTest\Entity\Post\UserComment', 'post_id'),
            'author' => $mapper->belongsTo($entity, 'SpotTest\Entity\Author', 'author_id')
        ];
    }

    public static function events(EventEmitter $eventEmitter)
    {
        // This is done only to allow events to be set dynamically in a very
        // specific way for testing purposes. You probably don't want to do
        // this in your code...
        foreach (static::$events as $eventName => $methods) {
            $eventEmitter->on($eventName, function ($entity, $mapper) use ($methods) {
                foreach ($methods as $method) {
                    $entity->$method();
                }
            });
        }
    }

    public static function scopes()
    {
        return [
            'active' => function (Query $query) {
                return $query->where(['status' => 1]);
            }
        ];
    }

    public function mock_save_hook()
    {
        $this->status++;
    }
}
