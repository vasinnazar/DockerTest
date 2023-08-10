<?php

use App\Http\Controllers\Api\ApiDebtorsController;
use App\Http\Controllers\Api\ApiEventsController;
use App\Http\Controllers\DaDataController;
use Illuminate\Support\Facades\Route;

Route::prefix('/debtors')->group(function () {

    Route::post('/msg/on-subdivision', [ApiDebtorsController::class,'onSubdivision']);
    Route::post('/onsite', [ApiDebtorsController::class,'onSite']);
    Route::post('/events/without-accept', [ApiEventsController::class,'withoutAcceptEvent']);
    Route::post('/suggests', [DaDataController::class, 'suggestAddress'])->name('debtors.suggests');
});
