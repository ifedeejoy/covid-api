<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class Logs extends Controller
{
    public function apiLogs()
    {
        $file = Storage::get('public/log.txt');
        return response($file, 200)->header('Content-Type', "text/plain");
    }
}
