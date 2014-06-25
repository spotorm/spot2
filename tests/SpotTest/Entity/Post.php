<?php
namespace SpotTest\Entity;

use Spot\Entity;
use Spot\Mapper;
use Spot\EventEmitter;

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

    public static function relations(Mapper $mapper, Entity $entity)
    {
        return [
            'tags' => $mapper->hasManyThrough($entity, 'SpotTest\Entity\Tag', 'SpotTest\Entity\PostTag', 'tag_id', 'post_id'),
            'comments' => $mapper->hasMany($entity, 'SpotTest\Entity\Post\Comment', 'post_id')->query(function($query) {
                return $query->order(['date_created' => 'ASC']);
            }),
            'author' => $mapper->belongsTo($entity, 'SpotTest\Entity\Author', 'author_id')
        ];
    }

    public static function events(EventEmitter $eventEmitter)
    {
        // This is done only to allow events to be set dynamically in a very
        // specific way for testing purposes. You probably don't want to do
        // this in your code...
        foreach(static::$events as $eventName => $methods) {
            $eventEmitter->on($eventName, function($entity, $mapper) use($methods) {
                foreach($methods as $method) {
                    $entity->$method();
                }
            });
        }
    }

    public function mock_save_hook()
    {
        $this->status++;
    }
}
