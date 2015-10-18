<?php
namespace SpotTest;

/**
 * @package Spot
 */
class CRUD extends \PHPUnit_Framework_TestCase
{
    public static function setupBeforeClass()
    {
        foreach (['Post', 'Post\Comment', 'Tag', 'PostTag', 'Author', 'Setting', 'Event', 'Event\Search', 'PolymorphicComment'] as $entity) {
            test_spot_mapper('\SpotTest\Entity\\' . $entity)->migrate();
        }
    }

    public static function tearDownAfterClass()
    {
        foreach (['Post', 'Post\Comment', 'Tag', 'PostTag', 'Author', 'Setting', 'Event', 'Event\Search', 'PolymorphicComment'] as $entity) {
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
        $postMapper = test_spot_mapper('SpotTest\Entity\PostTag');
        $data = [
            'tag_id' => 2145,
            'post_id' => 1295
        ];
        $where = [
            'tag_id' => 2145
        ];

        // Posttags has unique constraint on tag+post, so insert will fail the second time
        $result = $postMapper->upsert($data, $where);
        $result2 = $postMapper->upsert(array_merge($data, ['random' => 'blah blah']), $where);
        $postTag = $postMapper->first($where);

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

    public function testHasOneRelationValidation()
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
        
        $this->assertEquals($searchMapper->get($search->primaryKey()), false);
    }

    public function testBelongsToRelationValidation()
    {
        $mapper = test_spot_mapper('SpotTest\Entity\Post');
        $author = new \SpotTest\Entity\Author(['email' => 'test@example.com', 'password' => '123456']);
        $post = $mapper->build([
            'title' => 'Test',
            'body' => 'Test description',
        ]);
        $post->relation('author', $author);
        $mapper->save($post, ['relations' => true]);
        
        $this->assertEquals($post->author_id, $author->id);
        $this->assertFalse($post->isNew());
        $this->assertFalse($author->isNew());

        $author2 = new \SpotTest\Entity\Author(['email' => 'test2@example.com', 'password' => '123456789']);
        $post->relation('author', $author2);
        $mapper->save($post, ['relations' => true]);
        
        $this->assertEquals($post->author_id, $author2->id);
    }

    public function testHasManyRelationValidation()
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
            'author_id' => 5
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

        //Test all comments removed when relation set to false
        $post->relation('comments', false);
        $mapper->save($post, ['relations' => true]);
        foreach ($comments as $comment) {
            $this->assertEquals($commentMapper->get($comment->primaryKey()), false);
        }
    }

    public function testHasManyThroughRelationValidation()
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
            'author_id' => 5
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
}
