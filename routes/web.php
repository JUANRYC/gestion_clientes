<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', function () {
    return redirect()->route('customers.index');
})->name('home');

Route::get('/customers', function () {
    return view('customers.index');
})->name('customers.index');

Route::get('/orders', function () {
    return view('orders.index');
})->name('orders.index');
