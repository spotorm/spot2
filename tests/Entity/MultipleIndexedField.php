<?php
namespace SpotTest\Entity;

use Spot\Entity;

/**
 * Entity with fields in compound index and in non-compound index
 *
 * @package Spot
 */
class MultipleIndexedField extends \Spot\Entity
{
    protected static $table = 'test_multipleindexedfield';

    public static function fields()
    {
        return [
            'id'            => ['type' => 'integer', 'primary' => true],
            'companyGroup'  => ['type' => 'integer', 'required' => false, 'index' => true],
            'company'       => ['type' => 'integer', 'required' => false, 'index' => [true, 'employee']],
            'user'          => ['type' => 'string', 'required' => false, 'index' => [true, 'employee']],
        ];
    }
}
