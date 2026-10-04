<?php

use App\Http\Controllers\Api\CareerPageApplicationController;
use App\Http\Controllers\Api\JobApplicationController;
use Illuminate\Support\Facades\Route;

Route::get('/job-applications', [JobApplicationController::class, 'index']);

Route::post('/job-pages/apply', CareerPageApplicationController::class)
    ->middleware('throttle:5,60')
    ->name('job-pages.apply');
