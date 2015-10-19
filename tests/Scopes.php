<?php
namespace SpotTest;

/**
 * @package Spot
 */
class Scopes extends \PHPUnit_Framework_TestCase
{
    public static function setupBeforeClass()
    {
        foreach (['Post', 'Post\Comment', 'Event', 'Event\Search', 'Author'] as $entity) {
            test_spot_mapper('\SpotTest\Entity\\' . $entity)->migrate();
        }
    }

    public static function tearDownAfterClass()
    {
        foreach (['Post', 'Post\Comment', 'Event', 'Event\Search', 'Author'] as $entity) {
            test_spot_mapper('\SpotTest\Entity\\' . $entity)->dropTable();
        }
    }

    public function testSingleScopes()
    {
        $mapper = test_spot_mapper('SpotTest\Entity\Event');
        $query = $mapper->all()->noQuote()->active();
        $this->assertEquals("SELECT * FROM test_events  WHERE test_events.status = ?", $query->toSql());
    }

    public function testMultipleScopes()
    {
        $mapper = test_spot_mapper('SpotTest\Entity\Event');
        $query = $mapper->select()->noQuote()->free()->active();
        $this->assertEquals("SELECT * FROM test_events  WHERE (test_events.type = ?) AND (test_events.status = ?)", $query->toSql());
    }

    public function testEntityScopes()
    {
        $mapper = test_spot_mapper('SpotTest\Entity\Post');
        $query = $mapper->select()->noQuote()->active();
        $this->assertEquals("SELECT * FROM test_posts  WHERE test_posts.status = ?", $query->toSql());
    }

    public function testRelationScopes()
    {
        $mapper = test_spot_mapper('SpotTest\Entity\Post');
        $mapper->insert([
            'title' => 'Test',
            'body' => 'Test body',
            'author_id' => 1,
            ]);
        $query = $mapper->get(1)->comments->yesterday()->query();
        $sql = str_replace(['`', '"'], '', $query->toSql());
        $this->assertEquals("SELECT * FROM test_post_comments WHERE (test_post_comments.post_id = ?) AND (test_post_comments.date_created > ? AND test_post_comments.date_created < ?) ORDER BY test_post_comments.date_created ASC", $sql);
    }
}
