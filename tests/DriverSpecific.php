<?php
namespace SpotTest;

/**
 * @package Spot
 */
class DriverSpecific
{
    public static function getWeekFunction($mapper, $field = null) {
        if ($mapper->connectionIs('mysql')) {
            return "WEEK(" . $field . ")";
        } else if ($mapper->connectionIs('pgsql')) {
            return "EXTRACT(WEEK FROM TIMESTAMP " . $field . ")";
        } else if ($mapper->connectionIs('sqlite')) {
            return "STRFTIME('%W', " . $field . ")";
        }
        return false;
	}
}
