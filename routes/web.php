<?php

use Tish\LaravelMpesa\Http\Controllers\MpesaController;

Route::prefix('mpesa')->name('mpesa.')->group(function () {
    Route::post('stk-push', [MpesaController::class, 'stkPush'])->name('stk-push');
    Route::post('callback', [MpesaController::class, 'callback'])->name('callback');
    Route::post('stk-query', [MpesaController::class, 'stkQuery'])->name('stk-query');
    Route::get('status/{checkoutRequestId}', [MpesaController::class, 'getTransactionStatus'])->name('status');
});