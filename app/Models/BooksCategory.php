<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BooksCategory extends Model
{
    use HasFactory;
    protected $guarded = [];
    protected $table = 'books_categories';
}
