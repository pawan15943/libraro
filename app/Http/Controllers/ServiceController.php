<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class ServiceController extends Controller
{
    public function daily_dashboard(){
        return view ('service.gk');
    }
}
