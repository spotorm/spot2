<?php
namespace SpotTest;

/**
 * @package Spot
 */
class Manager extends \PHPUnit_Framework_TestCase
{
    public function testNotnullOverride()
    {
        $mapper = test_spot_mapper('SpotTest\Entity\NotNullOverride');
        $manager = $mapper->entityManager();
        $fields = $manager->fields();

        $this->assertTrue($fields['data1']['notnull']); // Should default to true
        $this->assertTrue($fields['data2']['notnull']); // Should override to true
        $this->assertFalse($fields['data3']['notnull']); // Should override to false
    }
}
