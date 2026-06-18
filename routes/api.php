<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\MasterDataController;
use App\Http\Controllers\Api\Doctor\PatientController as DoctorPatientController;
use App\Http\Controllers\Api\Doctor\ProfileController as DoctorProfileController;
use App\Http\Controllers\Api\Doctor\ClinicalNoteController as DoctorClinicalNoteController;
use App\Http\Controllers\Api\Doctor\RecommendationController as DoctorRecommendationController;
use App\Http\Controllers\Api\Doctor\HistoryController as DoctorHistoryController;
use App\Http\Controllers\Api\Patient\ProfileController as PatientProfileController;
use App\Http\Controllers\Api\Patient\HealthController as PatientHealthController;
use App\Http\Controllers\Api\Patient\ConnectionController as PatientConnectionController;
use App\Http\Controllers\Api\Family\ProfileController as FamilyProfileController;
use App\Http\Controllers\Api\Family\PatientController as FamilyPatientController;
use App\Http\Controllers\Api\NotificationController;
use App\Http\Controllers\Api\Admin\DoctorController as AdminDoctorController;
use App\Http\Controllers\Api\Admin\UserController as AdminUserController;
use App\Http\Controllers\Api\VerifyEmailController;

Route::prefix('auth')->group(function () {
    Route::post('/register/doctor', [AuthController::class, 'registerDoctor']);
    Route::post('/register/patient', [AuthController::class, 'registerPatient']);
    Route::post('/register/family', [AuthController::class, 'registerFamily']);
    Route::post('/login', [AuthController::class, 'login']);

    Route::post('/forgot-password', [AuthController::class, 'forgotPassword']);
    Route::post('/reset-password', [AuthController::class, 'resetPassword']);

    Route::get('/email/verify/{id}/{hash}', VerifyEmailController::class)
        ->middleware('signed')
        ->name('verification.verify');

    Route::post('/email/resend', [AuthController::class, 'resendVerificationEmail']);
    Route::post('/email/check', [AuthController::class, 'checkEmailVerification']);

    Route::middleware('auth:sanctum')->put('/change-password', [AuthController::class, 'changePassword']);
    Route::middleware('auth:sanctum')->post('/logout', [AuthController::class, 'logout']);
});

Route::prefix('master')->group(function () {
    Route::get('/specializations', [MasterDataController::class, 'specializations']);
    Route::get('/activity-types', [MasterDataController::class, 'activityTypes']);
    Route::get('/meal-types', [MasterDataController::class, 'mealTypes']);
    Route::get('/blood-types', [MasterDataController::class, 'bloodTypes']);
    Route::get('/rhesus-types', [MasterDataController::class, 'rhesusTypes']);
    Route::get('/relation-types', [MasterDataController::class, 'relationTypes']);
    Route::get('/clinical-parameters', [MasterDataController::class, 'clinicalParameters']);
});

Route::prefix('doctor')->group(function () {
    Route::get('/patients/{doctorId}', [DoctorPatientController::class, 'index']);
    Route::get('/patients/{patientId}/dashboard', [DoctorPatientController::class, 'dashboard']);
    Route::get('/patients/{patientId}/glucose', [DoctorPatientController::class, 'glucose']);
    Route::get('/patients/{patientId}/physiological', [DoctorPatientController::class, 'physiological']);
    Route::get('/patients/{patientId}/behavioral', [DoctorPatientController::class, 'behavioral']);
    Route::get('/patients/{patientId}/medication', [DoctorPatientController::class, 'medication']);

    Route::get('/patients/{patientId}/thresholds', [DoctorPatientController::class, 'thresholds']);
    Route::put('/patients/{patientId}/thresholds/{parameterId}', [DoctorPatientController::class, 'updateThreshold']);
    Route::delete('/patients/{patientId}/thresholds/{parameterId}', [DoctorPatientController::class, 'resetThreshold']);

    Route::delete('/patients/{patientId}', [DoctorPatientController::class, 'disconnect']);

    Route::get('/profile/{doctorId}', [DoctorProfileController::class, 'show']);
    Route::put('/profile/{doctorId}', [DoctorProfileController::class, 'update']);

    Route::post('/patients/{patientId}/clinical-notes', [DoctorClinicalNoteController::class, 'store']);
    Route::get('/patients/{patientId}/clinical-notes', [DoctorClinicalNoteController::class, 'getByPatient']);
    Route::get('/clinical-notes/{clinicalNoteId}', [DoctorClinicalNoteController::class, 'show']);

    Route::post('/clinical-notes/{clinicalNoteId}/recommendation', [DoctorRecommendationController::class, 'store']);
    Route::get('/clinical-notes/{clinicalNoteId}/recommendation', [DoctorRecommendationController::class, 'show']);

    Route::get('/patients/{patientId}/families', [DoctorPatientController::class, 'families']);

    Route::get('/connection-requests/{doctorId}', [DoctorPatientController::class, 'connectionRequests']);

    Route::post('/connection-requests/{patientId}/accept', [DoctorPatientController::class, 'acceptConnection']);
    Route::post('/connection-requests/{patientId}/reject', [DoctorPatientController::class, 'rejectConnection']);
    Route::get('/connection-requests/{doctorId}/rejected', [DoctorPatientController::class, 'rejectedConnectionRequests']);
    Route::get('/connections/status/{patientId}', [DoctorPatientController::class, 'connectionStatus']);

    Route::get('/history/{doctorId}', [DoctorHistoryController::class, 'index']);
});

