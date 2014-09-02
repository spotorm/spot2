<?php
namespace Spot\Provider;

use Illuminate\Support\ServiceProvider;
use Spot\Config;
use Spot\Locator;

class Laravel extends ServiceProvider
{
    /**
     * Indicates if loading of the provider is deferred.
     *
     * @var bool
     */
    protected $defer = false;

    /**
     * Register the service provider.
     *
     * The register method is called immediately when the service provider
     * is registered, there is no promise that services created by other
     * providers are available when this method is called.
     *
     * @return void
     */
    public function register()
    {
        $this->app->bindShared('spot', function ($app) {

            $connection = $app['config']->get('database.default');
            $credentials = $app['config']->get('database.connections.' . $connection);

            $config = new Config();
            $config->addConnection('default', [
                'dbname'   => $credentials['database'],
                'user'     => $credentials['username'],
                'password' => $credentials['password'],
                'host'     => $credentials['host'],
                'driver'   => 'pdo_' . $credentials['driver']
            ]);

            return new Locator($config);
        });
    }

    /**
     * Bootstrap the application events.
     *
     * This method is called right before a request is routed. If actions
     * in this provider rely on another service being registered, or you
     * are overriding services bound by another provider, you should
     * use this method.
     *
     * @return void
     */
    public function boot()
    {
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return array('spot');
    }
}
