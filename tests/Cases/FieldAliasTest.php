<?php
namespace SpotTest\Cases;
use SpotTest\Entity\Legacy;

/**
 * @package Spot
 */
class FieldAliasTest extends \PHPUnit\Framework\TestCase
{
    public static $legacyTable;
    private static $entities = ['PolymorphicComment', 'Legacy', 'Post', 'Author'];

    public static function setupBeforeClass(): void
    {
        self::$legacyTable = new \SpotTest\Entity\Legacy();
        foreach (self::$entities as $entity) {
            \test_spot_mapper('SpotTest\Entity\\' . $entity)->migrate();
        }

        $authorMapper = \test_spot_mapper('SpotTest\Entity\Author');
        $author = $authorMapper->build([
            'id' => 1,
            'email' => 'example@example.com',
            'password' => 't00r',
            'is_admin' => false
        ]);
        $result = $authorMapper->insert($author);

        if (!$result) {
            throw new \Exception("Unable to create author: " . var_export($author->data(), true));
        }

        $postMapper = \test_spot_mapper('\SpotTest\Entity\Post');
        $post = $postMapper->build([
            'title' => 'title',
            'body' => '<p>body</p>',
            'status' => 1 ,
            'date_created' => new \DateTime(),
            'author_id' => 1
        ]);
        $result = $postMapper->insert($post);

        if (!$result) {
            throw new \Exception("Unable to create post: " . var_export($post->data(), true));
        }
    }

    public static function tearDownAfterClass(): void
    {
        foreach (self::$entities as $entity) {
            \test_spot_mapper('\SpotTest\Entity\\' . $entity)->dropTable();
        }
    }

    public function testLegacySelectFieldsAreAliases()
    {
        $mapper = \test_spot_mapper('SpotTest\Entity\Legacy');
        $query = $mapper->select()->noQuote()->where(['number' => 2, 'name' => 'legacy_crud']);
        $this->assertEquals("SELECT * FROM test_legacy WHERE test_legacy." . self::$legacyTable->getNumberFieldColumnName() ." = ? AND test_legacy." . self::$legacyTable->getNameFieldColumnName() . " = ?", $query->toSql());
    }

    // Ordering
    public function testLegacyOrderBy()
    {
        $mapper = \test_spot_mapper('SpotTest\Entity\Legacy');
        $query = $mapper->where(['number' => 2])->order(['date_created' => 'ASC'])->noQuote();
        $this->assertStringContainsString("ORDER BY test_legacy." . self::$legacyTable->getDateCreatedColumnName() . " ASC", $query->toSql());
    }
    
    // Ordering by function
    public function testLegacyOrderByFunction()
    {
        $mapper = \test_spot_mapper('SpotTest\Entity\Legacy');
        $query = $mapper->where(['number' => 2])->order(['TRIM(name)' => 'ASC'])->noQuote();
        $this->assertContains("ORDER BY TRIM(test_legacy." . self::$legacyTable->getNameFieldColumnName() . ") ASC", $query->toSql());
    }
    
    // Ordering by complex function
    public function testLegacyOrderByComplexFunction()
    {
        $mapper = \test_spot_mapper('SpotTest\Entity\Legacy');
        if (!DriverSpecificTest::getWeekFunction($mapper)) {
            $this->markTestSkipped('This test is not supported with the current driver.');
        }
        $query = $mapper->where(['number' => 2])->order([DriverSpecificTest::getWeekFunction($mapper, 'date_created') => 'ASC'])->noQuote();
        $this->assertContains("ORDER BY " . DriverSpecificTest::getWeekFunction($mapper, 'test_legacy.' . self::$legacyTable->getDateCreatedColumnName()) . " ASC", $query->toSql());
    }

    // Grouping
    public function testLegacyGroupBy()
    {
        $mapper = \test_spot_mapper('SpotTest\Entity\Legacy');
        $query = $mapper->where(['name' => 'test_group'])->group(['id'])->noQuote();
        $this->assertEquals("SELECT * FROM test_legacy WHERE test_legacy." . self::$legacyTable->getNameFieldColumnName() . " = ? GROUP BY test_legacy." . self::$legacyTable->getIdFieldColumnName(), $query->toSql());
    }
    
    // Grouping by function
    public function testLegacyGroupByFunction()
    {
        $mapper = \test_spot_mapper('SpotTest\Entity\Legacy');
        $query = $mapper->where(['number' => 2])->group(['TRIM(name)'])->noQuote();
        $this->assertEquals("SELECT * FROM test_legacy WHERE test_legacy." . self::$legacyTable->getNumberFieldColumnName() . " = ? GROUP BY TRIM(test_legacy." . self::$legacyTable->getNameFieldColumnName() . ")", $query->toSql());
    }

    // Insert
    public function testLegacyInsert()
    {
        $legacy = new Legacy();
        $legacy->name = 'Something Here';
        $legacy->number = 5;

        $mapper = \test_spot_mapper('SpotTest\Entity\Legacy');
        $mapper->save($legacy);
        return $legacy;
    }

    /**
     * @depends testLegacyInsert
     */
    public function testLegacyEntityToArrayUsesFieldMappings(Legacy $legacy)
    {
        $mapper = \test_spot_mapper('SpotTest\Entity\Legacy');
        $savedLegacyItem = $mapper->first();
        $data = $savedLegacyItem->toArray();

        $this->assertEquals($data['name'], 'Something Here');
        $this->assertEquals($data['number'], 5);
    }

    /**
     * @depends testLegacyInsert
     */
    public function testLegacyUpdate(Legacy $legacy)
    {
        $legacy->name = 'Something ELSE Here';
        $legacy->number = 6;

        $mapper = \test_spot_mapper('SpotTest\Entity\Legacy');
        $mapper->save($legacy);
    }

    /**
     * @depends testLegacyInsert
     */
    public function testLegacyEntityFieldMapping(Legacy $legacy)
    {
        $mapper = \test_spot_mapper('SpotTest\Entity\Legacy');
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
        $commentMapper = \test_spot_mapper('SpotTest\Entity\PolymorphicComment');
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

    public function testFieldAliasMapping() {
        $testId = 2545;
        $testArray = ['testKey' => 'testValue'];

        $legacy = new Legacy();
        $legacy->id = $testId;
        $legacy->name = 'Something Here';
        $legacy->number = 5;
        $legacy->array = $testArray;
        $legacy->arrayAliased = $testArray;

        $mapper = \test_spot_mapper('SpotTest\Entity\Legacy');
        $mapper->save($legacy);

        unset($legacy);
        $legacy = $mapper->get($testId);

        $this->assertEquals($testArray, $legacy->array);
        $this->assertEquals($testArray, $legacy->arrayAliased);
    }
}
