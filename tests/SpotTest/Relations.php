<?php
namespace SpotTest;

/**
 * @package Spot
 */
class Relations extends \PHPUnit_Framework_TestCase
{
    private static $entities = ['Post', 'Post\Comment', 'Author', 'Tag', 'PostTag', 'Event', 'Event\Search'];

    public static function setupBeforeClass()
    {
        foreach(self::$entities as $entity) {
            test_spot_mapper('\SpotTest\Entity\\' . $entity)->migrate();
        }
    }

    public static function tearDownAfterClass()
    {
        foreach(self::$entities as $entity) {
            test_spot_mapper('\SpotTest\Entity\\' . $entity)->dropTable();
        }
    }

    public function testBlogPostInsert()
    {
        $mapper = test_spot_mapper('\SpotTest\Entity\Post');
        $post = $mapper->get();
        $post->title = "My Awesome Blog Post";
        $post->body = "<p>This is a really awesome super-duper post.</p><p>It's testing the relationship functions.</p>";
        $post->date_created = new \DateTime();
        $post->author_id = 1;
        $postId = $mapper->insert($post);

        $this->assertTrue($postId !== false);

        // Test selcting it to ensure it exists
        $postx = $mapper->get($postId);
        $this->assertTrue($postx instanceof \SpotTest\Entity\Post);

        return $postId;
    }

    /**
     * @depends testBlogPostInsert
     */
    public function testPostCommentsInsert($postId)
    {
        $mapper = test_spot_mapper('\SpotTest\Entity\Post');
        $commentMapper = test_spot_mapper('\SpotTest\Entity\Post\Comment');
        $post = $mapper->get($postId);

        // Array will usually come from POST/JSON data or other source
        $commentSaved = false;
        $comment = $commentMapper->get();
        $comment->data([
            'post_id' => $postId,
            'name' => 'Testy McTester',
            'email' => 'test@test.com',
            'body' => 'This is a test comment. Yay!',
            'date_created' => new \DateTime()
        ]);

        $commentSaved = $commentMapper->save($comment);
        if(!$commentSaved) {
            print_r($comment->errors());
            $this->fail("Comment NOT saved");
        }

        $this->assertTrue(false !== $commentSaved);
    }

    /**
     * @depends testBlogPostInsert
     */
    public function testPostCommentsCanIterate($postId)
    {
        $mapper = test_spot_mapper('\SpotTest\Entity\Post');
        $post = $mapper->get($postId);

        foreach($post->comments as $comment) {
            $this->assertTrue($comment instanceOf \SpotTest\Entity\Post\Comment);
        }
    }

    public function testHasManyRelationCountZero()
    {
        $mapper = test_spot_mapper('\SpotTest\Entity\Post');
        $post = $mapper->get();
        $post->title = "No Comments";
        $post->body = "<p>Comments relation test</p>";
        $mapper->save($post);

        $this->assertSame(0, count($post->comments));
    }

    public function testBlogCommentsIterateEmptySet()
    {
        $mapper = test_spot_mapper('\SpotTest\Entity\Post');
        $post = $mapper->get();
        $post->title = "No Comments";
        $post->body = "<p>Comments relation test</p>";
        $post->author_id = 1;
        $mapper->save($post);

        // Testing that we can iterate over an empty set
        foreach($post->comments as $comment) {
            $this->assertTrue($comment instanceOf \SpotTest\Entity\Post\Comment);
        }
    }

    /**
     * @depends testBlogPostInsert
     */
    public function testRelationsNotInData($postId)
    {
        $mapper = test_spot_mapper('\SpotTest\Entity\Post');
        $post = $mapper->get($postId);
        $this->assertNotContains('comments', array_keys($post->data()));
    }

    /**
     * @depends testBlogPostInsert
     */
    public function testBlogCommentsRelationCountOne($postId)
    {
        $mapper = test_spot_mapper('\SpotTest\Entity\Post');
        $post = $mapper->get($postId);

        $this->assertTrue(count($post->comments) == 1);
    }

    /**
     * @depends testBlogPostInsert
     */
    public function testBlogCommentsRelationCanBeModified($postId)
    {
        $mapper = test_spot_mapper('\SpotTest\Entity\Post');
        $post = $mapper->get($postId);

        $sortedComments = $post->comments->order(['date_created' => 'DESC']);
        $this->assertTrue($sortedComments instanceof \Spot\Query);
    }

    /**
     * @depends testBlogPostInsert
     */
    public function testRelationshipQueryNotReset($postId)
    {
        $mapper = test_spot_mapper('\SpotTest\Entity\Post');
        $post = $mapper->get($postId);

        $before_count = $post->comments->count();
        foreach($post->comments as $comment) {
            $query = $comment->post;
        }

        $this->assertSame($before_count, $post->comments->count());
    }

    /**
     * @depends testBlogPostInsert
     */
    public function testBlogTagsHasManyThrough($postId)
    {
        $mapper = test_spot_mapper('\SpotTest\Entity\Post');
        $post = $mapper->get($postId);
        $this->assertSame(0, count($post->tags));
    }

    /**
     * @depends testBlogPostInsert
     */
    public function testPostTagInsertHasManyThroughCountIsAccurate($postId)
    {
        $mapper = test_spot_mapper('SpotTest\Entity\Post');
        $post = $mapper->get($postId);

        $tagCount = 3;

        // Create some tags
        $tags = array();
        $tagMapper = test_spot_mapper('SpotTest\Entity\Tag');
        for( $i = 1; $i <= $tagCount; $i++ ) {
            $tags[] = $tagMapper->create([
                'name' => "Title {$i}"
            ]);
        }

        // Insert all tags for current post
        $postTagMapper = test_spot_mapper('SpotTest\Entity\PostTag');
        foreach($tags as $tag) {
            $posttag_id = $postTagMapper->create([
                'post_id' => $post->id,
                'tag_id' => $tag->id
            ]);
        }

        $this->assertSame($tagCount, count($post->tags));
        $tagData = [];
        foreach($tags as $tag) {
            $tagData[] = $tag->data();
        }
        $this->assertEquals($tagData, $post->tags->map(function($tag) { return $tag->data(); }));
    }

    public function testEventInsert()
    {
        $mapper = test_spot_mapper('SpotTest\Entity\Event');
        $event = $mapper->get();
        $event->title = "My Awesome Event";
        $event->description = "Some equally awesome event description here.";
        $event->type = 'free';
        $event->date_start = new \DateTime();
        $eventId = $mapper->save($event);

        $this->assertTrue($eventId !== false);

        return $event->id;
    }

    /**
     * @depends testEventInsert
     */
    public function testEventHasOneSearchIndex($eventId)
    {
        $mapper = test_spot_mapper('SpotTest\Entity\Event');
        $event = $mapper->get($eventId);
        $this->assertInstanceOf('SpotTest\Entity\Event\Search', $event->search->execute());
    }

    /**
     * @depends testEventInsert
     */
    public function testEventSearchBelongsToEvent($eventId)
    {
        $mapper = test_spot_mapper('SpotTest\Entity\Event\Search');
        $eventSearch = $mapper->first(['event_id' => $eventId]);
        $this->assertInstanceOf('SpotTest\Entity\Event', $eventSearch->event->execute());
    }
}
