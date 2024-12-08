<?php

namespace YourDomain\Sample\Controllers;

use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class SampleController
{
    public function sample(Request $request): View
    {
        $name = $request->input('name', 'Superman');

        // sample:: is the namespace defined in the SampleServiceProvider
        return view('sample::sample', [
            'name' => $name,
        ]);
    }
}
