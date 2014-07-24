<?php
namespace SpotTest;

/**
 * @package Spot
 */
class Insert extends \PHPUnit_Framework_TestCase
{
    private static $entities = ['Post', 'Author', 'Event', 'Event\Search'];

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

    public function testInsertPostEntity()
    {
        $post = new \SpotTest\Entity\Post();
        $mapper = test_spot_mapper('\SpotTest\Entity\Post');
        $post->title = "Test Post";
        $post->body = "<p>This is a really awesome super-duper post.</p><p>It's really quite lovely.</p>";
        $post->date_created = new \DateTime();
        $post->author_id = 1;

        $result = $mapper->insert($post);

        $this->assertTrue($result !== false);
        $this->assertTrue($post->id !== null);
    }

    public function testInsertPostEntitySequencesAreCorrect()
    {
        $mapper = test_spot_mapper('SpotTest\Entity\Post');

        $post = new Entity\Post();
        $post->title = "Test Post";
        $post->body = "<p>This is a really awesome super-duper post.</p><p>It's really quite lovely.</p>";
        $post->date_created = new \DateTime();
        $post->author_id = 1;
        $result = $mapper->insert($post);

        $post2 = new Entity\Post();
        $post2->title = "Test Post";
        $post2->body = "<p>This is a really awesome super-duper post.</p><p>It's really quite lovely.</p>";
        $post2->date_created = new \DateTime();
        $post2->author_id = 1;
        $result = $mapper->insert($post2);

        // Ensure sequence is incrementing number
        $this->assertNotEquals($post->id, $post2->id);
    }

    public function testInsertPostArray()
    {
        $mapper = test_spot_mapper('\SpotTest\Entity\Post');
        $post = [
            'title' => "Test Post",
            'author_id' => 1,
            'body' => "<p>This is a really awesome super-duper post.</p><p>It's really quite lovely.</p>",
            'date_created' => new \DateTime()
        ];
        $result = $mapper->insert($post); // returns inserted id

        $this->assertTrue($result !== false);
    }

    public function testCreateInsertsEntity()
    {
        $mapper = test_spot_mapper('\SpotTest\Entity\Post');
        $post = [
            'title' => "Test Post 101",
            'author_id' => 101,
            'body' => "<p>Test Post 101</p><p>It's really quite lovely.</p>",
            'date_created' => new \DateTime()
        ];
        $result = $mapper->create($post);

        $this->assertTrue($result !== false);
    }

    public function testBuildReturnsEntityUnsaved()
    {
        $mapper = test_spot_mapper('\SpotTest\Entity\Post');
        $post = [
            'title' => "Test Post 100",
            'author_id' => 100,
            'body' => "<p>Test Post 100</p>",
            'date_created' => new \DateTime()
        ];
        $result = $mapper->build($post);

        $this->assertInstanceOf('\SpotTest\Entity\Post', $result);
        $this->assertTrue($result->isNew());
        $this->assertEquals(null, $result->id);
    }

    public function testCreateReturnsEntity()
    {
        $mapper = test_spot_mapper('\SpotTest\Entity\Post');
        $post = [
            'title' => "Test Post 101",
            'author_id' => 101,
            'body' => "<p>Test Post 101</p>",
            'date_created' => new \DateTime()
        ];
        $result = $mapper->create($post);

        $this->assertInstanceOf('\SpotTest\Entity\Post', $result);
        $this->assertFalse($result->isNew());
    }

    public function testInsertNewEntitySavesWithIdAlreadySet()
    {
        $mapper = test_spot_mapper('\SpotTest\Entity\Post');
        $post = new \SpotTest\Entity\Post([
            'id' => 2001,
            'title' => "Test Post 2001",
            'author_id' => 2001,
            'body' => "<p>Test Post 2001</p>"
        ]);
        $result = $mapper->insert($post);
        $entity = $mapper->get($post->id);

        $this->assertInstanceOf('\SpotTest\Entity\Post', $entity);
        $this->assertFalse($entity->isNew());
    }

    public function testInsertEventRunsValidation()
    {
        $mapper = test_spot_mapper('\SpotTest\Entity\Event');
        $event = new \SpotTest\Entity\Event([
            'title' => 'Test Event 1',
            'description' => 'Test Description',
            'date_start' => new \DateTime('+1 day')
        ]);
        $result = $mapper->insert($event);

        $this->assertFalse($result);
        $this->assertContains('Type is required', $event->errors('type'));
    }

    public function testSaveEventRunsAfterInsertHook()
    {
        $mapper = test_spot_mapper('\SpotTest\Entity\Event');
        $event = new \SpotTest\Entity\Event([
            'title' => 'Test Event 1',
            'description' => 'Test Description',
            'type' => 'free',
            'date_start' => new \DateTime('+1 day')
        ]);

        $result = $mapper->save($event);

        $this->assertTrue($result !== false);
    }

    public function testInsertEventRunsDateValidation()
    {
        $mapper = test_spot_mapper('\SpotTest\Entity\Event');
        $event = new \SpotTest\Entity\Event([
            'title' => 'Test Event 1',
            'description' => 'Test Description',
            'type' => 'vip',
            'date_start' => new \DateTime('-1 day')
        ]);
        $result = $mapper->insert($event);
        $dsErrors = $event->errors('date_start');

        $this->assertFalse($result);
        $this->assertContains('Date Start must be date after', $dsErrors[0]);
    }

    public function testInsertEventRunsTypeOptionsValidation()
    {
        $mapper = test_spot_mapper('\SpotTest\Entity\Event');
        $event = new \SpotTest\Entity\Event([
            'title' => 'Test Event 1',
            'description' => 'Test Description',
            'type' => 'invalid_value',
            'date_start' => new \DateTime('+1 day')
        ]);
        $result = $mapper->insert($event);

        $this->assertFalse($result);
        $this->assertEquals(['Type contains invalid value'], $event->errors('type'));
    }

    /**
     * @expectedException Spot\Exception
     */
    public function testCreateWithErrorsThrowsException()
    {
        $mapper = test_spot_mapper('\SpotTest\Entity\Event');
        $event = $mapper->create([
            'title' => 'Test Event 1',
            'description' => 'Test Description',
            'date_start' => new \DateTime('+1 day')
        ]);
    }
}

