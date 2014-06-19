<?php
namespace SpotTest;

/**
 * @package Spot
 */
class Locator extends \PHPUnit_Framework_TestCase
{
    public function testGetConfig()
    {
        $cfg = new \Spot\Config();
        $spot = new \Spot\Locator($cfg);
        $this->assertInstanceOf('Spot\Config', $spot->config());
    }

    public function testGetMapper()
    {
        $cfg = new \Spot\Config();
        $spot = new \Spot\Locator($cfg);
        $this->assertInstanceOf('Spot\Mapper', $spot->mapper('SpotTest\Entity\Post'));
    }
}
