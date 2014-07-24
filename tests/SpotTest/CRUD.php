<?php
namespace SpotTest;

/**
 * @package Spot
 */
class CRUD extends \PHPUnit_Framework_TestCase
{
    public static function setupBeforeClass()
    {
        foreach(['Post', 'Post\Comment', 'Tag', 'PostTag', 'Author', 'Setting'] as $entity) {
            test_spot_mapper('\SpotTest\Entity\\' . $entity)->migrate();
        }
    }

    public static function tearDownAfterClass()
    {
        foreach(['Post', 'Post\Comment', 'Tag', 'PostTag', 'Author', 'Setting'] as $entity) {
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
        } catch(Exception $e) {
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
        for( $i = 1; $i <= 10; $i++ ) {
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
}
