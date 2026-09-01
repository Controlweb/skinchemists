<?php

namespace App\Exceptions;

use RuntimeException;

class OutOfStockException extends RuntimeException
{
    /** @param  array<int, string>  $products  Names of the products that came up short. */
    public function __construct(public readonly array $products)
    {
        parent::__construct('Stock insuffisant : '.implode(', ', $products));
    }
}
