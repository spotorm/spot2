<?php
namespace SpotTest;
use SpotTest\Entity\Legacy;

/**
 * @package Spot
 */
class FieldAlias extends \PHPUnit_Framework_TestCase
{
    public static $legacyTable;

    public static function setupBeforeClass()
    {
        self::$legacyTable = new \SpotTest\Entity\Legacy();
        foreach (['Legacy', 'PolymorphicComment'] as $entity) {
            test_spot_mapper('SpotTest\Entity\\' . $entity)->migrate();
        }
    }

    public static function tearDownAfterClass()
    {
        foreach (['Legacy', 'PolymorphicComment'] as $entity) {
            test_spot_mapper('\SpotTest\Entity\\' . $entity)->dropTable();
        }
    }

    public function testLegacySelectFieldsAreAliases()
    {
        $mapper = test_spot_mapper('SpotTest\Entity\Legacy');
        $query = $mapper->select()->noQuote()->where(['number' => 2, 'name' => 'legacy_crud']);
        $this->assertEquals("SELECT * FROM test_legacy  WHERE test_legacy." . self::$legacyTable->getNumberFieldColumnName() ." = ? AND test_legacy." . self::$legacyTable->getNameFieldColumnName() . " = ?", $query->toSql());
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
        $this->assertEquals("SELECT * FROM test_legacy  WHERE test_legacy." . self::$legacyTable->getNameFieldColumnName() . " = ? GROUP BY test_legacy." . self::$legacyTable->getIdFieldColumnName(), $query->toSql());
    }

    // Insert
    public function testLegacyInsert()
    {
        $legacy = new Legacy();
        $legacy->name = 'Something Here';
        $legacy->number = 5;

        $mapper = test_spot_mapper('SpotTest\Entity\Legacy');
        $mapper->save($legacy);
        return $legacy;
    }

    /**
     * @depends testLegacyInsert
     */
    public function testLegacyUpdate(Legacy $legacy)
    {
        $legacy->name = 'Something ELSE Here';
        $legacy->number = 6;

        $mapper = test_spot_mapper('SpotTest\Entity\Legacy');
        $mapper->save($legacy);
    }

    /**
     * @depends testLegacyInsert
     */
    public function testLegacyEntityFieldMapping(Legacy $legacy)
    {
        $mapper = test_spot_mapper('SpotTest\Entity\Legacy');
        $savedLegacyItem = $mapper->first();

        $this->assertEquals($legacy->name, $savedLegacyItem->name);
        $this->assertEquals($legacy->number, $savedLegacyItem->number);
    }

    /**
     * @depends testLegacyInsert
     */
    public function testLegacyRelations(Legacy $legacy)
    {
        // New Comment
        $commentMapper = test_spot_mapper('SpotTest\Entity\PolymorphicComment');
        $comment = new \SpotTest\Entity\PolymorphicComment([
            'item_id' => $legacy->id,
            'item_type' => 'legacy',
            'name' => 'Testy McTesterpants',
            'email' => 'tester@chester.com',
            'body' => '<p>Comment Text</p>'
        ]);
        $commentMapper->save($comment);

        $this->assertInstanceOf('Spot\Relation\HasMany', $legacy->polymorphic_comments);
        $this->assertEquals(count($legacy->polymorphic_comments), 1);
    }
}
