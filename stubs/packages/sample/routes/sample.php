<?php

use Illuminate\Support\Facades\Route;
use YourDomain\Sample\Controllers\SampleController;

Route::get('/sample', [SampleController::class, 'sample'])->name('sample');
