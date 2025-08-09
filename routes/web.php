<?php

use App\Http\Controllers\SurveyController;
use Illuminate\Support\Facades\Route;


// Route::view('/', 'dashboard')->name('dashboard');
// Route::view('/reports', 'reports')->name('reports');
// // routes/web.php
// Route::get('/dash', function () {
//     return view('dashboard', [
//         'embedUrl' => \App\Support\WilkerstatMetabase::dashboard(6),
//     ]);
// });
Route::get('/surveys', [SurveyController::class, 'index'])->name('surveys.index');

Route::get('/surveys/category/{category}', [SurveyController::class, 'index'])
    ->name('surveys.byCategory');

Route::get('/surveys/{survey}/embed', [SurveyController::class, 'embed'])->name('surveys.embed');

Auth::routes();

Route::get('/home', [\App\Http\Controllers\HomeController::class, 'index'])->name('home');
Route::get('/', [\App\Http\Controllers\HomeController::class, 'index'])->name('home');