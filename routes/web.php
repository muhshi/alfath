<?php

use App\Http\Controllers\SurveyController;
use Illuminate\Support\Facades\Route;

// ===== Auth default dari Tablar (cukup sekali) =====
Auth::routes();

// ===== Halaman utama =====
Route::get('/', [\App\Http\Controllers\HomeController::class, 'index'])
    ->name('dashboard');

// ===== Kalau mau tetap punya /home, tapi tanpa bentrok nama =====
// Route::get('/home', function () {
//     return redirect()->route('dashboard');
// })->name('home');

// ===== Routes survei =====
Route::get('/surveys', [SurveyController::class, 'index'])->name('surveys.index');
Route::get('/surveys/category/{category}', [SurveyController::class, 'index'])->name('surveys.byCategory');
Route::get('/surveys/{survey}/embed', [SurveyController::class, 'embed'])->name('surveys.embed');
