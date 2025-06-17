<?php
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V1\Auth\LibraryAuthController;
use App\Http\Controllers\Api\V1\Auth\LearnerAuthController;

// Library login
Route::post('library/login', [LibraryAuthController::class, 'login']);
Route::middleware('auth:library_api')->group(function () {
    Route::get('library/profile', [LibraryAuthController::class, 'profile']);
    Route::post('library/logout', [LibraryAuthController::class, 'logout']);
});

// Learner login
Route::post('learner/login', [LearnerAuthController::class, 'login']);
Route::middleware('auth:learner_api')->group(function () {
    Route::get('learner/profile', [LearnerAuthController::class, 'profile']);
    Route::post('learner/logout', [LearnerAuthController::class, 'logout']);
});
