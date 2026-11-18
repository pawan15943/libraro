<?php

namespace App\Services\Ordering;

/** The cart total differs from the latest prices: the learner must see the new total first. */
class PriceChangedException extends \RuntimeException
{
    public function __construct(public float $total, public array $prices)
    {
        parent::__construct('Some prices changed. Please check your cart again.');
    }
}
