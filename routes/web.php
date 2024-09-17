<?php

use Illuminate\Support\Facades\Route;

Route::get('{any}', function () {
  return view('welcome'); // または 'app' ではなく 'welcome' を返す
})->where('any', '.*');
