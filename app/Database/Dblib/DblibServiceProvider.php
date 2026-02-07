<?php

namespace App\Database\Dblib;

use Illuminate\Support\ServiceProvider;

class DblibServiceProvider extends ServiceProvider
{
    public function boot()
    {
        $this->app['db']->extend('dblib', function ($config, $name) {
            $connector = new DblibConnector();
            $pdo = $connector->connect($config);

            return new DblibConnection(
                $pdo,
                $config['database'],
                $config['prefix'] ?? '',
                $config
            );
        });
    }
}
