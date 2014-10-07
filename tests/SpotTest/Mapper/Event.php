<?php
namespace SpotTest\Mapper;

use Spot\Mapper;
use Spot\Query;

class Event extends Mapper
{
    /**
     * Custom scopes applied to Spot\Query
     *
     * @return array
     */
    public function scopes()
    {
        return [
            'free' => function (Query $query) {
                return $query->where(['type' => 'free']);
            },
            'active' => function (Query $query) {
                return $query->where(['status' => 1]);
            }
        ];
    }

    /**
     * Just generate a test query so we can ensure this method is getting called
     *
     * @return \Spot\Query
     */
    public function testQuery()
    {
        return $this->where(['title' => 'test']);
    }
}
