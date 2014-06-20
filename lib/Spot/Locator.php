<?php
namespace Spot;

/**
 * @package Spot
 */
class Locator
{
    protected $config;
    protected $mapper = [];

    /**
     * Constructor Method - private to enforce singleton with getInstance()
     */
    private function __construct() { }

    /**
     * Singleton instance
     */
    public static function getInstance()
    {
        static $self;
        if (empty($self)) {
            $self = new static();
        }
        return $self;
    }

    /**
     * Get config class mapper was instantiated with
     *
     * @return Spot\Config
     */
    public function config($cfg = null)
    {
        if ($cfg !== null) {
            $this->config = $cfg;
        }
        if (empty($this->config)) {
            throw new Exception("Config object must be set with \$spot->config(<\Spot\Config object>)");
        }
        return $this->config;
    }

    /**
     * Get mapper for specified entity
     *
     * @return Spot\Mapper
     */
    public function mapper($entityName)
    {
        if (!isset($this->mapper[$entityName])) {
            // Get custom mapper, if set
            $mapper = $entityName::mapper();
            // Fallback to generic mapper
            if ($mapper === false) {
                $mapper = 'Spot\Mapper';
            }
            $this->mapper[$entityName] = new $mapper($this->config(), $entityName);
        }
        return $this->mapper[$entityName];
    }
}
