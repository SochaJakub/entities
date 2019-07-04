<?php

/**
 * Copyright (C) Jakub Socha
 *
 *
 * @file       : EntitiesServiceProvider.php
 * @author     : Jakub Socha <jsocha@quatrodesign.pl>
 * @copyright  : (c) Jakub Socha
 * @date       : 7/4/19
 */

namespace Jsocha\Entities\Providers;

use Illuminate\Support\ServiceProvider;

final class EntitiesServiceProvider extends ServiceProvider
{
    /**
     * Register bindings in the container.
     *
     * @return void
     */
    public function register()
    {
        foreach ($this->app->make('files')->allFiles(app_path('Repositories')) as $file) {
            
            $realPath = str_replace('/', '\\', $file->getRelativePathname());
            
            $className = 'App\\Repositories\\' . str_replace('.php', '', $realPath);
            
            $repository = new $className;
            
            $this->app->singleton(get_class($repository), function () use ($repository) {
                return $repository;
            });
        }
        
    }
}
