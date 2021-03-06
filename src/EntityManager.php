<?php

/**
 * Copyright (C) Jakub Socha
 *
 *
 * @file       : EntityManager.php
 * @author     : Jakub Socha <jsocha@quatrodesign.pl>
 * @copyright  : (c) Jakub Socha
 * @date       : 4/17/19
 */

namespace Jsocha\Entities;

use Exception;
use Illuminate\Support\Facades\DB;
use Jsocha\Entities\Interfaces\EntityInterface;
use Jsocha\Entities\Interfaces\RepositoryInterface;

/**
 * Class EntityManager
 *
 * @package Jsocha\Entities
 */
final class EntityManager
{
    
    /**
     * Dodawanie encji do bazy danych
     *
     * @param  EntityInterface  $entity
     * @param  bool  $instantFetch
     *
     * @return EntityInterface
     */
    final public function add(EntityInterface $entity, bool $instantFetch = true): EntityInterface
    {
        $repository = $entity->getRepository();
        
        try {
            $query = DB::connection($repository->getWriteConnection())->table($repository->getTable());
            $entityData = $this->prepareDataForCreate($entity);
            
            if ($instantFetch) {
                $id = $query->insertGetId($entityData);
                
                $entity->setId($id);
                $entity->setOriginalData($entity->toArray());
            } else {
                $query->insert($entityData);
            }
            
            return $entity;
            
        } catch (\Exception $exception) {
            if (env('APP_ENV') === 'production') {
                throw new Exception('Can`t create entity', 500);
            } else {
                throw new Exception('Can`t create entity: ' . $exception->getMessage(), 500);
            }
            
        }
    }
    
    
    /**
     * Zapisywanie encji w bazie danych
     *
     * @param  EntityInterface  $entity
     *
     * @return bool
     */
    final public function save(EntityInterface $entity): bool
    {
        $repository = $entity->getRepository();
        
        $dataToUpdate = $this->prepareDataForUpdate($entity);
        
        if (count($dataToUpdate) > 0) {
            return DB::connection($repository->getWriteConnection())->table($repository->getTable())->where('id', $entity->getId())->update($dataToUpdate) > 0;
        }
        
        return true;
    }
    
    /**
     * Zapisuje encje w bazie
     *
     * @param  EntityInterface  $entity
     *
     * @param  array  $data
     *
     * @return EntityInterface|bool
     */
    final public function merge(EntityInterface $entity, array $data)
    {
        $repository = $entity->getRepository();
        
        $entityArray = $entity->toArray();
        
        $dataToUpdate = array_diff_assoc(array_merge($entityArray, $data), $entityArray);
        
        if (count($dataToUpdate) > 0) {
            
            DB::connection($repository->getWriteConnection())->table($repository->getTable())->where('id', $entity->getId())->update($dataToUpdate);
            
            foreach ($dataToUpdate as $key => $value) {
                $entityArray[$key] = $value;
            }
            
            $entityClass = $repository->getEntity();
            
            return new $entityClass($entityArray);
        }
        
        return false;
    }
    
    /**
     * Pernamentne usuwanie encji
     *
     * @param  EntityInterface  $entity
     *
     * @return bool
     */
    final public function delete(EntityInterface $entity): bool
    {
        $repository = $entity->getRepository();
        
        return DB::connection($repository->getWriteConnection())->table($repository->getTable())->where('id', $entity->getId())->delete() > 0;
    }
    
    /**
     * Masowe usuwanie encji (wg ID)
     *
     * @param  array  $entities
     *
     * @return bool
     */
    final public function massDelete(array $entities): bool
    {
        if (count($entities) > 0) {
            /** @var RepositoryInterface $repository */
            $repository = $entities[0]->getRepository();
            
            $ids = [];
            /** @var EntityInterface $entity */
            foreach ($entities as $entity) {
                $ids[] = $entity->getId();
            }
            
            return DB::connection($repository->getWriteConnection())->table($repository->getTable())->whereIn('id', $ids)->delete() > 0;
        }
        
        return true;
    }
    
    /**
     * @param  string  $repositoryClass
     * @param  array  $conditions
     * @param  array  $newData
     *
     * @return int
     * @throws Exception
     */
    final public function massUpdate(string $repositoryClass, array $conditions, array $newData)
    {
        $repository = resolve($repositoryClass);
        
        $query = BaseRepository::applyFilters(DB::connection($repository->getWriteConnection())->table($repository->getTable()), $conditions);
        
        return $query->update($newData);
    }
    
    /**
     * Przygotowuje dane do stworzenia nowego rekordu w bazie
     *
     * @param  EntityInterface  $resource
     *
     * @return array
     * @throws \ReflectionException
     */
    final protected function prepareDataForCreate(EntityInterface $resource): array
    {
        $reflect = new \ReflectionClass($resource);
        
        $properties = $reflect->getProperties();
        
        $dataToReturn = [];
        /**
         * @var EntityInterface $resource
         */
        foreach ($properties as $property) {
            $methodName = 'get' . ucfirst($property->name);
            $methodNameIs = 'is' . ucfirst($property->name);
            
            //Biore pod uwage tylko glowne pola encji - żadnych relacji
            if (! in_array($property->name, array_keys($resource->getRelations()))) {
                
                //Metoda istnieje i nie jest pobierane ID/Repozytorium
                if (method_exists($resource, $methodName) && ! in_array($methodName, ['getId', 'getRepository'])) {
                    $dataToReturn[Utils::toUnderScore($property->name)] = $resource->$methodName();
                } elseif (method_exists($this, $methodNameIs)) {
                    $dataToReturn[Utils::toUnderScore($property->name)] = (int) $resource->$methodNameIs();
                }
            }
        }
        
        //Pozbywam się rekordów których nie trzeba aktualizować
        foreach (['original_data', 'relations', 'repository'] as $key) {
            unset($dataToReturn[$key]);
        }
        
        return $dataToReturn;
    }
    
    /**
     * Przygotowuje dane do zapisu (przetworzenie Entity na pola SQL)
     *
     * @param  EntityInterface  $entity
     *
     * @return array
     */
    final protected function prepareDataForUpdate(EntityInterface $entity): array
    {
        $dataToReturn = [];
        
        /**
         * @var EntityInterface $entity
         */
        foreach ($entity->getOriginalData() as $key => $oldValue) {
            
            $methodName = 'get' . Utils::toCamelCase($key);
            $methodNameIs = 'is' . Utils::toCamelCase($key);
            
            if ($methodName !== 'getId') {
                if (method_exists($entity, $methodName)) {
                    if ($oldValue != $entity->$methodName()) {
                        $dataToReturn[Utils::toUnderScore($key)] = $entity->$methodName();
                    }
                }
                /**
                 * Wlaściwości "boolowskie" z rzutowaniem na "INT"
                 */
                if (method_exists($entity, $methodNameIs)) {
                    if ($oldValue != (int) $entity->$methodNameIs()) {
                        $dataToReturn[Utils::toUnderScore($key)] = (int) $entity->$methodNameIs();
                    }
                }
            }
        }
        
        return $dataToReturn;
    }
}