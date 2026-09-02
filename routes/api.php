<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\TicketCounterApiController;
use App\Http\Controllers\Api\TicketCheckerApiController;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::prefix('tickets')->group(function () {
    Route::get('available/{id}', [TicketCounterApiController::class, 'getAvailableQuantity']);
    Route::post('check-bulk-discount', [TicketCounterApiController::class, 'checkBulkDiscount']);
    Route::post('apply-coupon', [TicketCounterApiController::class, 'applyCoupon']);
    Route::post('calculate-bill', [TicketCounterApiController::class, 'calculateBill']);
    Route::post('purchase', [TicketCounterApiController::class, 'store']);
});


Route::prefix('checker')->group(function () {
    // Login and send otp over mail
    Route::post('/login', [TicketCheckerApiController::class, 'login']);

    //OTP verify and generate token
    Route::post('/verify-otp', [TicketCheckerApiController::class, 'verifyOtp']);
    
    //Resend OTP verify and generate token
    Route::post('/resend-otp', [TicketCheckerApiController::class, 'resendOtp']);

    Route::middleware('auth:sanctum')->group(function () {
        // Check Car validity
        Route::post('/check-car-ticket', [TicketCheckerApiController::class, 'checkCarTicket']);  

        // Check additional service pass validity
        Route::post('/check-service-ticket', [TicketCheckerApiController::class, 'checkServiceTicket']);
       
        // Check Ticket validity
        Route::post('/check-ticket', [TicketCheckerApiController::class, 'checkTicket']);  

        // View Profile
        Route::post('/view-profile', [TicketCheckerApiController::class, 'viewProfile']);

        // Logout
        Route::post('/logout', [TicketCheckerApiController::class, 'logout']);
    });
});
