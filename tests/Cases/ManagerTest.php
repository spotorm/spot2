<?php
namespace SpotTest\Cases;

/**
 * @package Spot
 */
class ManagerTest extends \PHPUnit\Framework\TestCase
{
    public function testNotnullOverride()
    {
        $mapper = \test_spot_mapper('SpotTest\Entity\NotNullOverride');
        $manager = $mapper->entityManager();
        $fields = $manager->fields();

        $this->assertTrue($fields['data1']['notnull']); // Should default to true
        $this->assertTrue($fields['data2']['notnull']); // Should override to true
        $this->assertFalse($fields['data3']['notnull']); // Should override to false
    }

    public function testMultipleIndexedField()
    {
        $mapper = \test_spot_mapper('SpotTest\Entity\MultipleIndexedField');
        $manager = $mapper->entityManager();
        $fieldKeys = $manager->fieldKeys();

        // companyGroup, company and user must be indexed separately
        $this->assertTrue(array_key_exists('test_multipleindexedfield_companyGroup', $fieldKeys['index']));
        $this->assertTrue(array_key_exists('test_multipleindexedfield_company', $fieldKeys['index']));
        $this->assertTrue(array_key_exists('test_multipleindexedfield_user', $fieldKeys['index']));

        // an "employee" index must exist with company and user field
        $this->assertTrue(array_key_exists('test_multipleindexedfield_employee', $fieldKeys['index']));
        $this->assertStringContainsString('company', $fieldKeys['index']['test_multipleindexedfield_employee']);
        $this->assertStringContainsString('user', $fieldKeys['index']['test_multipleindexedfield_employee']);
    }
}
