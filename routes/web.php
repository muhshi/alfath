<?php

use Illuminate\Support\Facades\Route;


// Route::view('/', 'dashboard')->name('dashboard');
// Route::view('/reports', 'reports')->name('reports');
// // routes/web.php
// Route::get('/dash', function () {
//     return view('dashboard', [
//         'embedUrl' => \App\Support\WilkerstatMetabase::dashboard(6),
//     ]);
// });

Route::resource('/surveys', \App\Http\Controllers\SurveyController::class);
Route::get('/surveys/{survey}/embed', [\App\Http\Controllers\SurveyController::class, 'embed'])->name('surveys.embed');
Auth::routes();

Route::get('/home', [\App\Http\Controllers\HomeController::class, 'index'])->name('home');
Route::get('/', [\App\Http\Controllers\HomeController::class, 'index'])->name('home');