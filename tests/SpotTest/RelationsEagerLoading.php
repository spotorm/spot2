<?php
namespace SpotTest;

/**
 * @package Spot
 */
class RelationsEagerLoading extends \PHPUnit_Framework_TestCase
{
    private static $entities = ['Post', 'Post\Comment', 'Author', 'Tag', 'PostTag', 'Event', 'Event\Search'];

    public static function setupBeforeClass()
    {
        foreach (self::$entities as $entity) {
            test_spot_mapper('\SpotTest\Entity\\' . $entity)->migrate();
        }

        // Fixtures for this test suite

        // Author
        $authorMapper = test_spot_mapper('SpotTest\Entity\Author');
        $author = $authorMapper->create([
            'email'    => 'test@test.com',
            'password' => 'password',
            'is_admin' => false
        ]);

        // Posts
        $posts = [];
        $postsCount = 3;
        $mapper = test_spot_mapper('SpotTest\Entity\Post');
        for ($i = 1; $i <= $postsCount; $i++) {
            $posts[] = $mapper->create([
                'title'     => "Eager Loading Test Post $i",
                'body'      => "Eager Loading Test Post Content Here $i",
                'author_id' => $author->id
            ]);
        }

        // 3 comments for each post
        foreach ($posts as $post) {
            $comments = [];
            $commentCount = 3;
            $commentMapper = test_spot_mapper('SpotTest\Entity\Post\Comment');
            for ($i = 1; $i <= $commentCount; $i++) {
                $comments[] = $commentMapper->create([
                    'post_id' => $post->id,
                    'name'    => 'Testy McTester',
                    'email'   => 'test@test.com',
                    'body'    => "This is a test comment $i. Yay!"
                ]);
            }
        }

        // Create some tags
        $tags = array();
        $tagCount = 3;
        $tagMapper = test_spot_mapper('SpotTest\Entity\Tag');
        for ($i = 1; $i <= $tagCount; $i++) {
            $tags[] = $tagMapper->create([
                'name' => "Tag {$i}"
            ]);
        }

        // Insert all tags for current post
        $postTagMapper = test_spot_mapper('SpotTest\Entity\PostTag');
        foreach ($posts as $post) {
            foreach ($tags as $tag) {
                $posttag_id = $postTagMapper->create([
                    'post_id' => $post->id,
                    'tag_id'  => $tag->id
                ]);
            }
        }

        // Event
        $eventMapper = test_spot_mapper('SpotTest\Entity\Event');
        $event = $eventMapper->create([
            'title'         => 'Eager Load Test Event',
            'description'   => 'some test eager loading description',
            'type'          => 'free',
            'date_start'    => new \DateTime('+1 second')
        ]);
        $event2 = $eventMapper->create([
            'title'         => 'Eager Load Test Event 2',
            'description'   => 'some test eager loading description 2',
            'type'          => 'free',
            'date_start'    => new \DateTime('+1 second')
        ]);
    }

    public static function tearDownAfterClass()
    {
        foreach (self::$entities as $entity) {
            test_spot_mapper('\SpotTest\Entity\\' . $entity)->dropTable();
        }
    }

    public function testEagerLoadHasMany()
    {
        $mapper = test_spot_mapper('\SpotTest\Entity\Post');

        // Set SQL logger
        $logger = new \Doctrine\DBAL\Logging\DebugStack();
        $mapper->connection()->getConfiguration()->setSQLLogger($logger);

        $startCount = count($logger->queries);

        $posts = $mapper->all()->with('comments');
        foreach ($posts as $post) {
            foreach ($post->comments as $comment) {
                // Do nothing - just had to iterate to execute the queries
                $this->assertEquals($post->id, $comment->post_id);
            }
        }
        $endCount = count($logger->queries);

        // Eager-loaded relation should be only 2 queries
        $this->assertEquals($startCount+2, $endCount);
    }

    public function testEagerLoadHasManyCounts()
    {
        $mapper = test_spot_mapper('\SpotTest\Entity\Post');

        // Set SQL logger
        $logger = new \Doctrine\DBAL\Logging\DebugStack();
        $mapper->connection()->getConfiguration()->setSQLLogger($logger);

        $startCount = count($logger->queries);

        $posts = $mapper->all()->order(['date_created' => 'DESC'])->with(['comments']);
        foreach ($posts as $post) {
            $this->assertEquals(3, count($post->comments));
        }
        $endCount = count($logger->queries);

        // Eager-loaded relation should be only 2 queries
        $this->assertEquals($startCount+2, $endCount);
    }

    public function testEagerLoadBelongsTo()
    {
        $mapper = test_spot_mapper('\SpotTest\Entity\Post');

        // Set SQL logger
        $logger = new \Doctrine\DBAL\Logging\DebugStack();
        $mapper->connection()->getConfiguration()->setSQLLogger($logger);

        $startCount = count($logger->queries);

        $posts = $mapper->all()->with('author');
        foreach ($posts as $post) {
            $this->assertEquals($post->author_id, $post->author->id);
        }
        $endCount = count($logger->queries);

        // Eager-loaded relation should be only 2 queries
        $this->assertEquals($startCount+2, $endCount);
    }

    public function testEagerLoadHasOne()
    {
        $mapper = test_spot_mapper('SpotTest\Entity\Event');

        // Set SQL logger
        $logger = new \Doctrine\DBAL\Logging\DebugStack();
        $mapper->connection()->getConfiguration()->setSQLLogger($logger);

        $startCount = count($logger->queries);

        $events = $mapper->all()->with('search');
        foreach ($events as $event) {
            $this->assertEquals($event->id, $event->search->event_id);
        }
        $endCount = count($logger->queries);

        // Eager-loaded relation should be only 2 queries
        $this->assertEquals($startCount+2, $endCount);
    }

    public function testEagerLoadHasManyThrough()
    {
        $mapper = test_spot_mapper('SpotTest\Entity\Post');

        // Set SQL logger
        $logger = new \Doctrine\DBAL\Logging\DebugStack();
        $mapper->connection()->getConfiguration()->setSQLLogger($logger);

        $startCount = count($logger->queries);

        $posts = $mapper->all()->with('tags');
        foreach ($posts as $post) {
            foreach ($post->tags as $tags) {
                // Do nothing - just had to iterate to execute the queries
            }
            $this->assertEquals(3, count($post->tags));
        }
        $endCount = count($logger->queries);

        // Eager-loaded HasManyThrough relation should be only 3 queries
        // (1 query more than other relations, for the join table)
        $this->assertEquals($startCount+3, $endCount);
    }

    public function testEagerLoadHasManyThroughToArray()
    {
        $mapper = test_spot_mapper('SpotTest\Entity\Post');
        $post = $mapper->all()->with('tags')->first();

        $result = $post->toArray();

        $this->assertTrue(is_array($result['tags']));
    }

    public function testEagerLoadHasManyThroughToArrayShouldNotLoadRelation()
    {
        $mapper = test_spot_mapper('SpotTest\Entity\Post');
        $post = $mapper->all()->first();

        $result = $post->toArray();

        $this->assertFalse(isset($result['tags']));
    }
}
