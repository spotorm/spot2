<?php

namespace Spot\Query\Operator;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Query\QueryBuilder;
use Spot\Exception;

/**
 * @package Spot\Query\Operator
 */
class Not
{
    /**
     * @param QueryBuilder $builder
     * @param $column
     * @param $value
     * @throws Exception
     * @return string
     */
    public function __invoke(QueryBuilder $builder, $column, $value)
    {
        if (is_array($value) && !empty($value)) {
            return $column . ' NOT IN (' . $builder->createPositionalParameter($value, Connection::PARAM_STR_ARRAY) . ')';
        }

        if ($value === null || (is_array($value) && empty($value))) {
            return $column . ' IS NOT NULL';
        }

        return $column . ' != ' . $builder->createPositionalParameter($value);
    }
}
