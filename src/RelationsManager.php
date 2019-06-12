<?php

/**
 * Copyright (C) Jakub Socha
 *
 *
 * @file       : RelationsManager.php
 * @author     : Jakub Socha <jsocha@quatrodesign.pl>
 * @copyright  : (c) Jakub Socha
 * @date       : 4/17/19
 */

namespace Jsocha\Entities;

use Jsocha\Entities\Interfaces\EntityInterface;
use Jsocha\Entities\Interfaces\RepositoryInterface;

/**
 * Class RelationsManager
 *
 * @package Jsocha\Entities
 */
final class RelationsManager
{
    /**
     * @var array
     */
    private $result = [];
    
    /**
     * @var array
     */
    private $relationData = [];
    
    /**
     * RelationsManager constructor.
     *
     * @param array $result
     * @param array $relationData
     */
    final public function __construct(array $result, array $relationData)
    {
        $this->result = $result;
        $this->relationData = $relationData;
    }
    
    /**
     * Pobranie danych relacji
     */
    final private function getRelatedEntities(): array
    {
        /**
         * Instancja encji dziecka
         *
         * @var EntityInterface $relatedEntity
         */
        $relatedEntity = new $this->relationData['entity'];
        
        
        /**
         * Instancja repozytorium dziecka
         *
         * @var RepositoryInterface $relatedEntityRepository
         */
        $relatedEntityRepository = $relatedEntity->getRepository();
        
        /**
         * Pobranie wszystkich encji pasujących do wartości klucza
         */
        $values = $this->extractForeignKeysValues($this->result, $this->relationData['local_key']);
        
        return $relatedEntityRepository->findBy([$this->relationData['entity_key'] => ['IN', $values]]);
    }
    
    /**
     * Relacja 1 do wielu
     *
     * @return array
     */
    final public function hasMany()
    {
        /**
         * Zgrupowanie wyników WG klucza podstawowego encji (np user_id)
         */
        $groupedResult = [];
        
        $relatedEntites = $this->getRelatedEntities();
        
        foreach ($relatedEntites as $entity) {
            
            $functionName = 'get' . Utils::toCamelCase($this->relationData['entity_key']);
            
            $groupedResult[$entity->$functionName()][] = $entity;
        }
        
        return $groupedResult;
    }
    
    /**
     * Relacja 1 do 1
     *
     * @return array
     */
    final public function hasOne()
    {
        /**
         * Zgrupowanie wyników WG klucza podstawowego encji (np user_id)
         */
        $groupedResult = [];
        
        $relatedEntites = $this->getRelatedEntities();
        
        
        foreach ($relatedEntites as $entity) {
            
            $functionName = 'get' . Utils::toCamelCase($this->relationData['entity_key']);
            
            $groupedResult[$entity->$functionName()] = $entity;
        }
        
        return $groupedResult;
    }
    
    
    /**
     * Wyciągam unikalne wartości z klucza (w 99% przypadkach ID) z wyników zapytania
     *
     * @param array  $query
     * @param string $key
     *
     * @return array
     */
    final private function extractForeignKeysValues(array $query, string $key): array
    {
        $keys = collect($query)->pluck($key)->toArray();
        
        return array_values(array_unique($keys));
    }
    
}