<?php
namespace SpotTest;

/**
 * @package Spot
 */
class Config extends \PHPUnit_Framework_TestCase
{
    public function testAddConnectionSqlite()
    {
        $cfg = new \Spot\Config();
        $dsnp = $cfg->parseDsn('sqlite::memory:');
        $this->assertEquals('pdo_sqlite', $dsnp['driver']);

        $adapter = $cfg->addConnection('test_sqlite', 'sqlite::memory:');
        $this->assertInstanceOf('Doctrine\DBAL\Connection', $adapter);
    }

    public function testAddSqliteConnectionWithDSNString()
    {
        $cfg = new \Spot\Config();
        $adapter = $cfg->addConnection('test_sqlite', 'sqlite::memory:');
        $this->assertInstanceOf('Doctrine\DBAL\Connection', $adapter);
    }

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

    public function testAddConnectionWithArray()
    {
        $cfg = new \Spot\Config();
        $dbalArray = [
            'dbname' => 'spot_test',
            'user' => 'test',
            'password' => 'password',
            'host' => 'localhost',
            'driver' => 'pdo_mysql'
        ];
        $adapter = $cfg->addConnection('test_array', $dbalArray);
        $this->assertInstanceOf('Doctrine\DBAL\Connection', $adapter);
    }
}
