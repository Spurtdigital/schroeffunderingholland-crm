<?php

use App\Http\Controllers\PdfController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect('/admin');
});

// PDF Download Routes
Route::middleware(['auth'])->group(function () {
    Route::get('/quotes/{quote}/pdf', [PdfController::class, 'quotePdf'])
        ->name('quotes.pdf');

    Route::get('/projects/{project}/packing-slip-pdf', [PdfController::class, 'packingSlip'])
        ->name('projects.packing-slip-pdf');

    Route::get('/projects/{project}/order-confirmation-pdf', [PdfController::class, 'orderConfirmationPdf'])
        ->name('projects.order-confirmation-pdf');

    Route::get('/projects/{project}/completion-report-pdf', [PdfController::class, 'completionReport'])
        ->name('projects.completion-report-pdf');

    Route::get('/purchase-orders/{purchaseOrder}/pdf', [PdfController::class, 'purchaseOrderPdf'])
        ->name('purchase-orders.pdf');
});
