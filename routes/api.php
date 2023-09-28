<?php

use App\Http\Controllers\Api\ApiDebtorsController;
use App\Http\Controllers\Api\ApiEventsController;
use App\Http\Controllers\FromDebtorsController;
use Illuminate\Support\Facades\Route;

Route::prefix('/debtors')->group(function () {

    Route::post('/getResponsibleUser', [FromDebtorsController::class,'respUserForSellingARM'])->middleware(['auth.basic.once']);
    Route::post('/msg/on-subdivision', [ApiDebtorsController::class,'onSubdivision']);
    Route::post('/onsite', [ApiDebtorsController::class,'onSite']);
    Route::post('/events/without-accept', [ApiEventsController::class,'withoutAcceptEvent']);

});
