<?php
namespace SpotTest;

/**
 * @package Spot
 */
class NestedRelation extends \PHPUnit_Framework_TestCase
{
    private static $entities = ['Post\UserComment', 'Post', 'Author', 'User'];

    public static function setupBeforeClass()
    {
        foreach (self::$entities as $entity) {
            test_spot_mapper('\SpotTest\Entity\\' . $entity)->migrate();
        }

        // Fixtures for this test suite

        // Author
        $authorMapper = test_spot_mapper('SpotTest\Entity\Author');
        $author = $authorMapper->create([
            'id'       => 123,
            'email'    => 'test123@test.com',
            'password' => 'password123',
            'is_admin' => false
        ]);

        // User
        $users = [];
        $usersCount = 2;
        $userMapper = test_spot_mapper('SpotTest\Entity\User');
        for ($i = 1; $i <= $usersCount; $i++) {
            $users[] = $userMapper->create([
                'email'    => "test_$i@test.com",
                'password' => 'password'
            ]);
        }

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
            $commentMapper = test_spot_mapper('SpotTest\Entity\Post\UserComment');
            for ($i = 1; $i <= $commentCount; $i++) {
                $comments[] = $commentMapper->create([
                    'post_id' => $post->id,
                    'user_id' => $i % $usersCount + 1,
                    'body'    => "This is a test comment $i. Yay!"
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

    public function testEagerLoad()
    {
        $mapper = test_spot_mapper('\SpotTest\Entity\Post');

        // Set SQL logger
        $logger = new \Doctrine\DBAL\Logging\DebugStack();
        $mapper->connection()->getConfiguration()->setSQLLogger($logger);

        $startCount = count($logger->queries);

        $posts = $mapper->all()->with('user_comments', 'user_comments.user');
        foreach ($posts as $post) {
            foreach ($post->user_comments as $comment) {
                $user = $comment->user;
                $user->id;
                // Do nothing - just had to iterate to execute the queries
                $this->assertEquals($post->id, $comment->post_id);
            }
        }
        $endCount = count($logger->queries);

        // Eager-loaded relation should be only 2 queries
        $this->assertEquals($startCount+2, $endCount);
    }
}