Route::prefix('patient')->group(function () {
    Route::get('/profile/{patientId}', [PatientProfileController::class, 'show']);
    Route::put('/profile/{patientId}', [PatientProfileController::class, 'update']);

    Route::post('/health/glucose', [PatientHealthController::class, 'storeGlucose']);
    Route::post('/health/physiological', [PatientHealthController::class, 'storePhysiological']);
    Route::post('/health/activity', [PatientHealthController::class, 'storeActivity']);
    Route::post('/health/meal', [PatientHealthController::class, 'storeMeal']);
    Route::post('/health/medication', [PatientHealthController::class, 'storeMedication']);
    Route::get('/health-history/{patientId}', [PatientHealthController::class, 'history']);

    Route::get('/find-doctors', [PatientConnectionController::class, 'findDoctors']);
    Route::post('/connection-requests', [PatientConnectionController::class, 'requestDoctor']);
    Route::get('/doctors/{patientId}', [PatientConnectionController::class, 'doctors']);
    Route::get('/doctors/{patientId}/{doctorId}', [PatientConnectionController::class, 'doctorDetail']);
    Route::delete('/doctors/{doctorId}', [PatientConnectionController::class, 'disconnectDoctor']);
});

Route::prefix('family')->group(function () {
    Route::get('/profile/{familyId}', [FamilyProfileController::class, 'show']);
    Route::put('/profile/{familyId}', [FamilyProfileController::class, 'update']);

    Route::post('/find-patient', [FamilyPatientController::class, 'findPatient']);
    Route::post('/connection-requests', [FamilyPatientController::class, 'requestConnection']);
    Route::get('/patients/{familyId}', [FamilyPatientController::class, 'patients']);
    Route::delete('/patients/{patientId}', [FamilyPatientController::class, 'disconnect']);

    Route::get('/patients/{patientId}/dashboard', [FamilyPatientController::class, 'dashboard']);
    Route::get('/patients/{patientId}/health-data', [FamilyPatientController::class, 'healthData']);
    Route::get('/patients/{patientId}/clinical-notes', [FamilyPatientController::class, 'clinicalNotes']);
    Route::get('/patients/{patientId}/recommendations', [FamilyPatientController::class, 'recommendations']);
});

Route::prefix('notifications')->group(function () {
    Route::get('/user/{userId}', [NotificationController::class, 'index']);
    Route::post('/', [NotificationController::class, 'store']);
    Route::patch('/{notificationId}/read', [NotificationController::class, 'markAsRead']);
    Route::patch('/user/{userId}/read-all', [NotificationController::class, 'markAllAsRead']);
});

Route::prefix('admin')->group(function () {
    Route::get('/doctors/pending', [AdminDoctorController::class, 'pending']);
    Route::post('/doctors/{doctorId}/verify', [AdminDoctorController::class, 'verify']);
    Route::post('/doctors/{doctorId}/reject', [AdminDoctorController::class, 'reject']);

    Route::get('/users', [AdminUserController::class, 'index']);
    Route::patch('/users/{userId}/status', [AdminUserController::class, 'updateStatus']);
    Route::get('/dashboard', [AdminUserController::class, 'dashboard']);
});
