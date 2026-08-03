<?php

use App\Http\Controllers\PdfController;
use App\Http\Controllers\PublicQuoteController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect('/admin');
});

// Public Quote Routes (signed URLs, no authentication required)
Route::get('/offerte/{quote}', [PublicQuoteController::class, 'show'])
    ->name('quotes.public.show')
    ->middleware('signed');

Route::get('/offerte/{quote}/pdf', [PublicQuoteController::class, 'pdf'])
    ->name('quotes.public.pdf')
    ->middleware('signed');

Route::post('/offerte/{quote}/ondertekenen', [PublicQuoteController::class, 'sign'])
    ->name('quotes.public.sign')
    ->middleware('signed');

Route::get('/algemene-voorwaarden', [PublicQuoteController::class, 'terms'])
    ->name('terms');

// PDF Download Routes
Route::middleware(['auth'])->group(function () {
    Route::get('/quotes/{quote}/pdf', [PdfController::class, 'quotePdf'])
        ->name('quotes.pdf');

    Route::get('/projects/{project}/work-ticket-pdf', [PdfController::class, 'workTicket'])
        ->name('projects.work-ticket-pdf');

    Route::get('/projects/{project}/packing-slip-pdf', [PdfController::class, 'packingSlip'])
        ->name('projects.packing-slip-pdf');


    Route::get('/projects/{project}/order-confirmation-pdf', [PdfController::class, 'orderConfirmationPdf'])
        ->name('projects.order-confirmation-pdf');

    Route::get('/projects/{project}/completion-report-pdf', [PdfController::class, 'completionReport'])
        ->name('projects.completion-report-pdf');

    Route::get('/purchase-orders/{purchaseOrder}/pdf', [PdfController::class, 'purchaseOrderPdf'])
        ->name('purchase-orders.pdf');
});
