<?php
namespace Spot\Relation;

use Spot\EntityInterface;
use Spot\Entity\Collection;

/**
 * NestedRelation
 *
 * Used for eager-loading multilevel relations
 *
 * @package Spot
 */
class NestedRelation extends RelationAbstract
{
    /**
     * @var RelationAbstract
     */
    protected $relationObject;

    /**
     * @var RelationAbstract
     */
    protected $parentRelationObject;

    /**
     * @var string
     */
    protected $parentRelationName;

    /**
     * @var string
     */
    protected $relationName;

    /*
     * @var Collection
     */
    protected $relationCollection;

    /**
     * @var array
     */
    protected $relationCollectionReversedIdentities = [];

    /**
     * NestedRelation constructor
     *
     * @param RelationAbstract $relationObject
     * @param RelationAbstract $parentRelationObject
     */
    public function __construct (
        RelationAbstract $relationObject,
        RelationAbstract $parentRelationObject
    )
    {
        $this->relationObject = $relationObject;
        $this->parentRelationObject = $parentRelationObject;
    }

    /**
     * Set identity values from given collection
     *
     * @param \Spot\Entity\Collection
     */
    public function identityValuesFromCollection(Collection $collection)
    {
        // This method is not used here
    }

    /**
     * Build query object
     *
     * @return \Spot\Query
     */
    protected function buildQuery()
    {
        return $this->relationObject->buildQuery();
    }

    /**
     * Map relation results to original collection of entities
     *
     * @param string Relation name
     * @param \Spot\Entity\Collection Collection of original entities to map results of query back to
     *
     * @return \Spot\Entity\Collection
     *
     * @throws \Exception
     */
    public function eagerLoadOnCollection($relationName, Collection $collection)
    {
        $relationNames = explode('.', $relationName);
        $this->relationName = array_pop($relationNames);
        $this->parentRelationName = array_pop($relationNames);

        $this->createRelationCollection($collection);

        $filledRelationCollection = $this->relationObject->eagerLoadOnCollection($this->relationName, $this->relationCollection);

        if (!empty($this->relationCollectionReversedIdentities)) {
            $result = [];
            foreach ($filledRelationCollection as $ent) {
                $result[$this->relationCollectionReversedIdentities[$ent->getId()]][] = $ent;
            }
            $filledRelationCollection = $result;
        }

        return $this->addFilledCollection($collection, $filledRelationCollection);
    }

    /**
     * @param $collection
     * @param $filledRelationCollection
     *
     * @return mixed
     */
    public function addFilledCollection($collection, $filledRelationCollection)
    {
        $parentCollection = $collection;
        if ($this->parentRelationObject instanceof NestedRelation) {
            $parentCollection = $this->parentRelationObject->relationCollection;
        }

        foreach($parentCollection as $entity) {
            $entity->relation($this->parentRelationName, $filledRelationCollection[$entity->getId()]);
        }

        if ($this->parentRelationObject instanceof NestedRelation) {
            return $this->parentRelationObject->addFilledCollection($collection, $parentCollection);
        }

        return $parentCollection;
    }

    /**
     * @param Collection $collection
     */
    public function createRelationCollection(Collection $collection)
    {
        if ($this->parentRelationObject instanceof NestedRelation) {
            $collection = $this->parentRelationObject->relationCollection;
        }
        $relationCollection = [];
        $resultsIdentities = [];
        foreach($collection as $entity) {
            $relatedEntity = $entity->relation($this->parentRelationName);
            if ($relatedEntity instanceof Collection) {
                foreach ($relatedEntity as $childEntity) {
                    $relationCollection[] = $childEntity;
                    $resultsIdentities[] = $childEntity->getId();
                    $this->relationCollectionReversedIdentities[$childEntity->getId()] = $entity->getId();
                }
            } else {
                $relationCollection[$entity->getId()] = $relatedEntity;
                $resultsIdentities[] = $relatedEntity->getId();
            }
        }
        $this->relationCollection = new Collection($relationCollection, $resultsIdentities);
        $this->relationObject->identityValuesFromCollection($this->relationCollection);
    }


    /**
     * Save related entities
     *
     * @param EntityInterface $entity Entity to save relation from
     * @param string $relationName Name of the relation to save
     * @param array $options Options to pass to the mappers
     *
     * @return boolean
     *
     * @throws \Exception
     */
    public function save(EntityInterface $entity, $relationName, $options = [])
    {
        return true; // Not needed to be implemented
    }
}
