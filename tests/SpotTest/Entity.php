<?php
namespace SpotTest;

/**
 * @package Spot
 */
class Entity extends \PHPUnit_Framework_TestCase
{
    private static $entities = ['Post', 'Author'];

    public static function setupBeforeClass()
    {
        foreach (self::$entities as $entity) {
            test_spot_mapper('\SpotTest\Entity\\' . $entity)->migrate();
        }
    }

    public static function tearDownAfterClass()
    {
        foreach (self::$entities as $entity) {
            test_spot_mapper('\SpotTest\Entity\\' . $entity)->dropTable();
        }
    }

    public function testEntitySetDataProperties()
    {
        $mapper = test_spot_mapper('SpotTest\Entity\Post');
        $post = new \SpotTest\Entity\Post();

        // Set data
        $post->title = "My Awesome Post";
        $post->body = "<p>Body</p>";
        $post->author_id = 1;

        $data = $post->data();
        ksort($data);

        $testData = [
            'id' => null,
            'title' => 'My Awesome Post',
            'body' => '<p>Body</p>',
            'status' => 0,
            'date_created' => new \DateTime(),
            'data' => null,
            'author_id' => 1
        ];
        ksort($testData);

        $this->assertEquals($testData, $data);

        $this->assertNull($post->asdf);
    }

    public function testEntitySetDataConstruct()
    {
        $mapper = test_spot_mapper('SpotTest\Entity\Post');
        $post = new \SpotTest\Entity\Post([
            'title' => 'My Awesome Post',
            'body' => '<p>Body</p>',
            'author_id' => 1,
            'date_created' => new \DateTime()
        ]);

        $data = $post->data();
        ksort($data);

        $testData = [
            'id' => null,
            'title' => 'My Awesome Post',
            'body' => '<p>Body</p>',
            'status' => 0,
            'date_created' => null,
            'data' => null,
            'author_id' => 1,
            'date_created' => new \DateTime()
        ];
        ksort($testData);

        $this->assertEquals($testData, $data);
    }

    public function testEntityErrors()
    {
        $post = new \SpotTest\Entity\Post([
            'title' => 'My Awesome Post',
            'body' => '<p>Body</p>'
        ]);
        $postErrors = [
            'title' => ['Title cannot contain the word awesome']
        ];

        // Has NO errors
        $this->assertTrue(!$post->hasErrors());

        // Set errors
        $post->errors($postErrors);

        // Has errors
        $this->assertTrue($post->hasErrors());

        // Full error array
        $this->assertEquals($postErrors, $post->errors());

        // Errors for one key only
        $this->assertEquals($postErrors['title'], $post->errors('title'));
    }

    public function testDataModified()
    {
        $data = [
            'title' => 'My Awesome Post 2',
            'body' => '<p>Body 2</p>'
        ];

        $testData = [
            'id' => null,
            'title' => 'My Awesome Post',
            'body' => '<p>Body</p>',
            'status' => 0,
            'date_created' => null,
            'data' => null,
            'author_id' => 1
        ];

        // Set initial data
        $post = new \SpotTest\Entity\Post($testData);

        $this->assertEquals($testData, $post->dataUnmodified());
        $this->assertEquals([], $post->dataModified());
        $this->assertFalse($post->isModified());

        $post->data($data);
        $this->assertEquals($data, $post->dataModified());
        $this->assertTrue($post->isModified('title'));
        $this->assertFalse($post->isModified('id'));
        $this->assertNull($post->isModified('asdf'));
        $this->assertTrue($post->isModified());
        $this->assertEquals($data['title'], $post->dataModified('title'));
        $this->assertEquals($testData['title'], $post->dataUnmodified('title'));
        $this->assertNull($post->dataModified('id'));
        $this->assertNull($post->dataModified('status'));
    }

    public function testDataNulls()
    {
        $data = [
            'title' => 'A Post',
            'body' => 'A Body',
            'status' => 0,
            'author_id' => 1,
        ];

        $post = new \SpotTest\Entity\Post($data);

        $post->status = null;
        $this->assertTrue($post->isModified('status'));

        $post->status = 1;
        $this->assertTrue($post->isModified('status'));

        $post->data(['status' => null]);
        $this->assertTrue($post->isModified('status'));

        $post->title = '';
        $this->assertTrue($post->isModified('title'));

        $this->title = null;
        $this->assertTrue($post->isModified('title'));

        $this->title = 'A Post';
        $post->data(['title' => null]);
        $this->assertTrue($post->isModified('title'));
    }

