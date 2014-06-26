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
        foreach(self::$entities as $entity) {
            test_spot_mapper('\SpotTest\Entity\\' . $entity)->migrate();
        }

        // Fixtures for this test suite
        //
        // 'id' is randomized in different groups of ranges to prevent
        // predicatable ids within the same range 1, 2, 3, etc. that can cause
        // subtle errors

        // Author
        $authorMapper = test_spot_mapper('SpotTest\Entity\Author');
        $author = $authorMapper->create([
            'id'       => rand(501, 600),
            'email'    => 'test@test.com',
            'password' => 'password',
            'is_admin' => false
        ]);

        // Posts
        $posts = [];
        $postsCount = 3;
        $startId = rand(601, 700);
        $mapper = test_spot_mapper('SpotTest\Entity\Post');
        for ($i = 1; $i <= $postsCount; $i++) {
            $posts[] = $mapper->create([
                'id'        => ++$startId,
                'title'     => "Eager Loading Test Post $i",
                'body'      => "Eager Loading Test Post Content Here $i",
                'author_id' => $author->id
            ]);
        }

        // 3 comments for each post
        $startId = rand(701, 750);
        foreach($posts as $post) {
            $comments = [];
            $commentCount = 3;
            $commentMapper = test_spot_mapper('SpotTest\Entity\Post\Comment');
            for ($i = 1; $i <= $commentCount; $i++) {
                $comments[] = $commentMapper->create([
                    'id'      => ++$startId,
                    'post_id' => $post->id,
                    'name'    => 'Testy McTester',
                    'email'   => 'test@test.com',
                    'body'    => "This is a test comment $i. Yay!"
                ]);
            }
        }

        // Event
        $eventMapper = test_spot_mapper('SpotTest\Entity\Event');
        $startId = rand(1001, 1100);
        $event = $eventMapper->create([
            'id'            => $startId,
            'title'         => 'Eager Load Test Event',
            'description'   => 'some test eager loading description',
            'type'          => 'free',
            'date_start'    => new \DateTime('+1 second')
        ]);
        $event2 = $eventMapper->create([
            'id'            => ++$startId,
            'title'         => 'Eager Load Test Event 2',
            'description'   => 'some test eager loading description 2',
            'type'          => 'free',
            'date_start'    => new \DateTime('+1 second')
        ]);
    }

    public static function tearDownAfterClass()
    {
        foreach(self::$entities as $entity) {
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
        foreach($posts as $post) {
            foreach($post->comments as $comment) {
                // Do nothing - just had to iterate to execute the queries
            }
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
        foreach($posts as $post) {
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
        foreach($events as $event) {
            $this->assertEquals($event->id, $event->search->event_id);
        }
        $endCount = count($logger->queries);

        // Eager-loaded relation should be only 2 queries
        $this->assertEquals($startCount+2, $endCount);
    }
}
