<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::get('/', function () {
    return view('index');
});


Route::prefix('menus')->group(function () {
    Route::get('/create', function () {
        return view('product');
    })->name('create');
    Route::post('/store', [\App\Livewire\CreateProduct::class, 'store'])->name('store');
    Route::get('/{menu}/edit', [\App\Livewire\CreateProduct::class, 'edit'])->name('edit');
    Route::put('/{menu}', [\App\Livewire\CreateProduct::class, 'update'])->name('update');
    Route::delete('/{menu}', [\App\Livewire\CreateProduct::class, 'destroy'])->name('destroy');
});


Route::get('/order-history', function () {
    return view('order-history');
})->name('order.history');

Route::get('/orders/{id}', function($id) {
    return view('show', compact('id'));
})->name('orders.show');
