<?php
namespace SpotTest;

/**
 * @package Spot
 */
class Mapper extends \PHPUnit_Framework_TestCase
{
    public function testGetGenericMapper()
    {
        $mapper = test_spot_mapper('SpotTest\Entity\Author');
        $this->assertInstanceOf('Spot\Mapper', $mapper);
    }

    public function testGetCustomEntityMapper()
    {
        $mapper = test_spot_mapper('SpotTest\Entity\Event');
        $this->assertInstanceOf(Entity\Event::mapper(), $mapper);

        $query = $mapper->testQuery();
        $this->assertInstanceOf('Spot\Query', $query);
    }

    /**
     * entityManager() return the current entity if $entityName is empty
     */
    public function testEntityManagerReturnTheCurrentEntityIfEntityNameIsEmpty()
    {
        $mapper = test_spot_mapper('SpotTest\Entity\NotNullOverride');
        $manager = $mapper->entityManager();
        $currentEntity = $mapper->entity();
        $reflection = new \ReflectionClass(get_class($manager));
        $privateProperty = $reflection->getProperty('entityName');
        $privateProperty->setAccessible(true);
        $privateValue = $privateProperty->getValue($manager);
        $this->assertEquals($currentEntity, $privateValue);
    }


    /**
     * entityManager() return the same entity if we passed as parameter
     */
    public function testEntityManagerReturnTheSameEntityIfWePassedAsParameter()
    {
        $mapper = test_spot_mapper('SpotTest\Entity\NotNullOverride');
        test_spot_mapper('\SpotTest\Entity\Author');
        $manager = $mapper->entityManager('\SpotTest\Entity\Author');
        $currentEntity = $mapper->entity();
        $reflection = new \ReflectionClass(get_class($manager));
        $privateProperty = $reflection->getProperty('entityName');
        $privateProperty->setAccessible(true);
        $privateValue = $privateProperty->getValue($manager);
        $this->assertNotEquals($currentEntity, $privateValue);
    }


    /**
     * entityManager() return will return different entity from current entity if we passed as parameter
     */
    public function testEntityManagerReturnWillReturnDifferentEntityFromCurrentEntityIfWePassedAsParameter()
    {
        $mapper = test_spot_mapper('SpotTest\Entity\NotNullOverride');
        test_spot_mapper('\SpotTest\Entity\Author');
        $manager = $mapper->entityManager('\SpotTest\Entity\Author');
        $reflection = new \ReflectionClass(get_class($manager));
        $privateProperty = $reflection->getProperty('entityName');
        $privateProperty->setAccessible(true);
        $privateValue = $privateProperty->getValue($manager);
        $this->assertEquals('\SpotTest\Entity\Author', $privateValue);
    }


    /**
     * entityManager() always return instance of Entity\Manager
     */
    public function testEntityManagerAlwaysReturnInstanceOfEntityManager()
    {
        $mapper = test_spot_mapper('\SpotTest\Entity\NotNullOverride');
        test_spot_mapper('\SpotTest\Entity\Author');
        $manager = $mapper->entityManager('\SpotTest\Entity\Author');
        $managerCurrent = $mapper->entityManager();
        $this->assertInstanceOf('\Spot\Entity\Manager', $manager);
        $this->assertInstanceOf('\Spot\Entity\Manager', $managerCurrent);
    }

}
