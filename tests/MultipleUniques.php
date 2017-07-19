<?php
/**
 * @package Spot
 */
class Test_MultipleUniques extends PHPUnit_Framework_TestCase
{
    private static $entities = ['MultipleUniques'];

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

    public function testMultipleUniques()
    {
        $mapper = test_spot_mapper('SpotTest\Entity\MultipleUniques');

        $entity1 = new SpotTest\Entity\MultipleUniques([
            'data1' => 'data1_test1',
            'data2' => 1,
            'data3' => 'data3_test1'
        ]);
        $mapper->save($entity1);

        $entity2 = new SpotTest\Entity\MultipleUniques([
            'data1' => 'data1_test2',
            'data2' => 2,
            'data3' => 'data3_test2'
        ]);
        $mapper->save($entity2);

        $entity3 = new SpotTest\Entity\MultipleUniques([
            'data1' => 'data1_test3',
            'data2' => 1,
            'data3' => 'data3_test3'
        ]);
        $mapper->save($entity3);

        $entity4 = new SpotTest\Entity\MultipleUniques([
            'data1' => 'data1_test1',
            'data2' => 4,
            'data3' => 'data3_test4'
        ]);
        $mapper->save($entity4);

        $entity5 = new SpotTest\Entity\MultipleUniques([
            'data1' => 'data1_test5',
            'data2' => 1,
            'data3' => 'data3_test1'
        ]);
        $mapper->save($entity5);

        $entity6 = new SpotTest\Entity\MultipleUniques([
            'data1' => 'data1_test1',
            'data2' => 1,
            'data3' => 'data3_test6'
        ]);
        $mapper->save($entity6);

        $entity7 = new SpotTest\Entity\MultipleUniques([
            'data1' => 'data1_test2',
            'data2' => 1,
            'data3' => 'data3_test2'
        ]);
        $mapper->save($entity7);

        $entity8 = new SpotTest\Entity\MultipleUniques([
            'data1' => 'data1_test1',
            'data2' => 1,
            'data3' => 'data3_test4'
        ]);
        $mapper->save($entity8);

        $this->assertFalse($entity1->hasErrors());
        $this->assertFalse($entity2->hasErrors());
        $this->assertFalse($entity3->hasErrors());
        $this->assertFalse($entity4->hasErrors());
        $this->assertFalse($entity5->hasErrors());
        $this->assertTrue($entity6->hasErrors());
        $this->assertContains("Uniq1 'data1_test1-1' is already taken.", $entity6->errors('uniq1'));
        $this->assertTrue($entity7->hasErrors());
        $this->assertContains("Uniq2 'data1_test2-data3_test2' is already taken.", $entity7->errors('uniq2'));
        $this->assertTrue($entity8->hasErrors());
        $this->assertContains("Uniq1 'data1_test1-1' is already taken.", $entity8->errors('uniq1'));
        $this->assertContains("Uniq2 'data1_test1-data3_test4' is already taken.", $entity8->errors('uniq2'));
    }
}