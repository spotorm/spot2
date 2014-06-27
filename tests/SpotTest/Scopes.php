<?php
namespace SpotTest;

/**
 * @package Spot
 */
class Scopes extends \PHPUnit_Framework_TestCase
{
    public static function setupBeforeClass()
    {
        foreach(['Post', 'Post\Comment', 'Event', 'Event\Search', 'Author'] as $entity) {
            test_spot_mapper('\SpotTest\Entity\\' . $entity)->migrate();
        }
    }

    public static function tearDownAfterClass()
    {
        foreach(['Post', 'Post\Comment', 'Event', 'Event\Search', 'Author'] as $entity) {
            test_spot_mapper('\SpotTest\Entity\\' . $entity)->dropTable();
        }
    }

    public function testSingleScopes()
    {
        $mapper = test_spot_mapper('SpotTest\Entity\Event');
        $query = $mapper->all()->noQuote()->active();
        $this->assertEquals("SELECT * FROM test_events test_events WHERE test_events.status = ?", $query->toSql());
    }

    public function testMultipleScopes()
    {
        $mapper = test_spot_mapper('SpotTest\Entity\Event');
        $query = $mapper->select()->noQuote()->free()->active();
        $this->assertEquals("SELECT * FROM test_events test_events WHERE (test_events.type = ?) AND (test_events.status = ?)", $query->toSql());
    }
}
