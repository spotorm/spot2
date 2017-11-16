<?php

namespace Spot\Query\Operator;

use Doctrine\DBAL\Query\QueryBuilder;

/**
 * @package Spot\Query\Operator
 */
class FullTextBoolean
{
    /**
     * @param QueryBuilder $builder
     * @param $column
     * @param $value
     * @return string
     */
    public function __invoke(QueryBuilder $builder, $column, $value)
    {
        if ($value instanceof \Closure) {
            return $column . ' = ' . $value();
        }

        return 'MATCH(' . $column . ') AGAINST (' . $builder->createPositionalParameter($value) . ' IN BOOLEAN MODE)';
    }
}
