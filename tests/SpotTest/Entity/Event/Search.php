<?php
namespace SpotTest\Entity\Event;

/**
 * Event Search Index
 *
 * @package Spot
 */
class Search extends \Spot\Entity
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

    public static function relations() {
      return [
          // Reference back to event
          'event' => [
              'type'   => 'HasOne',
              'entity' => 'SpotTest\Entity\Event',
              'where'  => ['id' => ':entity.event_id']
          ]
      ];
    }
}
