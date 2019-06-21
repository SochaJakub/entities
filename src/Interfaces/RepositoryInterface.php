<?php
/*******************************************************************************
 * Copyright (c) 2019 by Jakub Socha <jsocha@quatrodesign.pl>
 ******************************************************************************/

namespace Jsocha\Entities\Interfaces;

use Illuminate\Pagination\LengthAwarePaginator;

/**
 * Interface RepositoryInterface
 *
 * @package Jsocha\Entities\Interfaces
 */
interface RepositoryInterface
{
    function find(int $id);
    
    function findOneBy(array $conditions = [], array $sorting = []);
    
    function findBy(array $conditions = [], array $sorting = [], int $limit = 0, array $relations = []);
    
    function all(): array;
    
    function countBy(array $conditions = []): int;
    
    function paginate(array $filters, array $sorting, int $currentPage, int $perPage = 30, array $options = []): LengthAwarePaginator;
    
    function countForPagination(array $filters): int;
    
    function takePortion(array $filters, array $sorting, int $page = 1, int $perPage = 30): array;
    
    function getTable(): string;
    
    function getEntity(): string;
    
    function getWriteConnection(): string;
    
    function getReadConnection(): string;
    
}