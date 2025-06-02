<?php
namespace App\Traits;

trait HasSeatType
{
    public function isNumbered()
    {
        return $this->seat_type === 'numbered';
    }

    public function isGeneral()
    {
        return $this->seat_type === 'general';
    }

    public function isHybrid()
    {
        return $this->seat_type === 'hybrid';
    }
}
