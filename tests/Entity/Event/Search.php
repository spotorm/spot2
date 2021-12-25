<?php
namespace SpotTest\Entity\Event;

use Spot\Entity;
use Spot\EntityInterface;
use Spot\MapperInterface;

/**
 * Event Search Index
 *
 * @package Spot
 */
class Search extends Entity
{
    protected static $table = 'test_events_search';

    // MyISAM table for FULLTEXT searching
    protected static $tableOptions = [
        'engine' => 'MyISAM'
    ];

    public static function fields()
    {
        return [
            'id'        => ['type' => 'integer', 'primary' => true, 'autoincrement' => true],
            'event_id'  => ['type' => 'integer', 'index' => true, 'required' => true],
            'body'      => ['type' => 'text', 'required' => true, 'fulltext' => true]
        ];
    }

    public static function relations(MapperInterface $mapper, EntityInterface $entity)
    {
        return [
            'event' => $mapper->belongsTo($entity, 'SpotTest\Entity\Event', 'event_id')
        ];
    }
}
