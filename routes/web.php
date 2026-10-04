<?php

use App\Http\Controllers\CareerOpeningController;
use App\Http\Controllers\DataRightsRequestController;
use App\Http\Controllers\JobApplicationController;
use App\Http\Controllers\ListingSubmissionController;
use App\Http\Controllers\WebsiteController;
use Illuminate\Support\Facades\Route;

Route::get('/', [WebsiteController::class, 'home'])->name('home');
Route::get('/story', [WebsiteController::class, 'story'])->name('story');

Route::get('/jobs', [WebsiteController::class, 'jobs'])->name('jobs.index');
Route::post('/jobs/apply', [JobApplicationController::class, 'store'])->middleware('throttle:6,1')->name('jobs.apply.unified');
Route::post('/privacy-rights/requests', [DataRightsRequestController::class, 'store'])->name('privacy-rights.store');
Route::get('/jobs/{jobListing}', [WebsiteController::class, 'showJob'])->name('jobs.show'); // Hidden but kept for backwards compatibility
Route::post('/jobs/{jobListing}/apply', [ListingSubmissionController::class, 'storeJob'])->name('jobs.apply'); // Old apply route

Route::get('/careers/organizations/{careerOrganization}', [CareerOpeningController::class, 'organization'])->name('careers.organizations.show');
Route::get('/careers/{careerOpening}', [CareerOpeningController::class, 'show'])->name('careers.show');
Route::post('/careers/{careerOpening}/apply', [CareerOpeningController::class, 'store'])->middleware('throttle:6,1')->name('careers.apply');

Route::get('/tenders', [WebsiteController::class, 'tenders'])->name('tenders.index');
Route::get('/tenders/{tenderListing}', [WebsiteController::class, 'showTender'])->name('tenders.show');
Route::post('/tenders/{tenderListing}/apply', [ListingSubmissionController::class, 'storeTender'])->name('tenders.apply');
