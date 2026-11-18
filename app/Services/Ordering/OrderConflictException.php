<?php

namespace App\Services\Ordering;

use App\Models\Ordering\Order;

/** The order changed (or moved on) since the staff member looked at it: show them the fresh state. */
class OrderConflictException extends \RuntimeException
{
    public function __construct(public Order $order, string $message)
    {
        parent::__construct($message);
    }
}
