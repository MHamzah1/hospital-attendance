<?php

use Illuminate\Support\Facades\Route;

// Check if user agent is mobile and add fallback routes
$userAgent = request()->header('User-Agent', '');
$isMobile = preg_match('/Mobile|Android|iPhone|iPad/i', $userAgent);

if ($isMobile) {
    Route::get('/mobile', function () {
        return view('mobile.dashboard');
    })->name('mobile.dashboard');
    
    Route::get('/mobile/login', function () {
        return view('mobile.login');
    })->name('mobile.login');
    
    Route::get('/mobile/attendance', function () {
        return view('mobile.attendance');
    })->name('mobile.attendance');
}