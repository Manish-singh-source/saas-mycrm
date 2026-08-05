<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});
Route::get('/platform/access-control/roles', function () {
    return view('platform.access-control.roles');
})->name('platform.access-control.roles');
