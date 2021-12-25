<?php
namespace SpotTest\Cases;

/**
 * @package Spot
 */
class DefaultValueTest extends \PHPUnit\Framework\TestCase
{
    private static $entities = ['DefaultValue'];

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

    public function testDefaultValue()
    {
        $mapper = \test_spot_mapper('SpotTest\Entity\DefaultValue');

        $entity = new SpotTest\Entity\DefaultValue();
        $this->assertEquals(2, $entity->data1);
        $this->assertEquals(3, $entity->data2);
        $this->assertEquals(5, $entity->data3);
    }
}
