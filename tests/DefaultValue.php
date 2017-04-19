<?php
/**
 * @package Spot
 */
class Test_DefaultValue extends PHPUnit_Framework_TestCase
{
    private static $entities = ['DefaultValue'];

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

    public function testDefaultValue()
    {
        $mapper = test_spot_mapper('SpotTest\Entity\DefaultValue');

        $entity = new SpotTest\Entity\DefaultValue();
        $this->assertEquals(2, $entity->data1);
        $this->assertEquals(3, $entity->data2);
        $this->assertEquals(5, $entity->data3);
    }
}
