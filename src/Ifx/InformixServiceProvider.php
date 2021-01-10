<?php

namespace Archytech\Laravel\Ifx;

use Archytech\Laravel\Ifx\Connectors\Connector;
use Illuminate\Support\Arr;
use Illuminate\Support\ServiceProvider;

class InformixServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        if (file_exists(config_path('informix.php'))) {
            $this->mergeConfigFrom(config_path('informix.php'), 'database.connections');

            $config = $this->app['config']->get('informix', []);

            $connection_keys = array_keys($config);

            foreach ($connection_keys as $key) {
                $this->app['db']->extend($key, function ($config) {
                    $driver = Arr::get($config, 'driver', 'informix');
                    if ($driver === 'informix') {
                        $oConnector = new Connector($this->app['encrypter']);
                        $connection = $oConnector->connect($config);

                        return new Connection($connection, $config['database'], $config['prefix'], $config);
                    } elseif ($driver === 'informix-json') {
                        return new JsonConnection($config);
                    }
                });
            }
        }
    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                $this->getConfigFile() => config_path('informix.php'),
            ], 'config');
        }
    }

    /**
     * Get configuration file.
     *
     * @return string
     */
    protected function getConfigFile()
    {
        /* __DIR__.'/../../config/informix.php'*/
        return $this->getPathPackage('config/informix.php');
    }

    /**
     * Get root path of package.
     *
     * @param string $path
     *
     * @return string
     */
    protected function getPathPackage($path = 'src/')
    {
        return __DIR__.DIRECTORY_SEPARATOR.'..'.DIRECTORY_SEPARATOR.'..'.DIRECTORY_SEPARATOR.$path;
    }
}
