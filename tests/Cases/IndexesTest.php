<?php
namespace SpotTest\Cases;

/**
 * @package Spot
 */
class IndexesTest extends \PHPUnit\Framework\TestCase
{
    private static $entities = ['Zip'];

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

    public function testUniqueCompoundIndexDuplicateCausesValidationError()
    {
        $zipMapper = \test_spot_mapper('\SpotTest\Cases\Entity\Zip');

        $data = [
            'code'  => '12345',
            'city'  => 'Testville',
            'state' => 'NY',
            'lat'   => 1,
            'lng'   => 2
        ];

        $zip1 = $zipMapper->create($data);
        $zip2 = $zipMapper->build($data);
        $zipMapper->save($zip2);

        $this->assertEmpty($zip1->errors());
        $this->assertNotEmpty($zip2->errors());
    }

    public function testUniqueCompoundIndexNoValidationErrorWhenDataDifferent()
    {
        $zipMapper = \test_spot_mapper('\SpotTest\Cases\Entity\Zip');

        $data = [
            'code'  => '23456',
            'city'  => 'Testville',
            'state' => 'NY',
            'lat'   => 1,
            'lng'   => 2
        ];

        $zip1 = $zipMapper->create($data);

        // Make data slightly different on unique compound index
        $data2 = array_merge($data, ['city' => 'Testville2']);
        $zip2 = $zipMapper->create($data2);

        $this->assertEmpty($zip1->errors());
        $this->assertEmpty($zip2->errors());
    }
}
