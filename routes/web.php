<?php

use App\Http\Controllers\HomeController;
use Illuminate\Support\Facades\Route;

Route::get('/', [HomeController::class, 'index'])->name('home');
Route::get('/collections/{slug}', [HomeController::class, 'collection'])->name('collections.show');
Route::get('/products/{slug}', [HomeController::class, 'product'])->name('products.show');
Route::get('/cart', [HomeController::class, 'cart'])->name('cart.index');
