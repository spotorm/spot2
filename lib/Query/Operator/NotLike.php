<?php

namespace Spot\Query\Operator;

use Doctrine\DBAL\Query\QueryBuilder;

/**
 * @package Spot\Query\Operator
 */
class NotLike
{
    /**
     * @param QueryBuilder $builder
     * @param $column
     * @param $value
     * @return string
     */
    public function __invoke(QueryBuilder $builder, $column, $value)
    {
        return $column . ' NOT LIKE ' . $builder->createPositionalParameter($value);
    }
}
