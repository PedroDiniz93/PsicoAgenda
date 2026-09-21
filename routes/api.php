<?php

use App\Http\Controllers\Api\AdminPsychologistController;
use App\Http\Controllers\Api\AppointmentController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\AvailabilityController;
use App\Http\Controllers\Api\FinanceController;
use App\Http\Controllers\Api\GameKitAiController;
use App\Http\Controllers\Api\GameKitChildGamesController;
use App\Http\Controllers\Api\GameKitController;
use App\Http\Controllers\Api\GameKitMemoryController;
use App\Http\Controllers\Api\GameKitRoutineController;
use App\Http\Controllers\Api\GameKitVisualActivityController;
use App\Http\Controllers\Api\GoogleCalendarController;
use App\Http\Controllers\Api\GoogleOAuthController;
use App\Http\Controllers\Api\HomeDashboardController;
use App\Http\Controllers\Api\OnlineSessionController;
use App\Http\Controllers\Api\PatientController;
use App\Http\Controllers\Api\PatientRecordController;
use App\Http\Controllers\Api\PsychologistController;
use App\Http\Controllers\Api\RecurringAppointmentController;
use App\Http\Controllers\Api\ReportController;
use App\Http\Controllers\Api\WhatsAppWebhookController;
use App\Http\Middleware\EnsurePsychologistEmailIsVerified;
use Illuminate\Support\Facades\Route;

Route::post('/auth/login', [AuthController::class, 'login'])->middleware('throttle:auth-login');
Route::post('/auth/forgot-password', [AuthController::class, 'forgotPassword'])->middleware('throttle:password-recovery');
Route::post('/auth/reset-password', [AuthController::class, 'resetPassword'])->middleware('throttle:password-reset');

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/me', [AuthController::class, 'me']);
    Route::post('/auth/email-verification/verify', [AuthController::class, 'verifyEmail']);
    Route::post('/auth/email-verification/resend', [AuthController::class, 'resendEmailVerification'])->middleware('throttle:email-verification-resend');
    Route::post('/auth/logout', [AuthController::class, 'logout']);
});

Route::get('/webhook/whatsapp', [WhatsAppWebhookController::class, 'verify']);
Route::post('/webhook/whatsapp', [WhatsAppWebhookController::class, 'receive'])->middleware('throttle:whatsapp-webhook');

