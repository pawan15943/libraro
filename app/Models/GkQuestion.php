<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GkQuestion extends Model
{
    protected $guarded = [];

    public function options(): array
    {
        return ['a' => $this->option_a, 'b' => $this->option_b, 'c' => $this->option_c, 'd' => $this->option_d];
    }
}
