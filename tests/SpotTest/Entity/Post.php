<?php
namespace SpotTest\Entity;

/**
 * Post
 *
 * @package Spot
 */
class Post extends \Spot\Entity
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

    public static function relations()
    {
        return [
            // Each post entity 'hasMany' comment entites
            'comments' => [
                'type' => 'HasMany',
                'entity' => 'SpotTest\Entity\Post\Comment',
                'where' => ['post_id' => ':entity.id'],
                'order' => ['date_created' => 'ASC']
            ],
            // Each post entity 'hasManyThrough' tag entities
            'tags' => [
                'type' => 'HasManyThrough',
                'entity' => 'SpotTest\Entity\Tag',
                'throughEntity' => 'SpotTest\Entity\PostTag',
                'throughWhere' => ['post_id' => ':entity.id'],
                'where' => ['id' => ':throughEntity.tag_id'],
            ],
            // Each post entity 'hasOne' author entites
            'author' => [
                'type' => 'HasOne',
                'entity' => 'SpotTest\Entity\Author',
                'where' => ['id' => ':entity.author_id']
            ],
        ];
    }

    public function comments()
    {
        return $this->hasMany('SpotTest\Entity\Post\Comment', 'post_id')->order(['date_created' => 'ASC']);
    }

    public static function events(\Spot\EventEmitter $eventEmitter)
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
