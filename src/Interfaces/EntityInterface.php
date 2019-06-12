<?php
/*******************************************************************************
 * Copyright (c) 2019 by Jakub Socha <jsocha@quatrodesign.pl>
 ******************************************************************************/

namespace Jsocha\Entities\Interfaces;

/**
 * Interface EntityInterface
 *
 * @package Jsocha\Entities\Interfaces
 */
interface EntityInterface
{
    /**
     * @return int
     */
    public function getId(): int;
    
    /**
     * @param int $id
     */
    public function setId(int $id): void;
    
    /**
     * @return RepositoryInterface
     */
    public function getRepository();
    
    /**
     * @return array
     */
    public function toArray(): array;
    
    /**
     * @param array $originalData
     */
    public function setOriginalData(array $originalData): void;
    
    /**
     * @return array
     */
    public function getOriginalData(): array;
    
    /**
     * @return array
     */
    public function getRelations(): array;
    
}