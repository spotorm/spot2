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
     *  Constructor Method
     */
    public function __construct(Config $config)
    {
        $this->config = $config;
    }

    /**
     * Get config class mapper was instantiated with
     *
     * @return Spot\Config
     */
    public function config()
    {
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
