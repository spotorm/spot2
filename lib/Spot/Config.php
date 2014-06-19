<?php
namespace Spot;

/**
 * @package Spot
 */
class Config implements \Serializable
{
    protected $_defaultConnection;
    protected $_connections = array();

    /**
     * Add database connection
     *
     * @param string $name Unique name for the connection
     * @param string $dsn DSN string for this connection
     * @param boolean $defaut Use this connection as the default? The first connection added is automatically set as the default, even if this flag is false.
     *
     * @return Doctrine\DBAL\Connection
     * @throws Spot\Exception
     */
    public function addConnection($name, $dsn, $default = false)
    {
        // Connection name must be unique
        if(isset($this->_connections[$name])) {
            throw new Exception("Connection for '" . $name . "' already exists. Connection name must be unique.");
        }

        $connectionParams = $this->parseDsn($dsn);
        if($connectionParams === false) {
            throw new Exception("Unable to parse given DSN string");
        }

        $config = new \Doctrine\DBAL\Configuration();
        $connection = \Doctrine\DBAL\DriverManager::getConnection($connectionParams, $config);

        // Set as default connection?
        if(true === $default || null === $this->_defaultConnection) {
            $this->_defaultConnection = $name;
        }

        // Store connection and return adapter instance
        $this->_connections[$name] = $connection;
        return $connection;
    }

    /**
     * Get connection by name
     *
     * @param string $name Unique name of the connection to be returned
     * @return Doctrine\DBAL\Connection Spot adapter instance
     * @throws Spot\Exception
     */
    public function connection($name = null)
    {
        if ($name === null) {
            return $this->defaultConnection();
        }

        // Connection name must be unique
        if (!isset($this->_connections[$name])) {
            return false;
        }

        return $this->_connections[$name];
    }

    /**
     * Get default connection
     *
     * @return Spot_Adapter_Interface Spot adapter instance
     * @throws Spot_Exception
     */
    public function defaultConnection()
    {
        if (!isset($this->_connections[$this->_defaultConnection])) {
            throw new Exception("No database connection specified! Please add at least one database connection!");
        }
        return $this->_connections[$this->_defaultConnection];
    }

    /**
     * Parse DSN string
     *
     * @return array|false of parsed DSN parts
     */
    public function parseDsn($dsn, $type = null)
    {
        $dsnRegex = '/^(?P<user>\w+)(:(?P<password>\w+))?@(?P<host>[.\w]+)(:(?P<port>\d+))?\/(?P<dbname>\w+)$/im';

        // DSN prefixed with database type
        if(strpos($dsn, '://') !== false) {
            list($type, $dsn) = explode('://', $dsn);
        }

        $result = [
            'driver' => 'pdo_' . $type,
            'user' => '',
            'password' => '',
            'host' => 'localhost',
            'port' => 3306,
            'dbname' => ''
        ];

        if (strlen($dsn) == 0) {
            return false;
        }

        if (!preg_match($dsnRegex, $dsn, $matches)) {
            return false;
        }

        if (count($matches) == 0) {
            return false;
        }

        foreach ($result as $key => $value) {
            if (array_key_exists($key, $matches) and !empty($matches[$key])) {
                $result[$key] = $matches[$key];
            }
        }

        return $result;
    }

    /**
     * Default serialization behavior is to not attempt to serialize stored
     * adapter connections at all (thanks @TheSavior re: Issue #7)
     */
    public function serialize()
    {
        return serialize(array());
    }

    public function unserialize($serialized)
    {
    }
}
