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
Route::get('/surveys', [SurveyController::class , 'index'])->name('surveys.index');

Route::get('/surveys/category/{category}', [SurveyController::class , 'index'])
    ->name('surveys.byCategory');

Route::get('/surveys/{survey}/embed', [SurveyController::class , 'embed'])->name('surveys.embed');

Auth::routes();

Route::get('/', [\App\Http\Controllers\HomeController::class , 'index'])->name('dashboard');

Route::get('/home', [\App\Http\Controllers\HomeController::class , 'index'])->name('home');
Route::get('/dashboard-se2026', [\App\Http\Controllers\ExecutiveDashboardController::class , 'index'])->name('dashboard.se2026');
Route::get('/dashboard-pengolahan', [\App\Http\Controllers\PengolahanController::class , 'index'])->name('dashboard.pengolahan');
Route::get('/dashboard-pengolahan/export', [\App\Http\Controllers\PengolahanController::class , 'export'])->name('dashboard.pengolahan.export');
Route::post('/dashboard-pengolahan/catatan-anomali', [\App\Http\Controllers\PengolahanController::class , 'simpanCatatanAnomali'])->name('dashboard.pengolahan.catatan-anomali');
Route::get('/debug-db', function () {
    try {
        return 'Conn: ' . (new \App\Models\ScraperCookie)->getConnectionName();
    }
    catch (\Exception $e) {
        return $e->getMessage();
    }
});