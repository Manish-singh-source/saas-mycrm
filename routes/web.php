<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});
Route::get('/api-docs/rbac', function () {
    return view('api-docs.rbac');
})->name('api-docs.rbac');

Route::get('/api-docs/rbac.yaml', function () {
    $path = base_path('docs/openapi-rbac.yaml');

    abort_unless(is_file($path), 404);

    return response()->file($path, [
        'Content-Type' => 'application/yaml; charset=UTF-8',
    ]);
})->name('api-docs.rbac.spec');
Route::get('/api-docs', function () {
    return view('api-docs.rbac');
})->name('api-docs.completed');

Route::get('/api-docs/openapi.yaml', function () {
    $path = base_path('docs/openapi-completed.yaml');

    abort_unless(is_file($path), 404);

    return response()->file($path, [
        'Content-Type' => 'application/yaml; charset=UTF-8',
    ]);
})->name('api-docs.completed.spec');