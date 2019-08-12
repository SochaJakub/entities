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

use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Support\ServiceProvider;

/**
 * Class EntitiesServiceProvider
 *
 * @package Jsocha\Entities\Providers
 */
final class EntitiesServiceProvider extends ServiceProvider
{
	/**
	 * Register bindings in the container.
	 *
	 * Przypisuje repozytoria jako singletony do aplikacji, dzieki czemu tylko 1 instancja repozytorium jest zainicjowana w aplikacji
	 *
	 * @return void
	 */
	public function register()
	{
		/** @var Filesystem $filesystem */
		$filesystem = $this->app->make('files');
		
		$path = $filesystem->exists(app_path('Repository')) ? app_path('Repository') : app_path('Repositories');
		
		$namespace = $filesystem->exists(app_path('Repository')) ? 'Repository' : 'Repositories';
		
		foreach ($filesystem->allFiles($path) as $file) {
			$realPath = str_replace('/', '\\', $file->getRelativePathname());
			
			$className = 'App\\' . $namespace . '\\' . str_replace('.php', '', $realPath);
			
			$repository = new $className;
			
			$this->app->singleton(get_class($repository), function () use ($repository) {
				return $repository;
			});
		}
		
	}
}
