<?php
namespace SpotTest\Cases;
/**
 * @package Spot
 */
class ValidationTest extends \PHPUnit\Framework\TestCase
{
    private static $entities = ['Author', 'Report'];

    public static function setupBeforeClass(): void
    {
        foreach (self::$entities as $entity) {
            \test_spot_mapper('\SpotTest\Cases\Entity\\' . $entity)->migrate();
        }
    }

    public static function tearDownAfterClass(): void
    {
        foreach (self::$entities as $entity) {
            \test_spot_mapper('\SpotTest\Cases\Entity\\' . $entity)->dropTable();
        }
    }

    public function tearDown(): void
    {
        $mapper = \test_spot_mapper('SpotTest\Entity\Author');
        $mapper->truncateTable();
    }

    public function testRequiredField()
    {
        $mapper = \test_spot_mapper('SpotTest\Entity\Author');

        $entity = new SpotTest\Entity\Author([
            'is_admin' => true
        ]);
        $mapper->save($entity);

        $this->assertTrue($entity->hasErrors());
        $this->assertContains("Email is required", $entity->errors('email'));
    }

    public function testUniqueField()
    {
        $mapper = \test_spot_mapper('SpotTest\Entity\Author');

        // Setup new user
        $user1 = new SpotTest\Entity\Author([
            'email' => 'test@test.com',
            'password' => 'test',
            'is_admin' => true
        ]);
        $mapper->save($user1);

        // Setup new user (identical, expecting a validation error)
        $user2 = new SpotTest\Entity\Author([
            'email' => 'test@test.com',
            'password' => 'test',
            'is_admin' => false
        ]);
        $mapper->save($user2);

        $this->assertFalse($user1->hasErrors());
        $this->assertTrue($user2->hasErrors());
        $this->assertContains("Email 'test@test.com' is already taken.", $user2->errors('email'));
    }

    public function testUniqueFieldConvertToDb()
    {
        $mapper = \test_spot_mapper('SpotTest\Entity\Report');

        // Setup new report
        $report1 = new SpotTest\Entity\Report([
            'date' => new \DateTime('2016-05-04'),
            'result' => ['a' => 1, 'b' => 2],
        ]);
        $mapper->save($report1);

        // Setup new report (same date, expecting error)
        $report2 = new SpotTest\Entity\Report([
            'date' => new \DateTime('2016-05-04'),
            'result' => ['a' => 2, 'b' => 1],
        ]);
        $mapper->save($report2);

        $this->assertFalse($report1->hasErrors());
        $this->assertTrue($report2->hasErrors());
        $this->assertContains("Date '2016-05-04' is already taken.", $report2->errors('date'));
    }

    public function testEmail()
    {
        $mapper = \test_spot_mapper('SpotTest\Entity\Author');

        $entity = new SpotTest\Entity\Author([
            'email' => 'test',
            'password' => 'test'
        ]);
        $mapper->save($entity);

        $this->assertTrue($entity->hasErrors());
        $this->assertContains("Email is not a valid email address", $entity->errors('email'));
    }

    public function testLength()
    {
        $mapper = \test_spot_mapper('SpotTest\Entity\Author');

        $entity = new SpotTest\Entity\Author([
            'email' => 't@t',
            'password' => 'test'
        ]);
        $mapper->save($entity);

        $this->assertTrue($entity->hasErrors());
        $this->assertContains("Email must be 4 characters long", $entity->errors('email'));
    }

    public function testDisabledValidation()
    {
        $mapper = \test_spot_mapper('SpotTest\Entity\Author');

        $entity = new SpotTest\Entity\Author([
            'email' => 't@t',
            'password' => 'test'
        ]);
        $mapper->save($entity, ['validate' => false]);

        $this->assertFalse($entity->hasErrors());
    }

    public function testHasOneRelationValidation()
    {
        $mapper = \test_spot_mapper('SpotTest\Entity\Event');
        $search = new SpotTest\Entity\Event\Search();
        $event = $mapper->build([]);
        $event->relation('search', $search);
        $mapper->validate($event, ['relations' => true]);

        $this->assertTrue(isset($event->errors()['search']));
    }

    public function testBelongsToRelationValidation()
    {
        $mapper = \test_spot_mapper('SpotTest\Entity\Post');
        $author = new SpotTest\Entity\Author();
        $post = $mapper->build([]);
        $post->relation('author', $author);
        $mapper->validate($post, ['relations' => true]);

        $this->assertTrue(isset($post->errors()['author']));
    }

    public function testHasManyRelationValidation()
    {
        $mapper = \test_spot_mapper('SpotTest\Entity\Post');
        $comment = new SpotTest\Entity\Post\Comment();
        $post = $mapper->build([]);
        $post->relation('comments', new \Spot\Entity\Collection([$comment]));
        $mapper->validate($post, ['relations' => true]);

        $this->assertTrue(isset($post->errors()['comments'][0]));
    }

    public function testHasManyThroughRelationValidation()
    {
        $mapper = \test_spot_mapper('SpotTest\Entity\Post');
        $tag = new SpotTest\Entity\Tag();
        $post = $mapper->build([]);
        $post->relation('tags', new \Spot\Entity\Collection([$tag]));
        $mapper->validate($post, ['relations' => true]);

        $this->assertTrue(isset($post->errors()['tags'][0]));
    }
}
