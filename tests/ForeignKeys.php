<?php
namespace SpotTest;

/**
 * @package Spot
 */
class ForeignKeys extends \PHPUnit_Framework_TestCase
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

    public function testForeignKeyMigration()
    {
        $mapper = test_spot_mapper('\SpotTest\Entity\Post');
        $entity = $mapper->entity();
        $table = $entity::table();
        $schemaManager = $mapper->connection()->getSchemaManager();
        $foreignKeys = $schemaManager->listTableForeignKeys($table);

        $this->assertEquals(1, count($foreignKeys));
    }
}
