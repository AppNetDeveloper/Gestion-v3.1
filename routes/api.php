<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\GeneralSettingsController;
use App\Http\Controllers\Api\GeneralSettingsMediaController;
use App\Http\Controllers\Api\PermissionController;
use App\Http\Controllers\Api\ProfileController;
use App\Http\Controllers\Api\RoleController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\EnvironmentController;
use App\Http\Controllers\Api\DatabaseBackupController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\ServerMonitorController;
use App\Http\Controllers\Api\WhatsappMessageController;
use App\Http\Controllers\Api\TelegramController;
use App\Http\Controllers\Api\WhatsappSessionController;
use App\Http\Controllers\Api\ScrapingCallbackController; // Importar el controlador
use App\Http\Controllers\Api\OllamaTaskerController;

/*
 * API Routes
 */
Route::post('register', [AuthController::class, 'register']);
Route::post('login', [AuthController::class, 'login']);
// OAuth
Route::post('login-oauth', [AuthController::class, 'social']);

Route::post('forgot-password', [AuthController::class, 'forgotPassword']);

// Verify new email after change
Route::get('profile-verify-new-email/{token}',
    [ProfileController::class, 'verifyNewEmail'])->name('profile.verify-new-email');

// Ollama Tasker API Routes (solo requieren el token de API)
Route::prefix('ollama-tasks')->middleware('ollama.token')->group(function () {
    Route::post('/', [OllamaTaskerController::class, 'createTask']);
    Route::get('/{id}', [OllamaTaskerController::class, 'getTaskResult']);
});

// WhatsApp Proxy API Routes (requieren el token de WhatsApp)
Route::prefix('whatsapp')->middleware('whatsapp.token')->group(function () {
    // Sesiones
    Route::get('/sessions', [\App\Http\Controllers\Api\WhatsAppProxyController::class, 'getSessions']);
    Route::post('/start-session', [\App\Http\Controllers\Api\WhatsAppProxyController::class, 'startSession']);
    
    // Mensajes
    Route::post('/send-message', [\App\Http\Controllers\Api\WhatsAppProxyController::class, 'sendMessage']);
    Route::post('/send-media', [\App\Http\Controllers\Api\WhatsAppProxyController::class, 'sendMedia']);
    
    // Chats y mensajes
    Route::get('/chats', [\App\Http\Controllers\Api\WhatsAppProxyController::class, 'getChats']);
    Route::get('/messages', [\App\Http\Controllers\Api\WhatsAppProxyController::class, 'getMessages']);
    
    // Multimedia
    Route::get('/download-media', [\App\Http\Controllers\Api\WhatsAppProxyController::class, 'downloadMedia']);
    
    // Contactos
    Route::get('/contact-info', [\App\Http\Controllers\Api\WhatsAppProxyController::class, 'getContactInfo']);
});

// Telegram Proxy API Routes (requieren el token de Telegram)
Route::prefix('telegram')->middleware('telegram.token')->group(function () {
    // Mensajes
    Route::post('/send-message', [\App\Http\Controllers\Api\TelegramProxyController::class, 'sendMessage']);
    Route::post('/send-photo', [\App\Http\Controllers\Api\TelegramProxyController::class, 'sendPhoto']);
    
    // Actualizaciones
    Route::get('/updates', [\App\Http\Controllers\Api\TelegramProxyController::class, 'getUpdates']);
    
    // Información del bot
    Route::get('/me', [\App\Http\Controllers\Api\TelegramProxyController::class, 'getMe']);
    
    // Chats
    Route::get('/chats', [\App\Http\Controllers\Api\TelegramProxyController::class, 'getChats']);
    Route::get('/chat-info', [\App\Http\Controllers\Api\TelegramProxyController::class, 'getChatInfo']);
    Route::get('/chat-messages', [\App\Http\Controllers\Api\TelegramProxyController::class, 'getChatMessages']);
    
    // Contactos
    Route::get('/contacts', [\App\Http\Controllers\Api\TelegramProxyController::class, 'getContacts']);
});

// authenticated routes (requieren autenticación de usuario)
Route::group(['middleware' => 'auth:sanctum'], function () {
    Route::post('resend-verification', [AuthController::class, 'resendVerification'])
        ->middleware('throttle:6,1');
    Route::get('user', [AuthController::class, 'user']);
    Route::post('logout', [AuthController::class, 'logout']);

    Route::apiSingleton('env', EnvironmentController::class);
    Route::group(['middleware' => 'verified', 'as' => 'api.v1.'], function () {
        Route::post('password-change', [AuthController::class, 'changePassword']);
        Route::apiResource('users', UserController::class);
        Route::delete('users-delete-many', [UserController::class, 'destroyMany']);
        Route::apiResource('permissions', PermissionController::class);
        Route::resource('roles', RoleController::class)->except('edit');
        Route::apiSingleton('profile', ProfileController::class);
        Route::put('general-settings-images', GeneralSettingsMediaController::class);
        // Database Backup
        Route::apiResource('database-backups', DatabaseBackupController::class)->only(['index', 'destroy']);
        Route::get('database-backups-create', [DatabaseBackupController::class,'createBackup']);
        Route::get('database-backups-download/{fileName}', [DatabaseBackupController::class, 'databaseBackupDownload']);
    });
});


// General Settings
Route::get('general-settings', GeneralSettingsController::class);
// api.php
Route::post('/server-monitor', [ServerMonitorController::class, 'store']);
//api para iniciar sesion de whatsapp
Route::get('whatsapp/logout', [WhatsappSessionController::class, 'logout']);
Route::post('whatsapp/start-session', [WhatsappSessionController::class, 'startSession']);

//Api para insertas los mesajes de whatsapp
Route::post('/whatsapp-messages', [WhatsappMessageController::class, 'store']);
//whatsapp api sesion
Route::get('whatsapp/check', [WhatsappSessionController::class, 'checkSession']);
//send whatsapp
Route::post('/whatsapp/send-message-now', [WhatsappMessageController::class, 'sendMessageNow'])->name('api.whatsapp.send.message.now');

//api para insertar mesajes telegram

Route::post('/telegram', [TelegramController::class, 'store']);

// Ruta para recibir el callback de la API de scraping
Route::post('/scraping-callback', [ScrapingCallbackController::class, 'handleCallback'])->name('api.scraping.callback');
