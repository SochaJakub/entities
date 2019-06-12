<?php
/*******************************************************************************
 * Copyright (c) 2019 by Jakub Socha <jsocha@quatrodesign.pl>
 ******************************************************************************/

namespace Jsocha\Entites\Tools;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Jsocha\Entities\Utils;


/**
 * Class MakeEntity
 *
 * @package Jsocha\Entites\Tools
 * @refactored
 */
class MakeEntity extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'make:entity';
    
    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate base entity';
    
    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }
    
    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $tableName = $this->ask('Podaj nazwę tabeli z mySQL', 'users_events');
        
        $directory = $this->ask('Czy encja ma być w subkatalogu? Jeśli nie - zostaw puste', '');
        
        $fields = explode(',', str_replace(' ', '', trim($this->ask('Wypisz pola z bazy danych (po przecinku)', 'id'))));
        $entityName = $this->generateName($tableName);
        
        $directoryPath = $directory ? $directory . '/' : '';
        
        $path = app_path('Entities/' . $directoryPath . $entityName . '.php');
        
        if (File::exists($path)) {
            $this->error('Entities ' . $entityName . ' already exists on ' . $path . '');
        }
        else {
            $file = file_get_contents(base_path('vendor/jsocha/entities/src/Tools/stubs/Entity.stb'));
            
            $properties = '';
            $needCarbon = false;
            foreach ($fields as $field) {
                
                if ($field != 'id' && $field != '') {
                    
                    $type = DB::getSchemaBuilder()->getColumnType($tableName, $field);
                    
                    switch ($type) {
                        case 'integer':
                        case 'boolean':
                            $defaultValue = 0;
                            $type = 'int';
                            break;
                        default:
                            $defaultValue = "''";
                    }
                    
                    if ($type === 'datetime' || $type === 'date' || $type === 'time') {
                        $type = 'Carbon';
                        $needCarbon = true;
                    }
                    
                    if ($type === 'text') {
                        $type = 'string';
                    }
                    
                    $properties .= '
                    /**
                     * @var ' . $type . '
                     */
                     protected $' . Utils::toCamelCase($field) . ' = ' . $defaultValue . ';';
                    
                }
            }
            
            
            $namespace = 'App\\Entities\\' . $directory;
            $replace = [
                'ENTITY_NAME'           => $entityName,
                'REPOSITORY_CLASS_NAME' => $entityName . 'Repository',
                'NAMESPACE'             => rtrim($namespace, '\\'),
                'IMPORT_REPOSITORY'     => str_replace('\\\\', '\\', 'App\\Repositories\\' . $directory . '\\' . $entityName . 'Repository'),
                '@@PROPERTIES@@'        => trim($properties),
                '@@IMPORT_CARBON@@'     => $needCarbon ? 'use Carbon\Carbon;' : ''
            ];
            
            $html = str_replace(array_keys($replace), array_values($replace), $file);
            
            if (! is_dir(app_path('Entities/' . $directoryPath))) {
                mkdir(app_path('Entities/' . $directoryPath));
            }
            
            File::put($path, $html);
            
            /**
             * Repository
             */
            $repositoryPath = app_path('Repositories/' . $directoryPath . $entityName . 'Repository.php');
            
            $file = file_get_contents(base_path('vendor/jsocha/entities/src/Tools/stubs/Repository.stb'));
            
            $namespace = 'App\\Repositories\\' . $directory;
            $replace = [
                'REPOSITORY_CLASS_NAME' => $entityName . 'Repository',
                'ENTITY_CLASS_NAME'     => $entityName,
                'NAMESPACE'             => rtrim($namespace, '\\'),
                'TABLE_NAME'            => $tableName,
                'IMPORT_ENTITY'         => str_replace('\\\\', '\\', 'App\\Entities\\' . $directory . '\\' . $entityName)
            ];
            
            $html = str_replace(array_keys($replace), array_values($replace), $file);
            
            
            if (! is_dir(app_path('Repositories/' . $directoryPath))) {
                mkdir(app_path('Repositories/' . $directoryPath));
            }
            
            File::put($repositoryPath, $html);
            
            
            $this->info('Entity ' . $entityName . ' created on ' . $path . '');
        }
    }
    
    /**
     *
     * @param string $tableName
     *
     * @return string
     */
    final private function generateName(string $tableName)
    {
        $parts = explode('_', $tableName);
        
        $entityName = '';
        foreach ($parts as $part) {
            
            if (substr($part, -3) == 'ies') {
                $entityName .= ucfirst(substr($part, 0, -3)) . 'y';
                
            }
            elseif (substr($part, -1) == 's') {
                $entityName .= ucfirst(substr($part, 0, -1));
            }
            else {
                $entityName .= ucfirst($part);
            }
        }
        
        return $entityName;
    }
}
