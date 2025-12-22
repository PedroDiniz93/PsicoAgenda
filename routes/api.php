<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\PatientController;
use App\Http\Controllers\Api\AppointmentController;
use App\Http\Controllers\Api\PsychologistController;
use App\Http\Controllers\Api\GoogleOAuthController;
use App\Http\Controllers\Api\PatientRecordController;
use App\Http\Controllers\Api\RecurringAppointmentController;
use App\Http\Controllers\Api\ReportController;
use App\Http\Controllers\Api\WhatsAppWebhookController;

Route::post('/auth/login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/me', [AuthController::class, 'me']);
    Route::post('/auth/logout', [AuthController::class, 'logout']);
});

Route::get('/webhook/whatsapp', [WhatsAppWebhookController::class, 'verify']);
Route::post('/webhook/whatsapp', [WhatsAppWebhookController::class, 'receive']);

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/psychologist/profile', [PsychologistController::class, 'show']);
    Route::put('/psychologist/profile', [PsychologistController::class, 'update']);
    Route::put('/psychologist/settings', [PsychologistController::class, 'updateSettings']);
    Route::get('/google/oauth/url', [GoogleOAuthController::class, 'generateUrl']);
    Route::post('/google/oauth/disconnect', [GoogleOAuthController::class, 'disconnect']);

    Route::get('/patients', [PatientController::class, 'index']);
    Route::post('/patients', [PatientController::class, 'store']);
    Route::post('/patients/export', [PatientController::class, 'bulkExport']);
    Route::get('/patients/inactivity-alerts', [PatientController::class, 'inactivityAlerts']);
    Route::post('/patients/inactivity-alerts/acknowledge', [PatientController::class, 'acknowledgeInactivityAlerts']);
    Route::get('/patients/{id}', [PatientController::class, 'show'])->whereNumber('id');
    Route::get('/patients/{id}/export', [PatientController::class, 'export'])->whereNumber('id');
    Route::put('/patients/{id}', [PatientController::class, 'update'])->whereNumber('id');
    Route::delete('/patients/{id}', [PatientController::class, 'destroy'])->whereNumber('id');

    Route::get('/patients/{patient}/records', [PatientRecordController::class, 'index'])->whereNumber('patient');
    Route::post('/patients/{patient}/records', [PatientRecordController::class, 'store'])->whereNumber('patient');
    Route::put('/patients/{patient}/records/{record}', [PatientRecordController::class, 'update'])
        ->whereNumber('patient')
        ->whereNumber('record');
    Route::delete('/patients/{patient}/records/{record}', [PatientRecordController::class, 'destroy'])
        ->whereNumber('patient')
        ->whereNumber('record');

    Route::get('/appointments', [AppointmentController::class, 'index']);
    Route::post('/appointments', [AppointmentController::class, 'store']);
    Route::put('/appointments/{id}', [AppointmentController::class, 'update'])->whereNumber('id');
    Route::post('/appointments/{id}/cancel', [AppointmentController::class, 'cancel'])->whereNumber('id');
    Route::post('/appointments/{id}/mark-done', [AppointmentController::class, 'markDone'])->whereNumber('id');
    Route::post('/appointments/{id}/mark-missed', [AppointmentController::class, 'markMissed'])->whereNumber('id');
    Route::delete('/recurring-appointments/{id}', [RecurringAppointmentController::class, 'destroy'])->whereNumber('id');

    Route::get('/reports/appointments', [ReportController::class, 'appointments']);
});
