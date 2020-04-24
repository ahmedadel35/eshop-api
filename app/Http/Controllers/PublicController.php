<?php

namespace App\Http\Controllers;

use App\Console\Commands\Inspiring;
use Carbon\Carbon;
use Illuminate\Support\Facades\Artisan;

class PublicController extends Controller
{
    public function index()
    {
        return response()->json(Carbon::now());
    }

    public function inspireMe()
    {
        return response()->json(Inspiring::quote());
    }
}
