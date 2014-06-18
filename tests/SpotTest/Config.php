<?php
namespace SpotTest;

/**
 * @package Spot
 */
class Config extends \PHPUnit_Framework_TestCase
{
    public function testAddConnectionWithDSNString()
    {
        $cfg = new \Spot\Config();
        $adapter = $cfg->addConnection('test_mysql', 'mysql://test:password@localhost/test');
        $this->assertInstanceOf('Doctrine\DBAL\Connection', $adapter);
    }

    public function testConfigCanSerialize()
    {
        $cfg = new \Spot\Config();
        $adapter = $cfg->addConnection('test_mysql', 'mysql://test:password@localhost/test');

        $this->assertInternalType('string', serialize($cfg));
    }

    public function testConfigCanUnserialize()
    {
        $cfg = new \Spot\Config();
        $adapter = $cfg->addConnection('test_mysql', 'mysql://test:password@localhost/test');

        $this->assertInstanceOf('\Spot\Config', unserialize(serialize($cfg)));
    }
}
