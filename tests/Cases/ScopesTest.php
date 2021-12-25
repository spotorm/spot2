<?php
namespace SpotTest\Cases;

/**
 * @package Spot
 */
class ScopesTest extends \PHPUnit\Framework\TestCase
{
    private static $entities = ['Post\Comment', 'Post', 'Event\Search', 'Event', 'Author'];

    public static function setupBeforeClass(): void
    {
        foreach (self::$entities as $entity) {
            \test_spot_mapper('\SpotTest\Cases\Entity\\' . $entity)->migrate();
        }

        $authorMapper = \test_spot_mapper('SpotTest\Entity\Author');
        $author = $authorMapper->build([
            'id' => 1,
            'email' => 'example@example.com',
            'password' => 't00r',
            'is_admin' => false
        ]);
        $result = $authorMapper->insert($author);

        if (!$result) {
            throw new \Exception("Unable to create author: " . var_export($author->data(), true));
        }
    }

    public static function tearDownAfterClass(): void
    {
        foreach (self::$entities as $entity) {
            \test_spot_mapper('\SpotTest\Cases\Entity\\' . $entity)->dropTable();
        }
    }

    public function testSingleScopes()
    {
        $mapper = \test_spot_mapper('SpotTest\Entity\Event');
        $query = $mapper->all()->noQuote()->active();
        $this->assertEquals("SELECT * FROM test_events WHERE test_events.status = ?", $query->toSql());
    }

    public function testMultipleScopes()
    {
        $mapper = \test_spot_mapper('SpotTest\Entity\Event');
        $query = $mapper->select()->noQuote()->free()->active();
        $this->assertEquals("SELECT * FROM test_events WHERE (test_events.type = ?) AND (test_events.status = ?)", $query->toSql());
    }

    public function testEntityScopes()
    {
        $mapper = \test_spot_mapper('SpotTest\Entity\Post');
        $query = $mapper->select()->noQuote()->active();
        $this->assertEquals("SELECT * FROM test_posts WHERE test_posts.status = ?", $query->toSql());
    }

    public function testRelationScopes()
    {
        $mapper = \test_spot_mapper('SpotTest\Entity\Post');
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
