<?php
namespace SpotTest\Cases;

/**
 * @package Spot
 */
class SchemaQuerySqlTest extends \PHPUnit\Framework\TestCase
{
    public static function setupBeforeClass(): void
    {
        foreach (['Schema\Test'] as $entity) {
            \test_spot_mapper('SpotTest\Entity\\' . $entity)->migrate();
        }

        // Insert dummy data
		$entities = [];
        for ($i = 1; $i <= 10; $i++) {
            $entities[] = \test_spot_mapper('SpotTest\Entity\Schema\Test')->insert([
                'index' => $i % 5,
				'unique' => $i * 2
            ]);
        }
    }

    public static function tearDownAfterClass(): void
    {
        foreach (['Schema\Test'] as $entity) {
            \test_spot_mapper('\SpotTest\Entity\\' . $entity)->dropTable();
        }
    }

	// Filtering
	public function testWhere()
    {
        $mapper = \test_spot_mapper('SpotTest\Entity\Schema\Test');
        $query = $mapper->where(['unique' => 6])->noQuote();
        $this->assertEquals("SELECT * FROM spot_test.test_schema_test WHERE spot_test.test_schema_test.unique = ?", $query->toSql());
    }

	// Ordering
    public function testOrderBy()
    {
        $mapper = \test_spot_mapper('SpotTest\Entity\Schema\Test');
        $query = $mapper->where(['index' => 2])->order(['unique' => 'ASC'])->noQuote();
        $this->assertStringContainsString("ORDER BY spot_test.test_schema_test.unique ASC", $query->toSql());
        $this->assertEquals("SELECT * FROM spot_test.test_schema_test WHERE spot_test.test_schema_test.index = ? ORDER BY spot_test.test_schema_test.unique ASC", $query->toSql());
    }

	// Identifier quoting
    public function testQuoting()
    {
        $mapper = \test_spot_mapper('SpotTest\Entity\Schema\Test');

        $expected = str_replace(
            '`',
            $mapper->connection()->getDatabasePlatform()->getIdentifierQuoteCharacter(),
            'SELECT * FROM `spot_test`.`test_schema_test` WHERE `spot_test`.`test_schema_test`.`index` >= ?'
        );

        $query = $mapper->where(['index >=' => 2])->toSql();

        $this->assertEquals(
            $expected,
            $query
        );
    }
}
