<?php
/*******************************************************************************
 * Copyright (c) 2019 by Jakub Socha <jsocha@quatrodesign.pl>
 ******************************************************************************/

declare(strict_types=1);

namespace Jsocha\Entities;

use Carbon\Carbon;

/**
 * Class BaseEntity
 *
 * Jak używać:
 *
 * Każda property zapisana jaka camelCase (w bazie under_score!). Musi mieć get/set
 *
 * @package Jsocha\Entities
 */
abstract class BaseEntity
{
    /**
     * @var int
     */
    protected $id = 0;
    
    /**
     * Tablica relacji danej encji. Wpisujemy nazwę relacji by potem pominąć te wpisy z save/update
     *
     * @var array
     */
    protected $relations = [];
    
    /**
     * Kopia stany encji przed wprowadzeniem zmian - porownujemy ze stanem po zmianach i aktualizujemy odpowiednie pola w bazie danych
     *
     * @var array
     */
    protected $originalData;
    
    /**
     * BaseEntity constructor.
     *
     * Konwertujemy mysqlowe under_score na camelCase wlasciowsci obiektów
     *
     * @param array $data
     */
    public function __construct(array $data = [])
    {
        $originalData = [];
        
        foreach ($data as $key => $value) {
            $finalKey = Utils::toCamelCase($key);
            
            $this->{$finalKey} = $value;
            $originalData[$finalKey] = $value;
        }
        
        $this->originalData = $originalData;
        
    }
    
    /**
     * @param int $id
     */
    public function setId(int $id): void
    {
        $this->id = $id;
    }
    
    /**
     * Zwraca początkowy stan encji
     *
     * @return array
     */
    final public function getOriginalData(): array
    {
        return $this->originalData;
    }
    
    /**
     * @param array $originalData
     */
    public function setOriginalData(array $originalData): void
    {
        $this->originalData = $originalData;
    }
    
    
    /**
     * Zwraca tablice kluczy -relacji które należy pomijać przy get/set
     *
     * @return array
     */
    final public function getRelations(): array
    {
        return $this->relations;
    }
    
    
    /**
     * Zrzuca encje do tablicy
     *
     * @return array
     */
    final public function toArray(): array
    {
        $clone = new \ReflectionClass($this);
        $entityAsArray = [];
        
        foreach ($clone->getProperties() as $property) {
            
            //Biore pod uwage tylko glowne pola encji - żadnych relacji
            if (! in_array($property->name, $this->getRelations())) {
                
                $key = Utils::toUnderScore($property->name);
                
                $methodName = 'get' . ucfirst($property->name);
                
                $methodNameIs = 'is' . ucfirst($property->name);
                
                //Metoda istnieje i nie należy do zakazanych properties/methods
                if (method_exists($this, $methodName) && ! in_array($methodName, ['getRepository', 'relations', 'getRelations', 'getOriginalData', 'getPassword'])) {
                    
                    $value = $this->$methodName();
                    
                    if ($value instanceof Carbon) {
                        $entityAsArray[$key] = $value->toDateTimeString();
                    }
                    else {
                        $entityAsArray[$key] = $value;
                    }
                }
                else if (method_exists($this, $methodNameIs)) {
                    $entityAsArray[$key] = (int) $this->$methodNameIs();
                }
            }
        }
        
        return $entityAsArray;
        
    }
    
    
}