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
     * Constructor Method
     * 
     * @param \Spot\Config $config
     */
    public function __construct(Config $config)
    {
        $this->config = $config;
    }

    /**
     * Get config class mapper was instantiated with
     *
     * @return \Spot\Config
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
     * @param  string      $entityName Name of Entity object to load mapper for
     * @return \Spot\Mapper
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
            $this->mapper[$entityName] = new $mapper($this, $entityName);
        }

        return $this->mapper[$entityName];
    }
}
