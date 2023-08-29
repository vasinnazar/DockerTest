<?php

use App\Http\Controllers\Api\ApiDebtorsController;
use App\Http\Controllers\Api\ApiEventsController;
use Illuminate\Support\Facades\Route;

Route::prefix('/debtors')->group(function () {

    Route::post('/msg/on-subdivision', [ApiDebtorsController::class,'onSubdivision']);
    Route::post('/onsite', [ApiDebtorsController::class,'onSite']);
    Route::post('/events/without-accept', [ApiEventsController::class,'withoutAcceptEvent']);

});
