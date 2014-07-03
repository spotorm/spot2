<?php
namespace Spot\Provider;

use Illuminate\Support\ServiceProvider;
use Spot\Config;
use Spot\Locator;

class Laravel extends ServiceProvider
{
    protected $config = [];

    public function __construct($app)
    {
        $this->app = $app;

        $configObject = $this->app['config'];
        $connections = $configObject->get('database.connections');
        $config = $connections[$configObject->get('database.default')];

        // Munge Laravel array structure to match expected Doctrine DBAL's
        $config = [
            'dbname'    => $config['database'],
            'user'      => $config['username'],
            'password'  => $config['password'],
            'host'      => $config['host'],
            'driver'    => 'pdo_' . $config['driver']
        ];
        $this->config = $config;
    }

    public function register()
    {
        $this->app['spot'] = function() {
            $config = new Config();
            $config->addConnection('default', $this->config);
            return new Locator($config);
        };
    }

}

