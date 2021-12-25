<?php
namespace SpotTest\Cases;

/**
 * @package Spot
 */
class CollectionTest extends \PHPUnit\Framework\TestCase
{
    private static $entities = ['Tag'];

    public static function setupBeforeClass(): void
    {
        foreach (self::$entities as $entity) {
            \test_spot_mapper('\SpotTest\Cases\Entity\\' . $entity)->migrate();
        }

        $tagCount = 3;

        // Create some tags
        $tags = array();
        $tagMapper = \test_spot_mapper('SpotTest\Entity\Tag');
        for ($i = 1; $i <= $tagCount; $i++) {
            $tags[] = $tagMapper->create([
                'name'  => "Title {$i}"
            ]);
        }
    }

    public static function tearDownAfterClass(): void
    {
        foreach (self::$entities as $entity) {
            \test_spot_mapper('\SpotTest\Cases\Entity\\' . $entity)->dropTable();
        }
    }

    public function testMergeIntersecting()
    {
        $mapper = \test_spot_mapper('SpotTest\Entity\Tag');

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

    public function testCollectionJsonSerialize()
    {
        $mapper = \test_spot_mapper('SpotTest\Entity\Tag');

        $tags = $mapper->all()->execute();

        $data = json_encode($tags->toArray());
        $json = json_encode($tags);

        $this->assertSame($data, $json);
    }

    public function testQueryCallsCollectionMethods()
    {
        $mapper = \test_spot_mapper('SpotTest\Entity\Tag');

        // Method on Spot\Entity\Collection being called through Spot\Query object
        $tagsArray = $mapper->all()->resultsIdentities();

        $this->assertSame([1, 2, 3], $tagsArray);
    }

    public function testQueryCallsCollectionMethodsWithArguments()
    {
        $mapper = \test_spot_mapper('SpotTest\Entity\Tag');

        // Method on Spot\Entity\Collection being called through Spot\Query object
        $matchingTag = $mapper->all()->filter(function ($tag) {
            return $tag->id === 1;
        });

        $this->assertSame(1, $matchingTag[0]->id);
    }
}
