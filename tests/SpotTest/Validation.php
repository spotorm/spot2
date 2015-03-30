<?php
/**
 * @package Spot
 */
class Test_Validation extends PHPUnit_Framework_TestCase
{
    private static $entities = ['Author'];

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

    public function tearDown()
    {
        $mapper = test_spot_mapper('SpotTest\Entity\Author');
        $mapper->truncateTable();
    }

    public function testRequiredField()
    {
        $mapper = test_spot_mapper('SpotTest\Entity\Author');

        $entity = new SpotTest\Entity\Author([
            'is_admin' => true
        ]);
        $mapper->save($entity);

        $this->assertTrue($entity->hasErrors());
        $this->assertContains("Email is required", $entity->errors('email'));
    }

    public function testUniqueField()
    {
        $mapper = test_spot_mapper('SpotTest\Entity\Author');

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

    public function testEmail()
    {
        $mapper = test_spot_mapper('SpotTest\Entity\Author');

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
        $mapper = test_spot_mapper('SpotTest\Entity\Author');

        $entity = new SpotTest\Entity\Author([
            'email' => 't@t',
            'password' => 'test'
        ]);
        $mapper->save($entity);

        $this->assertTrue($entity->hasErrors());
        $this->assertContains("Email must be longer than 4", $entity->errors('email'));
    }

    public function testDisabledValidation()
    {
        $mapper = test_spot_mapper('SpotTest\Entity\Author');

        $entity = new SpotTest\Entity\Author([
            'email' => 't@t',
            'password' => 'test'
        ]);
        $mapper->save($entity, ['validate' => false]);

        $this->assertFalse($entity->hasErrors());
    }
}
