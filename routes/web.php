<?php

use App\Http\Controllers\SynthController;
use Illuminate\Support\Facades\Route;

Route::get('/', [SynthController::class, 'index']);
Route::post('/synthesize', [SynthController::class, 'synthesize']);
