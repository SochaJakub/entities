<?php

/**
 * Copyright (C) Jakub Socha
 *
 *
 * @file       : helpers.php
 * @author     : Jakub Socha <jsocha@quatrodesign.pl>
 * @copyright  : (c) Jakub Socha
 * @date       : 4/17/19
 */

namespace Jsocha\Entities;

use Jsocha\Entities\Interfaces\EntityInterface;

/**
 * Class Utils
 *
 * @package Jsocha\Entities
 */
final class Utils
{
    /**
     * Konwertuje camelCase do underscore i tylko takie pola są dozwolone w bazie danych
     *
     * @param string $key
     *
     * @return string
     */
    final public static function toUnderScore(string $key)
    {
        return strtolower(preg_replace('/(?<!^)[A-Z]/', '_$0', $key));
    }
    
    
    /**
     * Konwersja zmiennej underscore do camelCase
     *
     * @param string $field
     *
     * @return mixed|string
     */
    final public static function toCamelCase(string $field)
    {
        return preg_match('/_/', $field) ? str_replace('_', '', lcfirst(ucwords($field, '_'))) : $field;
    }
    
    /**
     * Mapuje kolekcje encji na tablice
     *
     * @param array $arrayOfEntities
     *
     * @return array
     */
    final public static function mapToArray(array $arrayOfEntities): array
    {
        return array_map(function (EntityInterface $entity) {
            return $entity->toArray();
        }, $arrayOfEntities);
    }
    
    
}

