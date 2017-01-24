<?php

namespace App\Http\Controllers;

use Faker\Generator;
use Illuminate\Http\Request;

class WebstuhlController extends Controller
{
    public function home()
    {
        return view('webstuhl.home');
    }
}
