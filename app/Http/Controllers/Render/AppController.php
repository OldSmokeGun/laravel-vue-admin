<?php

namespace App\Http\Controllers\Render;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class AppController extends Controller
{
    public function index()
    {
        return view('app');
    }
}
