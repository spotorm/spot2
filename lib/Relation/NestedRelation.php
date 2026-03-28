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

    /**
     * @var string
     */
    protected $entityName;

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
        $this->entityName = $relationObject->entityName();
    }

    /**
     * Set identity values from given collection
     *
     * @param \Spot\Entity\Collection
     */
    public function identityValuesFromCollection(Collection $collection)
    {
        throw new \BadMethodCallException("This method is not implemented in NestedRelation class");
    }

    /**
     * Build query object
     *
     */
    protected function buildQuery()
    {
        throw new \BadMethodCallException("This method is not implemented in NestedRelation class");
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
                if (isset($this->relationCollectionReversedIdentities[$ent->primaryKey()])) {
                    $result[$this->relationCollectionReversedIdentities[$ent->primaryKey()]][] = $ent;
                }
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
            if (isset($filledRelationCollection[$entity->primaryKey()])) {
                $entity->relation($this->parentRelationName, $filledRelationCollection[$entity->primaryKey()]);
            }
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
                    $resultsIdentities[] = $childEntity->primaryKey();
                    $this->relationCollectionReversedIdentities[$childEntity->primaryKey()] = $entity->primaryKey();
                }
            } else {
                $relationCollection[$entity->primaryKey()] = $relatedEntity;
                $resultsIdentities[] = $relatedEntity->primaryKey();
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
     * @throws \Exception
     */
    public function save(EntityInterface $entity, $relationName, $options = [])
    {
        throw new \BadMethodCallException("This method is not implemented in NestedRelation class");
    }
}
