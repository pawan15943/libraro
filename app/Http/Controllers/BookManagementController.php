<?php

namespace App\Http\Controllers;

use App\Models\BooksCategory;
use Illuminate\Http\Request;

class BookManagementController extends Controller
{
    public function categoryIndex()
    {
        $data = BooksCategory::all();
        return view('book_managment_master.categoryList', compact('data'));
    }

    public function categoryCreate($id = null)
    {
        $category = BooksCategory::find($id);
        return view('book_managment_master.category', compact('category'));
    }
}
