<?php

use App\Http\Controllers\MonnifyController;
use App\Http\Controllers\WhatsAppController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::post('/v1/monnify/webhook', [MonnifyController::class, 'webhook']);
Route::post('/v1/whatsapp/webhook', [WhatsAppController::class, 'webhook']);
Route::get('/v1/whatsapp/webhook', [WhatsAppController::class, 'webhook']);
Route::get("/email/verify", [EmailVerificationController::class, 'verify'])->name('email.verify');

