<?php

use App\Http\Controllers\EasyAccessController;
use App\Http\Controllers\FlutterWaveController;
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

Route::match(['GET', 'POST'], '/v1/whatsapp/webhook', [WhatsAppController::class, 'webhook']);
Route::match(['POST', "GET"], '/v1/flutterwave/webhook', [FlutterWaveController::class, 'webhook']);
Route::match(['POST', "GET"], '/v1/easyaccess/webhook', [EasyAccessController::class, 'webhook']);

Route::get("/email/verify", [EmailVerificationController::class, 'verify'])->name('email.verify');

