<?php

// use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// Route::middleware('auth:sanctum')->get('/', function (Request $request) {
//     return $request->user();
// });

Route::post('registration', 'Users\Registration');