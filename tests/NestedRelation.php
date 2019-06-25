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

        // 1 Author
        $authorMapper = test_spot_mapper('SpotTest\Entity\Author');
        $author = $authorMapper->create([
            'id'       => 123,
            'email'    => 'test123@test.com',
            'password' => 'password123',
            'is_admin' => false
        ]);

        // 4 Users
        $users = [];
        $usersCount = 4;
        $userMapper = test_spot_mapper('SpotTest\Entity\User');
        for ($i = 1; $i <= $usersCount; $i++) {
            $users[] = $userMapper->create([
                'email'    => "test_$i@test.com",
                'password' => 'password'
            ]);
        }

        // 10 Posts
        $posts = [];
        $postsCount = 10;
        $mapper = test_spot_mapper('SpotTest\Entity\Post');
        for ($i = 1; $i <= $postsCount; $i++) {
            $posts[] = $mapper->create([
                'title'     => "Eager Loading Test Post $i",
                'body'      => "Eager Loading Test Post Content Here $i",
                'author_id' => $author->id
            ]);
        }

        // 10 comments for each post
        foreach ($posts as $post) {
            $comments = [];
            $commentCount = 10;
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

    /**
     * @dataProvider testEagerLoadDataProvider()
     */
    public function testEagerLoad($relations, $expectedTotalQueries)
    {
        $mapper = test_spot_mapper('\SpotTest\Entity\Post');

        // Set SQL logger
        $logger = new \Doctrine\DBAL\Logging\DebugStack();
        $mapper->connection()->getConfiguration()->setSQLLogger($logger);

        $startCount = count($logger->queries);

        $posts = $mapper->all()->with($relations);
        foreach ($posts as $post) {
            foreach ($post->user_comments as $comment) {
                $user = $comment->user;
                // Lets check that we have the correct user for each comment
                $this->assertEquals($user->id, $comment->user_id);
            }
        }
        $endCount = count($logger->queries);

        // Eager-loaded relation should be only 2 queries
        $this->assertEquals($startCount+$expectedTotalQueries, $endCount);
    }

    public function testEagerLoadDataProvider()
    {
        return [
            [
                ['user_comments'], 102
            ],
            [
                ['user_comments', 'user_comments.user'], 3
            ]
        ];
    }

    /**
     * @dataProvider testWrongRelationNamesDataProvider()
     *
     * @expectedException \InvalidArgumentException
     */
    public function testNestedRelationBadRelationNames($relations)
    {
        $mapper = test_spot_mapper('\SpotTest\Entity\Post');


        $posts = $mapper->all()->with($relations);
        foreach ($posts as $post) {
            $post->id;
        }
    }

    public function testWrongRelationNamesDataProvider()
    {
        return [
                [['user_comments', 'user_commentsss.user']],
                [['user_comments.user']],
                [['user_comments', 'user_comments.userrr']],

        ];
    }

}
