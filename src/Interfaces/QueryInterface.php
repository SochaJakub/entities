<?php

/**
 * Copyright (C) Jakub Socha
 *
 *
 * @file       : QueryInterface.php
 * @author     : Jakub Socha <jsocha@quatrodesign.pl>
 * @copyright  : (c) Jakub Socha
 * @date       : 4/18/19
 */

namespace Jsocha\Entities\Interfaces;

/**
 * Interface QueryInterface
 *
 * @package Jsocha\Entities\Interfaces
 */
interface QueryInterface
{
    /**
     * @return bool
     */
    public function execute(): bool;
}