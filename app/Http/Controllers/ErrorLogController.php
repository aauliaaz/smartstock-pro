<?php

namespace App\Http\Controllers;

use App\Models\ErrorLog;
use Illuminate\Http\Request;

class ErrorLogController extends Controller
{
    public function index()
    {
        return ErrorLog::with('user')->latest()->paginate(20);
    }
}
