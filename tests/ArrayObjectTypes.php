<?php

namespace SpotTest;

/**
 * @package Spot
 */
class ArrayObjectTypes extends \PHPUnit_Framework_TestCase
{

    /**
     * The basic entity for these tests
     * @return \SpotTest\Entity\ArrayObjectType
     */
    private function getEntity()
    {    
        $entity = new \SpotTest\Entity\ArrayObjectType([
            'fld_array' => ['value' => 'original'],
            'fld_simple_array' => ['value' => 'original'],
            'fld_json_array' => ['value' => 'original'],
            'fld_object' => (object) ['value' => 'original']
        ]);

        return $entity;
    }

    public function testArray()
    {
        $entity = $this->getEntity();
        $this->assertFalse($entity->isModified('fld_array'));
        $entity->fld_array['value'] = 'modified';
        $this->assertTrue($entity->isModified('fld_array'));
    }

    public function testSimpleArray()
    {
        $entity = $this->getEntity();
        $this->assertFalse($entity->isModified('fld_simple_array'));
        $entity->fld_simple_array['value'] = 'modified';
        $this->assertTrue($entity->isModified('fld_simple_array'));
    }

    public function testJsonArray()
    {
        $entity = $this->getEntity();
        $this->assertFalse($entity->isModified('fld_json_array'));
        $entity->fld_json_array['value'] = 'modified';
        $this->assertTrue($entity->isModified('fld_json_array'));
    }

    public function testObject()
    {
        $entity = $this->getEntity();
        $this->assertFalse($entity->isModified('fld_object'));
        $entity->fld_object['value'] = 'modified';
        $this->assertTrue($entity->isModified('fld_object'));
    }

}
