<?php

namespace App\Exceptions;

use RuntimeException;

class InsufficientStockException extends RuntimeException
{
    /**
     * @param  array<int, string>  $skus
     */
    public function __construct(public readonly array $skus)
    {
        parent::__construct('Insufficient stock for: '.implode(', ', $skus));
    }
}
