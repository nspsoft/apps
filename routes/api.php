<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\WhatsappWebhookController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

// WhatsApp Webhook (Fonnte)
Route::prefix('whatsapp')->group(function () {
    Route::post('/webhook', [WhatsappWebhookController::class, 'handle']);
    Route::get('/webhook', [WhatsappWebhookController::class, 'verify']);
});

// Automated Bell Agent Desktop Client API
Route::prefix('v1/bell-agent')->group(function () {
    Route::get('/ping', [App\Http\Controllers\Api\BellAgentController::class, 'ping']);
    Route::get('/schedules', [App\Http\Controllers\Api\BellAgentController::class, 'schedules']);
    Route::get('/sound/{id}', [App\Http\Controllers\Api\BellAgentController::class, 'sound']);
});
