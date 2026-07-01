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
use App\Http\Controllers\Api\Caregiver\ProfileController as CaregiverProfileController;
use App\Http\Controllers\Api\Caregiver\PatientController as CaregiverPatientController;
use App\Http\Controllers\Api\Caregiver\HealthController as CaregiverHealthController;
use App\Http\Controllers\Api\NotificationController;
use App\Http\Controllers\Api\Admin\DoctorController as AdminDoctorController;
use App\Http\Controllers\Api\Admin\UserController as AdminUserController;
use App\Http\Controllers\Api\VerifyEmailController;

Route::prefix('auth')->group(function () {
    Route::post('/register/doctor', [AuthController::class, 'registerDoctor']);
    Route::post('/register/patient', [AuthController::class, 'registerPatient']);
    Route::post('/register/caregiver', [AuthController::class, 'registerCaregiver']);
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
    Route::get('/dosage-forms', [MasterDataController::class, 'dosageForms']);
    Route::get('/recommendation-categories', [MasterDataController::class, 'recommendationCategories']);
    Route::get('/diabetes-types', [MasterDataController::class, 'diabetesTypes']);
    Route::get('/genders', [MasterDataController::class, 'genders']);
});

Route::prefix('doctor')->middleware(['auth:sanctum', 'role:2'])->group(function () {
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

    Route::get('/patients/{patientId}/caregivers', [DoctorPatientController::class, 'caregivers']);

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

Route::prefix('patient')->middleware(['auth:sanctum', 'role:3'])->group(function () {
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
    Route::get('/connections/caregivers/{patientId}', [PatientConnectionController::class, 'connectedCaregivers']);
    Route::get('/connections/requests/{patientId}', [PatientConnectionController::class, 'incomingRequests']);

    Route::get('/doctors/search', [PatientConnectionController::class, 'searchDoctors']);
    Route::post('/doctors/{doctorId}/request', [PatientConnectionController::class, 'requestDoctorConnection']);

    Route::post('/caregiver-requests/accept', [PatientConnectionController::class, 'acceptCaregiverRequest']);
    Route::post('/caregiver-requests/reject', [PatientConnectionController::class, 'rejectCaregiverRequest']);

    Route::delete('/connections/doctors/{connectionId}', [PatientConnectionController::class, 'disconnectDoctor']);
    Route::delete('/connections/caregivers/{connectionId}', [PatientConnectionController::class, 'disconnectCaregiver']);

    Route::get('/home-summary/{patientId}', [PatientController::class, 'homeSummary']);

    Route::get('/dashboard/{patientId}', [PatientProfileController::class, 'dashboard']);
});

Route::prefix('caregiver')->middleware(['auth:sanctum', 'role:4'])->group(function () {
    Route::get('/profile/{caregiverId}', [CaregiverProfileController::class, 'show']);
    Route::put('/profile/{caregiverId}', [CaregiverProfileController::class, 'update']);

    Route::get('/patients/{caregiverId}', [CaregiverPatientController::class, 'patients']);
    Route::get('/patient-detail/{patientId}', [CaregiverPatientController::class, 'show']);

    Route::get('/patients/{patientId}/dashboard', [CaregiverPatientController::class, 'dashboard']);
    Route::get('/patients/{patientId}/health-data', [CaregiverPatientController::class, 'healthData']);
    Route::get('/patients/{patientId}/clinical-notes', [CaregiverPatientController::class, 'clinicalNotes']);
    Route::get('/patients/{patientId}/histories', [CaregiverPatientController::class, 'histories']);
    Route::get('/patients/{patientId}/recommendations', [CaregiverPatientController::class, 'recommendations']);
    Route::get('/patients/{patientId}/prescriptions/active', [CaregiverPatientController::class, 'activePrescriptions']);

    Route::post('/find-patient', [CaregiverPatientController::class, 'findPatient']);
    Route::post('/request-connection', [CaregiverPatientController::class, 'requestConnection']);
    Route::delete('/patients/{patientId}/disconnect', [CaregiverPatientController::class, 'disconnect']);

    Route::post('/patients/{patientId}/glucose', [CaregiverHealthController::class, 'storeGlucose']);
    Route::post('/patients/{patientId}/physiological', [CaregiverHealthController::class, 'storePhysiological']);
    Route::post('/patients/{patientId}/activity', [CaregiverHealthController::class, 'storeActivity']);
    Route::post('/patients/{patientId}/meal', [CaregiverHealthController::class, 'storeMeal']);
    Route::post('/patients/{patientId}/medication', [CaregiverHealthController::class, 'storeMedication']);
});

Route::prefix('notifications')->middleware('auth:sanctum')->group(function () {

    Route::get('/user/{userId}', [NotificationController::class, 'index']);

    Route::get('/{notificationId}', [NotificationController::class, 'show']);

    Route::patch('/{notificationId}/read', [NotificationController::class, 'markAsRead']);

    Route::patch('/user/{userId}/read-all', [NotificationController::class, 'markAllAsRead']);

    Route::post('/', [NotificationController::class, 'store']);

    Route::post('/fcm-token', [NotificationController::class, 'saveFcmToken']);
    Route::post('/fcm-token/deactivate', [NotificationController::class, 'deactivateFcmToken']);
    Route::post('/test-push', [NotificationController::class, 'testPush']);
});

Route::prefix('admin')->group(function () {
    Route::get('/doctors/pending', [AdminDoctorController::class, 'pending']);
    Route::post('/doctors/{doctorId}/verify', [AdminDoctorController::class, 'verify']);
    Route::post('/doctors/{doctorId}/reject', [AdminDoctorController::class, 'reject']);

    Route::get('/users', [AdminUserController::class, 'index']);
    Route::patch('/users/{userId}/status', [AdminUserController::class, 'updateStatus']);
    Route::get('/dashboard', [AdminUserController::class, 'dashboard']);
});
