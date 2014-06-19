<?php
namespace SpotTest\Mapper;

use Spot\Mapper;

class Event extends Mapper
{
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
