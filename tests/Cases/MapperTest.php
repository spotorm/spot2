<?php
namespace SpotTest\Cases;

/**
 * @package Spot
 */
class MapperTest extends \PHPUnit\Framework\TestCase
{
    public function testGetGenericMapper()
    {
        $mapper = \test_spot_mapper('SpotTest\Entity\Author');
        $this->assertInstanceOf('Spot\Mapper', $mapper);
    }

    public function testGetCustomEntityMapper()
    {
        $mapper = \test_spot_mapper('SpotTest\Entity\Event');
        $this->assertInstanceOf(Entity\Event::mapper(), $mapper);

        $query = $mapper->testQuery();
        $this->assertInstanceOf('Spot\Query', $query);
    }
}
