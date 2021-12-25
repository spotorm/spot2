<?php
namespace SpotTest\Cases;

/**
 * @package Spot
 */
class RelationsTest extends \PHPUnit\Framework\TestCase
{
    private static $entities = ['PostTag', 'Post\Comment', 'Post', 'Tag', 'Author', 'Event\Search', 'Event'];

    public static function setupBeforeClass(): void
    {
        foreach (self::$entities as $entity) {
            \test_spot_mapper('\SpotTest\Entity\\' . $entity)->migrate();
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
            \test_spot_mapper('\SpotTest\Entity\\' . $entity)->dropTable();
        }
    }

    public function testBlogPostInsert()
    {
        $mapper = \test_spot_mapper('\SpotTest\Entity\Post');
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
        $mapper = \test_spot_mapper('\SpotTest\Entity\Post');
        $commentMapper = \test_spot_mapper('\SpotTest\Entity\Post\Comment');
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
        if (!$commentSaved) {
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
        $mapper = \test_spot_mapper('\SpotTest\Entity\Post');
        $post = $mapper->get($postId);

        foreach ($post->comments as $comment) {
            $this->assertTrue($comment instanceof \SpotTest\Entity\Post\Comment);
        }
    }

    public function testHasManyRelationCountZero()
    {
        $mapper = \test_spot_mapper('\SpotTest\Entity\Post');
        $post = $mapper->get();
        $post->title = "No Comments";
        $post->body = "<p>Comments relation test</p>";
        $mapper->save($post);

        $this->assertSame(0, count($post->comments));
    }

    public function testBlogCommentsIterateEmptySet()
    {
        $mapper = \test_spot_mapper('\SpotTest\Entity\Post');
        $post = $mapper->get();
        $post->title = "No Comments";
        $post->body = "<p>Comments relation test</p>";
        $post->author_id = 1;
        $mapper->save($post);

        // Testing that we can iterate over an empty set
        foreach ($post->comments as $comment) {
            $this->assertTrue($comment instanceof \SpotTest\Entity\Post\Comment);
        }
    }

    /**
     * @depends testBlogPostInsert
     */
    public function testRelationsNotInData($postId)
    {
        $mapper = \test_spot_mapper('\SpotTest\Entity\Post');
        $post = $mapper->get($postId);
        $this->assertNotContains('comments', array_keys($post->data()));
    }

    /**
     * @depends testBlogPostInsert
     */
    public function testBlogCommentsRelationCountOne($postId)
    {
        $mapper = \test_spot_mapper('\SpotTest\Entity\Post');
        $post = $mapper->get($postId);

        $this->assertTrue(count($post->comments) == 1);
    }

    /**
     * @depends testBlogPostInsert
     */
    public function testBlogCommentsRelationCanBeModified($postId)
    {
        $mapper = \test_spot_mapper('\SpotTest\Entity\Post');
        $post = $mapper->get($postId);

        $sortedComments = $post->comments->order(['date_created' => 'DESC']);
        $this->assertInstanceOf('Spot\Relation\HasMany', $sortedComments);

        $this->assertContains("ORDER BY", $sortedComments->query()->toSql());
    }

    /**
     * @depends testBlogPostInsert
     */
    public function testRelationshipQueryNotReset($postId)
    {
        $mapper = \test_spot_mapper('\SpotTest\Entity\Post');
        $post = $mapper->get($postId);

        $before_count = $post->comments->count();
        foreach ($post->comments as $comment) {
            $query = $comment->post;
        }

        $this->assertSame($before_count, $post->comments->count());
    }

    /**
     * @depends testBlogPostInsert
     */
    public function testBlogTagsHasManyThrough($postId)
    {
        $mapper = \test_spot_mapper('\SpotTest\Entity\Post');
        $post = $mapper->get($postId);
        $this->assertSame(0, count($post->tags));
    }

    /**
     * @depends testBlogPostInsert
     */
    public function testPostTagInsertHasManyThroughCountIsAccurate($postId)
    {
        $mapper = \test_spot_mapper('\SpotTest\Entity\Post');
        $post = $mapper->get($postId);

        $tagCount = 3;

        // Create some tags
        $tags = array();
        $tagMapper = \test_spot_mapper('SpotTest\Entity\Tag');
        for ($i = 1; $i <= $tagCount; $i++) {
            $tags[] = $tagMapper->create([
                'name'  => "Title {$i}"
            ]);
        }

        // Insert all tags for current post
        $postTagMapper = \test_spot_mapper('SpotTest\Entity\PostTag');
        foreach ($tags as $tag) {
            $posttag_id = $postTagMapper->create([
                'post_id' => $post->id,
                'tag_id' => $tag->id
            ]);
        }

        $this->assertSame($tagCount, count($post->tags));
        $tagData = [];
        foreach ($tags as $tag) {
            $tagData[] = $tag->data();
        }
        $this->assertEquals($tagData, $post->tags->map(function ($tag) { return $tag->data(); }));
    }

    public function testEventInsert()
    {
        $mapper = \test_spot_mapper('SpotTest\Entity\Event');
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
        $mapper = \test_spot_mapper('SpotTest\Entity\Event');
        $event = $mapper->get($eventId);
        $eventSearch = $event->search->execute();
        $this->assertInstanceOf('SpotTest\Entity\Event\Search', $eventSearch);
        $this->assertEquals($eventSearch->event_id, $eventId);
    }

    /**
     * @depends testEventInsert
     */
    public function testEventSearchBelongsToEvent($eventId)
    {
        $mapper = \test_spot_mapper('SpotTest\Entity\Event\Search');
        $eventSearch = $mapper->first(['event_id' => $eventId]);
        $event = $eventSearch->event->execute();
        $this->assertInstanceOf('SpotTest\Entity\Event', $event);
        $this->assertEquals($event->id, $eventId);
    }

    /**
     * @depends testEventInsert
     */
    public function testEventSearchEntityAccessibleWithEntityMethod($eventId)
    {
        $mapper = \test_spot_mapper('SpotTest\Entity\Event\Search');
        $eventSearch = $mapper->first(['event_id' => $eventId]);
        $event = $eventSearch->event->entity();
        $this->assertInstanceOf('SpotTest\Entity\Event', $event);
        $this->assertEquals($event->id, $eventId);
    }

    /**
     * @depends testEventInsert
     */
    public function testEventSearchEntityMethodCalledOnEntityDoesNotError($eventId)
    {
        $mapper = \test_spot_mapper('SpotTest\Entity\Event\Search');
        $eventSearch = $mapper->first(['event_id' => $eventId]);
        $event = $eventSearch->event->entity()->entity();
        $this->assertInstanceOf('SpotTest\Entity\Event', $event);
        $this->assertEquals($event->id, $eventId);
    }

    public function testInvalidRelationClass()
    {
        $this->expectException(InvalidArgumentException::class);

        $mapper = \test_spot_mapper('\SpotTest\Entity\Post');
        $entity = $mapper->first();
        $entity->fake = $mapper->hasOne($entity, 'Nonexistent\Entity', 'fake_field');

        $entity->fake->something;
    }

    public function testAccessingRelationObjectProperty()
    {
        $email = 'test@test.com';
        $postMapper = \test_spot_mapper('\SpotTest\Entity\Post');
        $authorMapper = \test_spot_mapper('SpotTest\Entity\Author');

        $author = $authorMapper->create([
            'id' => 2,
            'email'    => $email,
            'password' => 'password',
            'is_admin' => false,
        ]);
        $post = $postMapper->create([
            'title'     => "Testing Property Access",
            'body'      => "I hope array access is set correctly",
            'author_id' => $author->id,
        ]);

        $this->assertEquals($post->author['email'], $email);
    }

    public function testLazyLoadRelationIsset()
    {
        $postMapper = \test_spot_mapper('\SpotTest\Entity\Post');
        $authorMapper = \test_spot_mapper('SpotTest\Entity\Author');

        $author = $authorMapper->create([
            'id' => 3,
            'email'    => 'test1@test.com',
            'password' => 'password',
            'is_admin' => false,
        ]);
        $post = $postMapper->create([
            'title'     => "Testing Property Access",
            'body'      => "I hope array access is set correctly",
            'author_id' => $author->id,
        ]);

        $this->assertTrue(isset($post->author));
    }
}
