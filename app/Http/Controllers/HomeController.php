<?php

namespace App\Http\Controllers;

use App\Models\Setting;

class HomeController extends Controller
{
    public function __invoke()
    {
        $numbers = [];
        foreach (config('effepi.home_numbers') as $key => $default) {
            $numbers[$key] = (int) Setting::get('home_'.$key, $default);
        }

        return view('home', ['numbers' => $numbers]);
    }
}
