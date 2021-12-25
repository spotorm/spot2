<?php
namespace SpotTest\Cases;

/**
 * @package Spot
 */
class ForeignKeysTest extends \PHPUnit\Framework\TestCase
{
    private static $entities = ['Post', 'Author', 'RecursiveEntity'];

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

    public function testForeignKeyMigration()
    {
        $mapper = \test_spot_mapper('\SpotTest\Cases\Entity\Post');
        $entity = $mapper->entity();
        $table = $entity::table();
        $schemaManager = $mapper->connection()->getSchemaManager();
        $foreignKeys = $schemaManager->listTableForeignKeys($table);

        $this->assertEquals(1, count($foreignKeys));
    }
}
