<?php

use App\Http\Controllers\LinkController;
use Illuminate\Support\Facades\Route;

Route::get('/', [LinkController::class, 'index'])->name('links.index');

Route::get('/{slug}', [LinkController::class, 'redirect'])->name('links.redirect');
