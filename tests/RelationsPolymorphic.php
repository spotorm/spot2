<?php
namespace SpotTest;

/**
 * @package Spot
 */
class RelationsPolymorphic extends \PHPUnit_Framework_TestCase
{
    private static $entities = ['PolymorphicComment', 'Post', 'Author', 'Event\Search', 'Event'];

    public static function setupBeforeClass()
    {
        foreach (self::$entities as $entity) {
            test_spot_mapper('\SpotTest\Entity\\' . $entity)->migrate();
        }

        // Fixtures for this test suite

        // Author
        $authorMapper = test_spot_mapper('SpotTest\Entity\Author');
        $author = $authorMapper->create([
            'email'    => 'chester@tester.com',
            'password' => 'password',
            'is_admin' => true
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

        // 3 polymorphic comments for each post
        $commentMapper = test_spot_mapper('SpotTest\Entity\PolymorphicComment');
        foreach ($posts as $post) {
            $comments = [];
            $commentCount = 3;
            for ($i = 1; $i <= $commentCount; $i++) {
                $comments[] = $commentMapper->create([
                    'item_type' => 'post',
                    'item_id'   => $post->id,
                    'name'      => 'Chester Tester',
                    'email'     => 'chester@tester.com',
                    'body'      => "This is a test POST comment $i. Yay!"
                ]);
            }
        }

        // Event
        $events = [];
        $eventsCount = 3;
        $eventMapper = test_spot_mapper('SpotTest\Entity\Event');
        $events[] = $eventMapper->create([
            'title'         => 'Eager Load Test Event',
            'description'   => 'some test eager loading description',
            'type'          => 'free',
            'date_start'    => new \DateTime('+1 second')
        ]);
        $events[] = $eventMapper->create([
            'title'         => 'Eager Load Test Event 2',
            'description'   => 'some test eager loading description 2',
            'type'          => 'free',
            'date_start'    => new \DateTime('+1 second')
        ]);

        // 3 polymorphic comments for each event
        foreach ($events as $event) {
            $eventComments = [];
            $commentCount = 3;
            for ($i = 1; $i <= $commentCount; $i++) {
                $eventComments[] = $commentMapper->create([
                    'item_type' => 'event',
                    'item_id'   => $event->id,
                    'name'      => 'Chester Tester',
                    'email'     => 'chester@tester.com',
                    'body'      => "This is a test EVENT comment $i. Yay!"
                ]);
            }
        }
    }

    public static function tearDownAfterClass()
    {
        foreach (self::$entities as $entity) {
            test_spot_mapper('\SpotTest\Entity\\' . $entity)->dropTable();
        }
    }

    public function testEventHasManyPolymorphicComments()
    {
        $mapper = test_spot_mapper('SpotTest\Entity\Event');
        $event = $mapper->first();
        $this->assertInstanceOf('SpotTest\Entity\Event', $event);

        $query = $event->polymorphic_comments->query();

        $this->assertEquals(3, count($event->polymorphic_comments));
    }

    public function testPostHasManyPolymorphicComments()
    {
        $mapper = test_spot_mapper('SpotTest\Entity\Post');
        $post = $mapper->first();
        $this->assertInstanceOf('SpotTest\Entity\Post', $post);

        $query = $post->polymorphic_comments->query();

        $this->assertEquals(3, count($post->polymorphic_comments));
    }
}
