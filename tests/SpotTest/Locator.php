<?php
namespace SpotTest;

/**
 * @package Spot
 */
class Locator extends \PHPUnit_Framework_TestCase
{
    public function testGetConfig()
    {
        $spot = \Spot\Locator::getInstance();
        $this->assertInstanceOf('Spot\Config', $spot->config());
    }

    public function testGetMapper()
    {
        $spot = \Spot\Locator::getInstance();
        $this->assertInstanceOf('Spot\Mapper', $spot->mapper('SpotTest\Entity\Post'));
    }
}
