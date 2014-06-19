<?php
namespace Spot;

/**
 * @package Spot
 */
class Locator
{
    protected $config;
    protected $mapper = [];
    protected $entityManager = [];

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
            $mapper = $this->entityManager($entityName)->mapper();
            // Fallback to generic mapper
            if ($mapper === false) {
                $mapper = 'Spot\Mapper';
            }
            $this->mapper[$entityName] = new $mapper($this->config(), $entityName);
        }
        return $this->mapper[$entityName];
    }

    /**
     * Entity manager class for storing information and meta-data about entities
     */
    public function entityManager($entityName)
    {
        if (!isset($this->entityManager[$entityName])) {
            $this->entityManager[$entityName] = new Entity\Manager($entityName);
        }
        return $this->entityManager[$entityName];
    }
}