Route::middleware(['auth:sanctum', EnsurePsychologistEmailIsVerified::class])->group(function () {
    Route::get('/admin/psychologists', [AdminPsychologistController::class, 'index']);
    Route::post('/admin/psychologists', [AdminPsychologistController::class, 'store']);
    Route::put('/admin/psychologists/{psychologist}', [AdminPsychologistController::class, 'update'])
        ->whereNumber('psychologist');
    Route::post('/admin/psychologists/{psychologist}/email-verification', [AdminPsychologistController::class, 'resendEmailVerification'])
        ->whereNumber('psychologist');

    Route::get('/psychologist/profile', [PsychologistController::class, 'show']);
    Route::put('/psychologist/profile', [PsychologistController::class, 'update']);
    Route::put('/psychologist/settings', [PsychologistController::class, 'updateSettings']);
    Route::get('/google/oauth/url', [GoogleOAuthController::class, 'generateUrl']);
    Route::post('/google/oauth/disconnect', [GoogleOAuthController::class, 'disconnect']);
    Route::get('/google/calendar/events', [GoogleCalendarController::class, 'events']);
    Route::delete('/google/calendar/events/{eventId}', [GoogleCalendarController::class, 'destroy']);
    Route::get('/home/dashboard', [HomeDashboardController::class, 'show']);
    Route::post('/gamekit/ai/generate', [GameKitAiController::class, 'generate'])->middleware('throttle:gamekit-ai');
    Route::post('/gamekit/default-model', [GameKitAiController::class, 'defaultModel'])->middleware('throttle:gamekit-ai');
    Route::get('/gamekit/templates', [GameKitAiController::class, 'index']);
    Route::post('/gamekit/templates', [GameKitAiController::class, 'store']);
    Route::get('/gamekit/templates/{id}', [GameKitAiController::class, 'show'])->whereNumber('id');
    Route::put('/gamekit/templates/{id}', [GameKitAiController::class, 'update'])->whereNumber('id');
    Route::patch('/gamekit/templates/{id}', [GameKitAiController::class, 'rename'])->whereNumber('id');
    Route::post('/gamekit/templates/{id}/duplicate', [GameKitAiController::class, 'duplicate'])->whereNumber('id');
    Route::delete('/gamekit/templates/{id}', [GameKitAiController::class, 'destroy'])->whereNumber('id');
    Route::post('/gamekit/templates/{id}/session', [GameKitAiController::class, 'createSession'])->whereNumber('id');
    Route::post('/gamekit/templates/{id}/generate-card', [GameKitAiController::class, 'generateCard'])->whereNumber('id');
    Route::get('/gamekit/memory/games', [GameKitMemoryController::class, 'index']);
    Route::post('/gamekit/memory/games', [GameKitMemoryController::class, 'store']);
    Route::post('/gamekit/memory/ai-generate', [GameKitMemoryController::class, 'generateWithAi'])->middleware('throttle:gamekit-ai');
    Route::get('/gamekit/memory/games/{id}', [GameKitMemoryController::class, 'show'])->whereNumber('id');
    Route::put('/gamekit/memory/games/{id}', [GameKitMemoryController::class, 'update'])->whereNumber('id');
    Route::delete('/gamekit/memory/games/{id}', [GameKitMemoryController::class, 'destroy'])->whereNumber('id');
    Route::post('/gamekit/memory/games/{id}/duplicate', [GameKitMemoryController::class, 'duplicate'])->whereNumber('id');
    Route::post('/gamekit/memory/games/{id}/sessions', [GameKitMemoryController::class, 'createSession'])->whereNumber('id');
    Route::get('/gamekit/memory/sessions/{id}', [GameKitMemoryController::class, 'session'])->whereNumber('id');
    Route::post('/gamekit/memory/sessions/{id}/link', [GameKitMemoryController::class, 'link'])->whereNumber('id');
    Route::get('/gamekit/routines', [GameKitRoutineController::class, 'index']);
    Route::post('/gamekit/routines', [GameKitRoutineController::class, 'store']);
    Route::post('/gamekit/routines/ai-generate', [GameKitRoutineController::class, 'generateWithAi'])->middleware('throttle:gamekit-ai');
    Route::get('/gamekit/routines/{id}', [GameKitRoutineController::class, 'show'])->whereNumber('id');
    Route::put('/gamekit/routines/{id}', [GameKitRoutineController::class, 'update'])->whereNumber('id');
    Route::delete('/gamekit/routines/{id}', [GameKitRoutineController::class, 'destroy'])->whereNumber('id');
    Route::post('/gamekit/routines/{id}/link', [GameKitRoutineController::class, 'link'])->whereNumber('id');
    Route::get('/gamekit/visual-activities', [GameKitVisualActivityController::class, 'index']);
    Route::post('/gamekit/visual-activities/generate', [GameKitVisualActivityController::class, 'generate'])->middleware('throttle:gamekit-ai');
    Route::get('/gamekit/visual-activities/{id}/download', [GameKitVisualActivityController::class, 'download'])->whereNumber('id')->name('gamekit.visual.download');
    Route::post('/gamekit/visual-activities/{id}/publish', [GameKitVisualActivityController::class, 'publish'])->whereNumber('id');
    Route::patch('/gamekit/visual-activities/{id}/archive', [GameKitVisualActivityController::class, 'archive'])->whereNumber('id');
    Route::post('/gamekit/hangman/ai-generate', [GameKitChildGamesController::class, 'generateHangman'])->middleware('throttle:gamekit-ai');
    Route::get('/gamekit/hangman/games', [GameKitChildGamesController::class, 'hangmanGames']);
    Route::post('/gamekit/hangman/games', [GameKitChildGamesController::class, 'storeHangman']);
    Route::put('/gamekit/hangman/games/{id}', [GameKitChildGamesController::class, 'updateHangman'])->whereNumber('id');
    Route::delete('/gamekit/hangman/games/{id}', [GameKitChildGamesController::class, 'destroyHangman'])->whereNumber('id');
    Route::post('/gamekit/hangman/games/{id}/sessions', [GameKitChildGamesController::class, 'createHangmanSession'])->whereNumber('id');
    Route::post('/gamekit/hangman/sessions/{id}/link', [GameKitChildGamesController::class, 'linkHangman'])->whereNumber('id');
    Route::post('/gamekit/tictactoe/sessions', [GameKitChildGamesController::class, 'createTicTacToe']);
    Route::post('/gamekit/tictactoe/sessions/{id}/link', [GameKitChildGamesController::class, 'linkTicTacToe'])->whereNumber('id');
    Route::get('/gamekit/sessions', [GameKitController::class, 'index']);
    Route::post('/gamekit/sessions', [GameKitController::class, 'store']);
    Route::get('/gamekit/sessions/{id}', [GameKitController::class, 'show'])->whereNumber('id');
    Route::put('/gamekit/sessions/{id}', [GameKitController::class, 'update'])->whereNumber('id');
    Route::post('/gamekit/sessions/{id}/link', [GameKitController::class, 'link'])->whereNumber('id');
    Route::post('/gamekit/sessions/{id}/finish', [GameKitController::class, 'finish'])->whereNumber('id');
    Route::post('/gamekit/sessions/{id}/review', [GameKitController::class, 'review'])->whereNumber('id');

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
    Route::get('/patients/{patient}/records/{record}/attachments/{attachment}', [PatientRecordController::class, 'downloadAttachment'])
        ->whereNumber('patient')
        ->whereNumber('record');

    Route::get('/appointments', [AppointmentController::class, 'index']);
    Route::post('/appointments', [AppointmentController::class, 'store']);
    Route::put('/appointments/{id}', [AppointmentController::class, 'update'])->whereNumber('id');
    Route::delete('/appointments/{id}', [AppointmentController::class, 'destroy'])->whereNumber('id');
    Route::post('/appointments/{id}/cancel', [AppointmentController::class, 'cancel'])->whereNumber('id');
    Route::post('/appointments/{id}/mark-done', [AppointmentController::class, 'markDone'])->whereNumber('id');
    Route::post('/appointments/{id}/mark-missed', [AppointmentController::class, 'markMissed'])->whereNumber('id');
    Route::post('/appointments/{appointment}/online-session', [OnlineSessionController::class, 'store'])->whereNumber('appointment');
    Route::get('/online-sessions/{id}', [OnlineSessionController::class, 'show'])->whereNumber('id');
    Route::post('/online-sessions/{id}/end', [OnlineSessionController::class, 'end'])->whereNumber('id');
    Route::get('/online-sessions/{id}/ice-servers', [OnlineSessionController::class, 'iceServers'])->whereNumber('id');
    Route::post('/online-sessions/{id}/signal', [OnlineSessionController::class, 'signal'])
        ->whereNumber('id')
        ->middleware('throttle:120,1');
    Route::delete('/recurring-appointments/{id}', [RecurringAppointmentController::class, 'destroy'])->whereNumber('id');

    Route::get('/availability', [AvailabilityController::class, 'index']);
    Route::put('/availability/settings', [AvailabilityController::class, 'updateSettings']);
    Route::post('/availability/blocks', [AvailabilityController::class, 'storeBlock']);
    Route::delete('/availability/blocks/{block}', [AvailabilityController::class, 'destroyBlock'])->whereNumber('block');

    Route::get('/reports/appointments', [ReportController::class, 'appointments']);

    Route::get('/finance/dashboard', [FinanceController::class, 'dashboard']);
    Route::put('/finance/settings', [FinanceController::class, 'updateSettings']);
    Route::patch('/finance/appointments/{appointment}/payment', [FinanceController::class, 'updatePayment'])
        ->whereNumber('appointment');
    Route::post('/finance/appointments/{appointment}/receipt', [FinanceController::class, 'issueReceipt'])
        ->whereNumber('appointment');
});

