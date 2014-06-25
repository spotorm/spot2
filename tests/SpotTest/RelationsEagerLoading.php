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
        $posts = [];
        $postsCount = 3;
        $mapper = test_spot_mapper('SpotTest\Entity\Post');
        for ($i = 1; $i <= $postsCount; $i++) {
            $posts[] = $mapper->create([
                'title'         => "Eager Loading Test Post $i",
                'body'          => "Eager Loading Test Post Content Here $i",
                'date_created'  => new \DateTime(),
                'author_id'     => 1
            ]);
        }

        // 3 comments for each post
        foreach($posts as $post) {
            $comments = [];
            $commentCount = 3;
            $commentMapper = test_spot_mapper('SpotTest\Entity\Post\Comment');
            for ($i = 1; $i <= $commentCount; $i++) {
                $comments[] = $commentMapper->create([
                    'post_id' => $post->id,
                    'name' => 'Testy McTester',
                    'email' => 'test@test.com',
                    'body' => "This is a test comment $i. Yay!",
                    'date_created' => new \DateTime()
                ]);
            }
        }
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
}
