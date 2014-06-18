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
    public static $hooks = [];

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

    public static function hooks()
    {
        return static::$hooks;
    }

    public function mock_save_hook()
    {
        $this->status++;
    }
}