Route::middleware('throttle:30,1')->group(function () {
    Route::get('/online-sessions/join/{token}', [OnlineSessionController::class, 'publicShow']);
    Route::get('/gamekit/play/{token}', [GameKitController::class, 'play']);
    Route::post('/gamekit/play/{token}/responses', [GameKitController::class, 'respond']);
    Route::post('/gamekit/play/{token}/finish', [GameKitController::class, 'finishPublic']);
    Route::get('/gamekit/memory/play/{token}', [GameKitMemoryController::class, 'play']);
    Route::post('/gamekit/memory/play/{token}/result', [GameKitMemoryController::class, 'result']);
    Route::get('/gamekit/routine/play/{token}', [GameKitRoutineController::class, 'publicView']);
    Route::get('/gamekit/hangman/play/{token}', [GameKitChildGamesController::class, 'hangmanPlay']);
    Route::post('/gamekit/hangman/play/{token}/result', [GameKitChildGamesController::class, 'hangmanResult']);
    Route::get('/gamekit/tictactoe/play/{token}', [GameKitChildGamesController::class, 'ticTacToePlay']);
    Route::post('/gamekit/tictactoe/play/{token}/result', [GameKitChildGamesController::class, 'ticTacToeResult']);
});

Route::middleware('throttle:120,1')->group(function () {
    Route::post('/online-sessions/join/{token}/signal', [OnlineSessionController::class, 'publicSignal']);
    Route::get('/online-sessions/join/{token}/ice-servers', [OnlineSessionController::class, 'publicIceServers']);
});

Route::middleware('throttle:30,1')->group(function () {
    Route::get('/gamekit/visual/play/{token}', [GameKitVisualActivityController::class, 'publicView'])->name('gamekit.visual.public-view');
    Route::get('/gamekit/visual/play/{token}/image', [GameKitVisualActivityController::class, 'publicImage'])->name('gamekit.visual.public-image');
});
