<?php
namespace SpotTest;

/**
 * @package Spot
 */
class ForeignKeys extends \PHPUnit_Framework_TestCase
{

    public function testForeignKeyMigration()
    {
        $mapper = test_spot_mapper('\SpotTest\Entity\Post');
        $mapper->migrate();

        $entity = $mapper->entity();
        $table = $entity::table();
        $schemaManager = $mapper->connection()->getSchemaManager();
        $foreignKeys = $schemaManager->listTableForeignKeys($table);

        $this->assertEquals(1, count($foreignKeys));
    }
}
