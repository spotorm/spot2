<?php
namespace SpotTest;

/**
 * @package Spot
 */
class Collection extends \PHPUnit_Framework_TestCase
{
    private static $entities = ['Tag'];

    public static function setupBeforeClass()
    {
        foreach (self::$entities as $entity) {
            test_spot_mapper('\SpotTest\Entity\\' . $entity)->migrate();
        }

        $tagCount = 3;

        // Create some tags
        $tags = array();
        $tagMapper = test_spot_mapper('SpotTest\Entity\Tag');
        for ($i = 1; $i <= $tagCount; $i++) {
            $tags[] = $tagMapper->create([
                'name'  => "Title {$i}"
            ]);
        }
    }

    public static function tearDownAfterClass()
    {
        foreach (self::$entities as $entity) {
            test_spot_mapper('\SpotTest\Entity\\' . $entity)->dropTable();
        }
    }

    public function testMergeIntersecting()
    {
        $mapper = test_spot_mapper('SpotTest\Entity\Tag');

        // Fetch 3 entries
        $tags = $mapper->all()->execute();

        // Fetch 1 tag we already have
        $newTags = $mapper->where(['name' => 'Title 1'])->execute();

        // Check counts before merge
        $this->assertEquals(3, count($tags));
        $this->assertEquals(1, count($newTags));

        // Merge new tag in and expect count to remain the same (duplicate tag)
        $tags->merge($newTags);
        $this->assertEquals(3, count($tags));
    }
}
