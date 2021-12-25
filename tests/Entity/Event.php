<?php
namespace SpotTest\Entity;

use Spot\EntityInterface;
use Spot\MapperInterface;
use Spot\EventEmitter;

/**
 * Post
 *
 * @package Spot
 */
class Event extends \Spot\Entity
{
    protected static $table = 'test_events';

    // Use a custom mapper for this object
    protected static $mapper = 'SpotTest\Mapper\Event';

    public static function fields()
    {
        return [
            'id' => ['type' => 'integer', 'primary' => true, 'autoincrement' => true],
            'title' => ['type' => 'string', 'required' => true],
            'description' => ['type' => 'text', 'required' => true],
            'type' => ['type' => 'string', 'required' => true, 'options' => [
                'free', // 'Free',
                'private', // 'Private (Ticket Required)'
                'vip' // 'VIPs only'
            ]],
            'token' => ['type' => 'string', 'required' => true],
            'date_start' => ['type' => 'datetime', 'required' => true, 'validation' => [
                'dateAfter' => new \DateTime('-1 second')
            ]],
            'status' => ['type' => 'string', 'default' => 1, 'options' => [
                0, // 'Inactive'
                1, // 'Active'
                2 // 'Archived'
            ]],
            'date_created' => ['type' => 'datetime']
        ];
    }

    public static function relations(MapperInterface $mapper, EntityInterface $entity)
    {
        return [
            'search' => $mapper->hasOne($entity, 'SpotTest\Entity\Event\Search', 'event_id'),
            'polymorphic_comments' => $mapper->hasMany($entity, 'SpotTest\Entity\PolymorphicComment', 'item_id')->where(['item_type' => 'event'])
        ];
    }

    public static function events(EventEmitter $eventEmitter)
    {
        $eventEmitter->on('beforeInsert', function ($entity, $mapper) {
            $entity->token = uniqid();
        });

        $eventEmitter->on('afterInsert', function ($entity, $mapper) {
            $mapper = \test_spot_mapper('SpotTest\Entity\Event\Search');
            $result = $mapper->create([
                'event_id' => $entity->id,
                'body'     => $entity->title . ' ' . $entity->description
            ]);

            if (!$result) {
                throw new \Spot\Exception("Event search index entity failed to save!");
            }
        });
    }
}