    public function testJsonArray()
    {
        $data = [
            'title' => 'A Post',
            'body' => 'A Body',
            'status' => 0,
            'author_id' => 1,
            'data' => ['posts' => 'are cool', 'another field' => 'to serialize'],
            'date_created' => new \DateTime()
        ];
        $post = new \SpotTest\Entity\Post($data);
        $this->assertEquals($post->data, ['posts' => 'are cool', 'another field' => 'to serialize']);

        $mapper = test_spot_mapper('SpotTest\Entity\Post');
        $mapper->save($post);

        $post = $mapper->get($post->id);
        $this->assertEquals($post->data, ['posts' => 'are cool', 'another field' => 'to serialize']);

        $post->data = 'asdf';
        $this->assertEquals($post->data, 'asdf');

        $mapper->save($post);
        $post = $mapper->get($post->id);
        $this->assertEquals($post->data, 'asdf');
    }

    public function testDataReferences()
    {
        $data = [
            'title' => 'A Post',
            'body' => 'A Body',
            'status' => 0,
            'data' => ['posts' => 'are cool', 'another field' => 'to serialize'],
            'date_created' => new \DateTime()
        ];

        $post = new \SpotTest\Entity\Post($data);

        // Reference test
        $title = $post->title;
        $this->assertEquals($title, $post->title);
        $title = 'asdf';
        $this->assertEquals('A Post', $post->title);
        $this->assertEquals('asdf', $title);

        // Property settting
        $post->date_created = null;
        $this->assertNull($post->date_created);

        $post->data['posts'] = 'are really cool';
        $this->assertEquals($post->data, ['posts' => 'are really cool', 'another field' => 'to serialize']);

        $data =& $post->data;
        $data['posts'] = 'are still cool';
        $this->assertEquals($post->data, ['posts' => 'are still cool', 'another field' => 'to serialize']);
    }

    public function testLocalVariablesAreNotByReference()
    {
        $data = [
            'title' => 'A Post',
            'body' => 'A Body',
            'status' => 0,
            'data' => ['posts' => 'are cool', 'another field' => 'to serialize'],
            'date_created' => new \DateTime()
        ];

        $post = new \SpotTest\Entity\Post($data);

        $title = $post->title;
        $title = 'A Post Title';

        $this->assertNotEquals($title, $post->title);
    }

    public function testLocalArrayVariablesAreNotByReference()
    {
        $data = [
            'title' => 'A Post',
            'body' => 'A Body',
            'status' => 0,
            'data' => ['posts' => 'are cool', 'another field' => 'to serialize'],
            'date_created' => new \DateTime()
        ];

        $post = new \SpotTest\Entity\Post($data);

        $data = $post->data;
        $data['posts'] = 'are not by reference';

        $this->assertNotEquals($data, $post->data);
    }

    public function testCustomSetterMethod()
    {
        $entity = new \SpotTest\Entity\CustomMethods();
        $entity->test1 = 'test';

        $this->assertEquals('test_test_gotten', $entity->test1);
    }

    public function testCustomSetterMethodWithArrayLoad()
    {
        $entity = new \SpotTest\Entity\CustomMethods([
            'test1' => 'test'
        ]);

        $this->assertEquals('test_test_gotten', $entity->test1);
    }

    public function testCustomGetterMethodWithArrayData()
    {
        $entity = new \SpotTest\Entity\CustomMethods([
            'test1' => 'test'
        ]);
        $data = $entity->data();

        $this->assertEquals('test_test_gotten', $data['test1']);
    }

    public function testGetPrimaryKeyField()
    {
        $entity = new \SpotTest\Entity\CustomMethods([
            'test1' => 'test'
        ]);
        $this->assertEquals('id', $entity->primaryKeyField());
    }

    public function testGetPrimaryKeyFieldValue()
    {
        $entity = new \SpotTest\Entity\CustomMethods([
            'test1' => 'test'
        ]);
        $this->assertEquals($entity->id, $entity->primaryKey());
    }

    public function testJsonEncodeJsonSerializable()
    {
        $post = new \SpotTest\Entity\Post([
            'title' => 'A Post',
            'body' => 'A Body',
            'status' => 0,
            'data' => ['posts' => 'are cool', 'another field' => 'to serialize'],
            'date_created' => new \DateTime()
        ]);
        $json = json_encode($post);
        $data = json_decode($json, true);

        $this->assertEquals('A Post', $data['title']);
    }

    public function testToStringReturnsJson()
    {
        $post = new \SpotTest\Entity\Post([
            'title' => 'A Post',
            'body' => 'A Body',
            'status' => 0,
            'data' => ['posts' => 'are cool', 'another field' => 'to serialize'],
            'date_created' => new \DateTime()
        ]);
        $json = (string) $post;
        $data = json_decode($json, true);

        $this->assertEquals('A Post', $data['title']);
    }
}
