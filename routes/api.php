<?php

use App\Http\Controllers\Api\LeadWebhookController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

// Contact Form 7 Webhook - No authentication required
Route::post('/webhooks/cf7', [LeadWebhookController::class, 'store'])
    ->name('webhooks.cf7');
