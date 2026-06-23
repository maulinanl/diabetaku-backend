<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\MasterDataController;
use App\Http\Controllers\Api\Doctor\PatientController as DoctorPatientController;
use App\Http\Controllers\Api\Doctor\ProfileController as DoctorProfileController;
use App\Http\Controllers\Api\Doctor\ClinicalNoteController as DoctorClinicalNoteController;
use App\Http\Controllers\Api\Doctor\RecommendationController as DoctorRecommendationController;
use App\Http\Controllers\Api\Doctor\HistoryController as DoctorHistoryController;
use App\Http\Controllers\Api\Doctor\PrescriptionController;
use App\Http\Controllers\Api\Patient\ProfileController as PatientProfileController;
use App\Http\Controllers\Api\Patient\HealthController as PatientHealthController;
use App\Http\Controllers\Api\Patient\ConnectionController as PatientConnectionController;
use App\Http\Controllers\Api\Patient\PatientController as PatientController;
use App\Http\Controllers\Api\Family\ProfileController as FamilyProfileController;
use App\Http\Controllers\Api\Family\PatientController as FamilyPatientController;
use App\Http\Controllers\Api\Family\HealthController as FamilyHealthController;
use App\Http\Controllers\Api\NotificationController;
use App\Http\Controllers\Api\Admin\DoctorController as AdminDoctorController;
use App\Http\Controllers\Api\Admin\UserController as AdminUserController;
use App\Http\Controllers\Api\VerifyEmailController;

Route::prefix('auth')->group(function () {
    Route::post('/register/doctor', [AuthController::class, 'registerDoctor']);
    Route::post('/register/patient', [AuthController::class, 'registerPatient']);
    Route::post('/register/family', [AuthController::class, 'registerFamily']);
    Route::post('/login', [AuthController::class, 'login']);
    Route::get('/check-email', [AuthController::class, 'checkEmail']);

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
    Route::get('/prescription-meal-rules', [MasterDataController::class, 'prescriptionMealRules']);
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

    Route::get('/medications/search', [PrescriptionController::class, 'searchMedications']);
    Route::get('/medication-sessions', [PrescriptionController::class, 'sessions']);

    Route::get('/patients/{patientId}/prescriptions/active', [PrescriptionController::class, 'active']);
    Route::get('/patients/{patientId}/prescriptions/history', [PrescriptionController::class, 'history']);
    Route::post('/patients/{patientId}/prescriptions', [PrescriptionController::class, 'store']);

    Route::get('/prescriptions/{prescriptionId}', [PrescriptionController::class, 'show']);
    Route::put('/prescriptions/{prescriptionId}', [PrescriptionController::class, 'update']);
    Route::patch('/prescriptions/{prescriptionId}/stop', [PrescriptionController::class, 'stop']);
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
    Route::get('/recommendations/{patientId}', [PatientHealthController::class, 'recommendations']);
    Route::get('/latest-recommendation/{patientId}',[PatientHealthController::class, 'latestRecommendation']);
    Route::get('/prescriptions/{patientId}/active', [PatientHealthController::class, 'activePrescriptions']);
    Route::get('/pending-validations/{patientId}', [PatientHealthController::class, 'pendingValidations']);
    Route::post('/respond-validation', [PatientHealthController::class, 'respondValidation']);

    Route::get('/connections/doctors/{patientId}', [PatientConnectionController::class, 'connectedDoctors']);
    Route::get('/connections/families/{patientId}', [PatientConnectionController::class, 'connectedFamilies']);
    Route::get('/connections/requests/{patientId}', [PatientConnectionController::class, 'incomingRequests']);

    Route::get('/doctors/search', [PatientConnectionController::class, 'searchDoctors']);
    Route::post('/doctors/{doctorId}/request', [PatientConnectionController::class, 'requestDoctorConnection']);

    Route::post('/family-requests/accept', [PatientConnectionController::class, 'acceptFamilyRequest']);
    Route::post('/family-requests/reject', [PatientConnectionController::class, 'rejectFamilyRequest']);

    Route::delete('/connections/doctors/{connectionId}', [PatientConnectionController::class, 'disconnectDoctor']);
    Route::delete('/connections/families/{connectionId}', [PatientConnectionController::class, 'disconnectFamily']);

    Route::get('/home-summary/{patientId}', [PatientController::class, 'homeSummary']);

    Route::get('/dashboard/{patientId}', [PatientProfileController::class, 'dashboard']);
});

Route::prefix('family')->group(function () {
    Route::get('/profile/{familyId}', [FamilyProfileController::class, 'show']);
    Route::put('/profile/{familyId}', [FamilyProfileController::class, 'update']);

    Route::get('/patients/{familyId}', [FamilyPatientController::class, 'patients']);
    Route::get('/patient-detail/{patientId}', [FamilyPatientController::class, 'show']);

    Route::get('/patients/{patientId}/dashboard', [FamilyPatientController::class, 'dashboard']);
    Route::get('/patients/{patientId}/health-data', [FamilyPatientController::class, 'healthData']);
    Route::get('/patients/{patientId}/clinical-notes', [FamilyPatientController::class, 'clinicalNotes']);
    Route::get('/patients/{patientId}/histories', [FamilyPatientController::class, 'histories']);
    Route::get('/patients/{patientId}/recommendations', [FamilyPatientController::class, 'recommendations']);

    Route::post('/find-patient', [FamilyPatientController::class, 'findPatient']);
    Route::post('/request-connection', [FamilyPatientController::class, 'requestConnection']);
    Route::delete('/patients/{patientId}/disconnect', [FamilyPatientController::class, 'disconnect']);

    Route::post('/patients/{patientId}/glucose', [FamilyHealthController::class, 'storeGlucose']);
    Route::post('/patients/{patientId}/physiological', [FamilyHealthController::class, 'storePhysiological']);
    Route::post('/patients/{patientId}/activity', [FamilyHealthController::class, 'storeActivity']);
    Route::post('/patients/{patientId}/meal', [FamilyHealthController::class, 'storeMeal']);
    Route::post('/patients/{patientId}/medication', [FamilyHealthController::class, 'storeMedication']);
});

Route::prefix('notifications')->group(function () {

    Route::get('/user/{userId}', [NotificationController::class, 'index']);

    Route::patch('/{notificationId}/read', [
        NotificationController::class,
        'markAsRead'
    ]);

    Route::patch('/user/{userId}/read-all', [
        NotificationController::class,
        'markAllAsRead'
    ]);

    Route::post('/', [
        NotificationController::class,
        'store'
    ]);
});

Route::prefix('admin')->group(function () {
    Route::get('/doctors/pending', [AdminDoctorController::class, 'pending']);
    Route::post('/doctors/{doctorId}/verify', [AdminDoctorController::class, 'verify']);
    Route::post('/doctors/{doctorId}/reject', [AdminDoctorController::class, 'reject']);

    Route::get('/users', [AdminUserController::class, 'index']);
    Route::patch('/users/{userId}/status', [AdminUserController::class, 'updateStatus']);
    Route::get('/dashboard', [AdminUserController::class, 'dashboard']);
});
