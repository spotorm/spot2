<?php
namespace SpotTest;

/**
 * @package Spot
 */
class FieldAlias extends \PHPUnit_Framework_TestCase
{
    public static $legacyTable;

    public static function setupBeforeClass()
    {
        self::$legacyTable = new \SpotTest\Entity\Legacy();
        foreach (['Legacy'] as $entity) {
            test_spot_mapper('SpotTest\Entity\\' . $entity)->migrate();
        }
    }

    public static function tearDownAfterClass()
    {
        foreach (['Legacy'] as $entity) {
            test_spot_mapper('\SpotTest\Entity\\' . $entity)->dropTable();
        }
    }

    public function testLegacySelectFieldsAreAliases()
    {
        $mapper = test_spot_mapper('SpotTest\Entity\Legacy');
        $query = $mapper->select()->noQuote()->where(['number' => 2, 'name' => 'legacy_crud']);
        $this->assertEquals("SELECT * FROM test_legacy test_legacy WHERE test_legacy." . self::$legacyTable->getNumberFieldColumnName() ." = ? AND test_legacy." . self::$legacyTable->getNameFieldColumnName() . " = ?", $query->toSql());
    }

    // Ordering
    public function testLegacyOrderBy()
    {
        $mapper = test_spot_mapper('SpotTest\Entity\Legacy');
        $query = $mapper->select()->noQuote()->where(['number' => 2])->order(['date_created' => 'ASC']);
        $this->assertContains("ORDER BY test_legacy." . self::$legacyTable->getDateCreatedColumnName() . " ASC", $query->toSql());
    }

    // Grouping
    public function testLegacyGroupBy()
    {
        $mapper = test_spot_mapper('SpotTest\Entity\Legacy');
        $query = $mapper->select()->noQuote()->where(['name' => 'test_group'])->group(['id']);
        $this->assertEquals("SELECT * FROM test_legacy test_legacy WHERE test_legacy." . self::$legacyTable->getNameFieldColumnName() . " = ? GROUP BY test_legacy." . self::$legacyTable->getIdFieldColumnName(), $query->toSql());
    }
}
