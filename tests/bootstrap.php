<?php
/**
 * @package Spot
 */

error_reporting(-1);
ini_set('display_errors', 1);

/**
 * Autoload dependencies and test namespace
 */
require dirname(dirname(__FILE__)) . '/vendor/autoload.php';
require dirname(dirname(__FILE__)) . '/tests/autoload.php';

// Date setup
date_default_timezone_set('America/Chicago');

// Setup available adapters for testing
$cfg = new \Spot\Config();
$dbDsn = getenv('SPOT_DB_DSN');

if (!empty($dbDsn)) {
    $cfg->addConnection('test', $dbDsn);
} else {
    die('DSN not configured');
}

/**
 * Return Spot mapper for use
 */
$spot = new Spot\Locator($cfg);
function test_spot_mapper($entityName)
{
    global $spot; // you should never do this in real code :)

    return $spot->mapper($entityName);
}
