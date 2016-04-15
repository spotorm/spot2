<?php
namespace SpotTest;

/**
 * @package Spot
 */
class CRUD extends \PHPUnit_Framework_TestCase
{
    private static $entities = ['PostTag', 'Post\Comment', 'Post', 'Tag', 'Author', 'Setting'];

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
}
