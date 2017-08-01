<?php
namespace SpotTest;

/**
 * @package Spot
 */
class CRUD extends \PHPUnit_Framework_TestCase
{
    private static $entities = ['PolymorphicComment', 'PostTag', 'Post\Comment', 'Post', 'Tag', 'Author', 'Setting', 'Event\Search', 'Event'];

    public static function setupBeforeClass()
    {
        foreach (self::$entities as $entity) {
            test_spot_mapper('\SpotTest\Entity\\' . $entity)->migrate();
        }

        $authorMapper = test_spot_mapper('SpotTest\Entity\Author');
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

    public static function tearDownAfterClass()
    {
        foreach (self::$entities as $entity) {
            test_spot_mapper('\SpotTest\Entity\\' . $entity)->dropTable();
        }
    }

    public function testSampleNewsInsert()
    {
        $mapper = test_spot_mapper('SpotTest\Entity\Post');
        $post = $mapper->get();
        $post->title = "Test Post";
        $post->body = "<p>This is a really awesome super-duper post.</p><p>It's really quite lovely.</p>";
        $post->author_id = 1;
        $post->date_created = new \DateTime();
        $result = $mapper->insert($post); // returns an id

        $this->assertTrue($result !== false);

        return $post;
    }

    public function testSampleNewsInsertWithEmptyNonRequiredFields()
    {
        $mapper = test_spot_mapper('SpotTest\Entity\Post');
        $post = $mapper->get();
        $post->title = "Test Post With Empty Values";
        $post->body = "<p>Test post here.</p>";
        $post->author_id = 1;
        $post->date_created = null;
        try {
            $result = $mapper->insert($post); // returns an id
        } catch (Exception $e) {
            $result = false;
        }

        $this->assertTrue($result !== false);
    }

    public function testSelect()
    {
        $mapper = test_spot_mapper('SpotTest\Entity\Post');
        $post = $mapper->first(['title' => "Test Post"]);

        $this->assertTrue($post instanceof Entity\Post);
    }

    public function testInsertThenSelectReturnsProperTypes()
    {
        // Insert Post into database
        $mapper = test_spot_mapper('SpotTest\Entity\Post');
        $post = $mapper->get();
        $post->title = "Types Test";
        $post->body = "<p>This is a really awesome super-duper post.</p><p>It's really quite lovely.</p>";
        $post->status = 1;
        $post->date_created = new \DateTime();
        $post->author_id = 1;
        $result = $mapper->insert($post); // returns an id

        // Read Post from database
        $post = $mapper->get($result);

        // Strict equality
        $this->assertSame(1, $post->status);
        $postData = $post->data();
        $this->assertSame(1, $postData['status']);
    }

    public function testSampleNewsUpdate()
    {
        $mapper = test_spot_mapper('SpotTest\Entity\Post');
        $post = $mapper->first(['title' => "Test Post"]);
        $this->assertTrue($post instanceof Entity\Post);

        $post->title = "Test Post Modified";
        $result = $mapper->update($post); // returns boolean

        $postu = $mapper->first(['title' => "Test Post Modified"]);
        $this->assertInstanceOf('SpotTest\Entity\Post', $postu);
    }

    public function testSampleNewsDelete()
    {
        $mapper = test_spot_mapper('SpotTest\Entity\Post');
        $post = $mapper->first(['title' => "Test Post Modified"]);
        $result = $mapper->delete($post);

        $this->assertTrue((boolean) $result);
    }

    public function testMultipleConditionDelete()
    {
        $postMapper = test_spot_mapper('SpotTest\Entity\Post');
        for ($i = 1; $i <= 10; $i++) {
            $postMapper->insert([
                'title' => ($i % 2 ? 'odd' : 'even' ). '_title',
                'author_id' => 1,
                'body' => '<p>' . $i  . '_body</p>',
                'status' => $i ,
                'date_created' => new \DateTime()
            ]);
        }

        $result = $postMapper->delete(['status !=' => [3, 4, 5], 'title' => 'odd_title']);
        $this->assertTrue((boolean) $result);
        $this->assertEquals(3, $result);

    }

    public function testPostTagUpsert()
    {
        $tagMapper = test_spot_mapper('SpotTest\Entity\Tag');
        $tag = $tagMapper->build([
            'id' => 2145,
            'name' => 'Example Tag'
        ]);
        $result = $tagMapper->insert($tag);

        if (!$result) {
            throw new \Exception("Unable to create tag: " . var_export($tag->data(), true));
        }

        $postMapper = test_spot_mapper('SpotTest\Entity\Post');
        $post = $postMapper->build([
            'id' => 1295,
            'title' => 'Example Title',
            'author_id' => 1,
            'body' => '<p>body</p>',
            'status' => 0,
            'date_created' => new \DateTime()
        ]);
        $result = $postMapper->insert($post);

        if (!$result) {
            throw new \Exception("Unable to create post: " . var_export($post->data(), true));
        }

        $postTagMapper = test_spot_mapper('SpotTest\Entity\PostTag');
        $data = [
            'tag_id' => 2145,
            'post_id' => 1295
        ];
        $where = [
            'tag_id' => 2145
        ];

        // Posttags has unique constraint on tag+post, so insert will fail the second time
        $result = $postTagMapper->upsert($data, $where);
        $result2 = $postTagMapper->upsert(array_merge($data, ['random' => 'blah blah']), $where);
        $postTag = $postTagMapper->first($where);

        $this->assertTrue((boolean) $result);
        $this->assertTrue((boolean) $result2);
        $this->assertSame('blah blah', $postTag->random);
    }

    public function testUniqueConstraintUpsert()
    {
        $mapper = test_spot_mapper('SpotTest\Entity\Setting');
        $data = [
            'skey' => 'my_setting',
            'svalue' => 'abc123'
        ];
        $where = [
            'skey' => 'my_setting'
        ];

        // Posttags has unique constraint on tag+post, so insert will fail the second time
        $result = $mapper->upsert($data, $where);
        $result2 = $mapper->upsert(['svalue' => 'abcdef123456'], $where);
        $entity = $mapper->first($where);

        $this->assertTrue((boolean) $result);
        $this->assertTrue((boolean) $result2);
        $this->assertSame('abcdef123456', $entity->svalue);
    }

    public function testTruncate()
    {
        $postTagMapper = test_spot_mapper('SpotTest\Entity\PostTag');
        $postTagMapper->truncateTable();
    }

    public function testDeleteAll()
    {
        $postTagMapper = test_spot_mapper('SpotTest\Entity\PostTag');
        $postTagMapper->delete();
    }

    /**
     * @expectedException Spot\Exception
     */
    public function testStrictInsert()
    {
        $postMapper = test_spot_mapper('SpotTest\Entity\Post');
        $result = $postMapper->insert([
            'title' => 'irrelevant_title',
            'author_id' => 1,
            'body' => '<p>test_body</p>',
            'status' => 10,
            'date_created' => new \DateTime(),
            'additional_field' => 'Should cause an error'
        ]);
    }

    public function testNonStrictInsert()
    {
        $postMapper = test_spot_mapper('SpotTest\Entity\Post');
        $result = $postMapper->insert([
            'title' => 'irrelevant_title',
            'author_id' => 1,
            'body' => '<p>test_body</p>',
            'status' => 10,
            'date_created' => new \DateTime(),
            'additional_field' => 'Should cause an error'
        ], ['strict' => false]);

        $this->assertTrue((boolean) $result);
    }

    /**
     * @expectedException Spot\Exception
     */
    public function testStrictUpdate()
    {
        $postMapper = test_spot_mapper('SpotTest\Entity\Post');
        $post = $postMapper->create([
            'title' => 'irrelevant_title',
            'author_id' => 1,
            'body' => '<p>test_body</p>',
            'status' => 10,
            'date_created' => new \DateTime()
        ]);

        $post->additional_field = 'Should cause an error';
        $result = $postMapper->update($post);
    }

    public function testNonStrictUpdate()
    {
        $postMapper = test_spot_mapper('SpotTest\Entity\Post');
        $post = $postMapper->create([
            'title' => 'irrelevant_title',
            'author_id' => 1,
            'body' => '<p>test_body</p>',
            'status' => 10,
            'date_created' => new \DateTime()
        ]);

        $post->status = 11;
        $post->additional_field = 'Should cause an error';

        $result = $postMapper->update($post, ['strict' => false]);
        $this->assertTrue((boolean) $result);
        $this->assertTrue( ! $post->isModified());
    }

    /**
     * @expectedException Spot\Exception
     */
    public function testStrictSave()
    {
        $postMapper = test_spot_mapper('SpotTest\Entity\Post');
        $post = $postMapper->build([
            'title' => 'irrelevant_title',
            'author_id' => 1,
            'body' => '<p>test_body</p>',
            'status' => 10,
            'date_created' => new \DateTime(),
            'additional_field' => 'Should cause an error'
        ]);

        $postMapper->save($post);
    }

    public function testNonStrictSave()
    {
        $postMapper = test_spot_mapper('SpotTest\Entity\Post');
        $post = $postMapper->build([
            'title' => 'irrelevant_title',
            'author_id' => 1,
            'body' => '<p>test_body</p>',
            'status' => 10,
            'date_created' => new \DateTime(),
            'additional_field' => 'Should cause an error'
        ]);

        $result = $postMapper->save($post, ['strict' => false]);
        $this->assertTrue((boolean) $result);
    }

    /**
     * @group save-relations
     */
    public function testHasOneNewEntitySaveRelation()
    {
        $mapper = test_spot_mapper('SpotTest\Entity\Event');
        $searchMapper = test_spot_mapper('SpotTest\Entity\Event\Search');
        $search = new Entity\Event\Search(['body' => 'Some body content']);
        $event = $mapper->build([
            'title' => 'Test',
            'description' => 'Test description',
            'type' => 'free',
            'token' => 'some-token',
            'date_start' => new \DateTime
        ]);
        $event->relation('search', $search);
        $mapper->save($event, ['relations' => true]);

        $this->assertEquals($event->id, $search->event_id);
        $this->assertEquals($event->search->id, $search->id);

        //Check that old related entity gets deleted when updating relationship
        $search2 = new Entity\Event\Search(['body' => 'body2']);
        $event->relation('search', $search2);
        $mapper->save($event, ['relations' => true]);

        $queryHasOne = $searchMapper->where(['event_id' => $event->id]);
        $this->assertEquals(count($queryHasOne), 1);
        $this->assertEquals($queryHasOne->first()->get('body'), 'body2');
    }

    /**
     * @group save-relations
     */
    public function testHasOneRelatedEntityAlreadyExists()
    {

        $mapper = test_spot_mapper('SpotTest\Entity\Event');
        $searchMapper = test_spot_mapper('SpotTest\Entity\Event\Search');
        $data = [
            'title' => 'Test',
            'description' => 'Test description',
            'type' => 'free',
            'token' => 'some-token',
            'date_start' => new \DateTime
        ];
        $event = $mapper->build($data);
        $mapper->insert($mapper->build($data));
        $mapper->save($event);
        $search2 = new Entity\Event\Search(['body' => 'body2', 'event_id' => 1]);
        $searchMapper->save($search2);
        
        $savedEvent = $mapper->get($event->primaryKey());
        $savedEvent->relation('search', $search2);
        $mapper->save($savedEvent, ['relations' => true]);
        $savedEvent = $mapper->get($savedEvent->primaryKey());
        $this->assertEquals($savedEvent->search->id, $search2->id);
        $this->assertEquals($savedEvent->search->event_id, $search2->event_id);
        $this->assertEquals($savedEvent->id, $search2->event_id);
        $this->assertEquals($savedEvent->search->body, $search2->body);
    }

    /**
     * @group save-relations
     */
    public function testHasOneIgnoreRelationNotLoaded()
    {
        $mapper = test_spot_mapper('SpotTest\Entity\Event');
        $searchMapper = test_spot_mapper('SpotTest\Entity\Event\Search');
        $event = $mapper->build([
            'title' => 'Test',
            'description' => 'Test description',
            'type' => 'free',
            'token' => 'some-token',
            'date_start' => new \DateTime
        ]);
        $mapper->save($event);
        $searchMapper->delete(['event_id' => $event->id]);
        $savedEvent = $mapper->get($event->primaryKey());
        $savedEvent->set('title', 'Test 2');

        $this->assertEquals($mapper->save($savedEvent, ['relations' => true]), 1);

    }

    /**
     * @group save-relations
     */
    public function testBelongsToNewEntitySaveRelation()
    {
        $mapper = test_spot_mapper('SpotTest\Entity\Post');
        $author = new \SpotTest\Entity\Author(['id' => 2, 'email' => 'test@example.com', 'password' => '123456']);
        $post = $mapper->build([
            'title' => 'Test',
            'body' => 'Test description',
        ]);
        $post->relation('author', $author);
        $mapper->save($post, ['relations' => true]);

        $this->assertEquals($post->author_id, $author->id);
        $this->assertFalse($post->isNew());
        $this->assertFalse($author->isNew());

        $author2 = new \SpotTest\Entity\Author(['id' => 3, 'email' => 'test2@example.com', 'password' => '123456789']);
        $post->relation('author', $author2);
        $mapper->save($post, ['relations' => true]);

        $this->assertEquals($post->author_id, $author2->id);
    }

    /**
     * @group save-relations
     */
    public function testHasManyNewEntitySaveRelation()
    {
        $mapper = test_spot_mapper('SpotTest\Entity\Post');
        $commentMapper = test_spot_mapper('SpotTest\Entity\Post\Comment');
        $comments = [];
        for ($i = 1; $i < 3; $i++) {
            $comments[] = new \SpotTest\Entity\Post\Comment([
                'name' => 'John Doe',
                'email'  => 'test@example.com',
                'body' => '#'.$i.': Lorem ipsum is dolor.',
            ]);
        }
        $post = $mapper->build([
            'title' => 'Test',
            'body' => 'Test description',
            'author_id' => 1
        ]);
        $post->relation('comments', new \Spot\Entity\Collection($comments));
        $mapper->save($post, ['relations' => true]);
        $this->assertFalse($post->isNew());
        foreach ($post->comments as $comment) {
            $this->assertFalse($comment->isNew());
            $this->assertEquals($comment->post_id, $post->id);
        }
        //Test comment deleted from DB when removed from relation
        $removedComment = array_shift($comments);
        $post->relation('comments', new \Spot\Entity\Collection($comments));
        $mapper->save($post, ['relations' => true]);
        $this->assertEquals($commentMapper->get($removedComment->primaryKey()), false);
        $this->assertEquals($commentMapper->where(['post_id' => $post->id])->count(), 1);

        //Test all comments removed when relation set to false
        $post->relation('comments', false);
        $mapper->save($post, ['relations' => true]);
        foreach ($comments as $comment) {
            $this->assertEquals($commentMapper->get($comment->primaryKey()), false);
        }
    }

    /**
     * @group save-relations
     */
    public function testHasManyExistingEntitySaveRelation()
    {
        $mapper = test_spot_mapper('SpotTest\Entity\Post');
        $data = [
            'title' => 'Test',
            'body' => 'Test description',
            'author_id' => 1
        ];
        $mapper->save($mapper->build(array_merge($data, ['id' => 99])));
        $commentMapper = test_spot_mapper('SpotTest\Entity\Post\Comment');
        $comments = [];
        for ($i = 1; $i < 3; $i++) {
            $comment = new \SpotTest\Entity\Post\Comment([
                'name' => 'John Doe',
                'email'  => 'test@example.com',
                'post_id' => 99,
                'body' => '#'.$i.': Lorem ipsum is dolor.',
            ]);
            $commentMapper->insert($comment);
            $comments[] = $comment;
        }
        $post = $mapper->build($data);
        $post->relation('comments', new \Spot\Entity\Collection($comments));
        $mapper->save($post, ['relations' => true]);
        $post = $mapper->get($post->primaryKey());
        $this->assertTrue(count($post->comments) === 2);
    }

    /**
     * @group save-relations
     */
    public function testHasManyThroughRelationSave()
    {
        $mapper = test_spot_mapper('SpotTest\Entity\Post');
        $postTagMapper = test_spot_mapper('SpotTest\Entity\PostTag');
        $tags = [];
        for ($i = 1; $i < 3; $i++) {
            $tags[] = new \SpotTest\Entity\Tag([
                'name' => 'Tag #'.$i
            ]);
        }
        $post = $mapper->build([
            'title' => 'Test',
            'body' => 'Test description',
            'author_id' => 1
        ]);
        $post->relation('tags', new \Spot\Entity\Collection($tags));
        $mapper->save($post, ['relations' => true]);

        $this->assertFalse($post->isNew());
        $this->assertEquals($postTagMapper->all()->count(), 2);
        $i = 1;
        foreach ($post->tags as $tag) {
            $this->assertFalse($tag->isNew());
            $this->assertEquals($tag->name, 'Tag #'.$i);
            $i++;
        }

        //Test comment deleted from DB when removed from relation
        $removedTag = array_shift($tags);
        $post->relation('tags', new \Spot\Entity\Collection($tags));
        $mapper->save($post, ['relations' => true]);
        $this->assertEquals($postTagMapper->where(['tag_id' => $removedTag->primaryKey()])->count(), 0);

        //Test all comments removed when relation set to false
        $post->relation('tags', false);
        $mapper->save($post, ['relations' => true]);
        $this->assertEquals($postTagMapper->all()->count(), 0);
    }

    /**
     * @depends testSampleNewsInsert
     */
    public function testQueryWithDateTimeObjectValue($post)
    {
        $mapper = test_spot_mapper('SpotTest\Entity\Post');
        $results = $mapper->where(['date_created <=' => new \DateTime()])->toArray();

        $this->assertTrue(count($results) > 0);
    }
}
