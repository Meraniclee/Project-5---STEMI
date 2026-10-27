<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\Admin\UserManagementController;
use App\Http\Controllers\Stemi\WorkspaceController as Workspace;
use App\Http\Controllers\Stemi\PatientManagementController;
use App\Http\Controllers\Stemi\ConsultantDoctorController;
use App\Http\Controllers\Stemi\ExaminationReportController;
use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome')->name('home');
Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
Route::post('/login', [LoginController::class, 'login'])->name('login.submit');

Route::middleware('auth')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::post('/logout', [LoginController::class, 'logout'])->name('logout');
    Route::get('/ecg-images/{image}/file', [Workspace::class, 'image'])
        ->middleware('role:admin,doctor,consultant')->name('ecg-images.file');

    Route::middleware('role:admin')->prefix('admin')->name('admin.')->group(function () {
        Route::get('/dashboard', [Workspace::class, 'dashboard'])->name('dashboard');
        Route::get('/patients', [Workspace::class, 'patients'])->name('patients');
        Route::get('/patients/create', [PatientManagementController::class, 'create'])->name('patients.create');
        Route::post('/patients', [PatientManagementController::class, 'store'])->name('patients.store');
        Route::get('/patients/{patient}/edit', [PatientManagementController::class, 'edit'])->name('patients.edit');
        Route::put('/patients/{patient}', [PatientManagementController::class, 'update'])->name('patients.update');
        Route::get('/patients/{patient}', [Workspace::class, 'patient'])->name('patients.show');
        Route::get('/ecg-images/upload', [Workspace::class, 'uploadForm'])->name('ecg-images.create');
        Route::post('/ecg-images/upload', [Workspace::class, 'upload'])->name('ecg-images.store');
        Route::get('/reports', [ExaminationReportController::class, 'index'])->name('reports');
        Route::get('/reports/patients/{patient}', [ExaminationReportController::class, 'show'])->name('reports.show');
        Route::get('/doctors', [UserManagementController::class, 'doctors'])->name('doctors');
        Route::get('/doctors/create', [UserManagementController::class, 'doctorCreate'])->name('doctors.create');
        Route::post('/doctors', [UserManagementController::class, 'doctorStore'])->name('doctors.store');
        Route::get('/doctors/{user}', [UserManagementController::class, 'doctorShow'])->name('doctors.show');
        Route::get('/doctors/{user}/patients/{patient}', [Workspace::class, 'patientForDoctor'])->name('doctors.patients.show');
        Route::get('/doctors/{user}/edit', [UserManagementController::class, 'doctorEdit'])->name('doctors.edit');
        Route::put('/doctors/{user}', [UserManagementController::class, 'doctorUpdate'])->name('doctors.update');
        Route::patch('/doctors/{user}/status', [UserManagementController::class, 'doctorToggleStatus'])->name('doctors.status');
        Route::delete('/doctors/{user}', [UserManagementController::class, 'doctorDestroy'])->name('doctors.destroy');
        Route::get('/consultants', [UserManagementController::class, 'consultants'])->name('consultants');
        Route::get('/consultants/create', [UserManagementController::class, 'consultantCreate'])->name('consultants.create');
        Route::post('/consultants', [UserManagementController::class, 'consultantStore'])->name('consultants.store');
        Route::get('/consultants/{user}/edit', [UserManagementController::class, 'consultantEdit'])->name('consultants.edit');
        Route::put('/consultants/{user}', [UserManagementController::class, 'consultantUpdate'])->name('consultants.update');
        Route::patch('/consultants/{user}/status', [UserManagementController::class, 'consultantToggleStatus'])->name('consultants.status');
        Route::delete('/consultants/{user}', [UserManagementController::class, 'consultantDestroy'])->name('consultants.destroy');
    });

    Route::middleware('role:doctor')->prefix('doctor')->name('doctor.')->group(function () {
        Route::get('/dashboard', [Workspace::class, 'dashboard'])->name('dashboard');
        Route::get('/patients', [Workspace::class, 'patients'])->name('patients');
        Route::get('/my-patients', [Workspace::class, 'patients'])->name('my-patients');
        Route::get('/patients/{patient}/edit', [PatientManagementController::class, 'edit'])->name('patients.edit');
        Route::put('/patients/{patient}', [PatientManagementController::class, 'update'])->name('patients.update');
        Route::get('/patients/{patient}', [Workspace::class, 'patient'])->name('patients.show');
        Route::post('/patients/{patient}/start', [Workspace::class, 'claim'])->name('patients.start');
        Route::post('/patients/{patient}/interpretations', [Workspace::class, 'saveInterpretation'])->name('interpretations.store');
        Route::get('/my-cases', [Workspace::class, 'cases'])->name('my-cases');
        Route::get('/reports', [ExaminationReportController::class, 'index'])->name('reports');
        Route::get('/reports/patients/{patient}', [ExaminationReportController::class, 'show'])->name('reports.show');
        Route::get('/profile', [Workspace::class, 'profile'])->name('profile');
    });

    Route::middleware('role:consultant')->prefix('consultant')->name('consultant.')->group(function () {
        Route::get('/dashboard', [Workspace::class, 'dashboard'])->name('dashboard');
        Route::get('/doctors', [ConsultantDoctorController::class, 'index'])->name('doctors');
        Route::get('/doctors/{user}', [ConsultantDoctorController::class, 'show'])->name('doctors.show');
        Route::get('/doctors/{user}/patients/{patient}', [Workspace::class, 'patientForDoctor'])->name('doctors.patients.show');
        Route::get('/patients', [Workspace::class, 'patients'])->name('patients');
        Route::get('/patients/{patient}', [Workspace::class, 'patient'])->name('patients.show');
        Route::get('/case-status', [Workspace::class, 'cases'])->name('case-status');
        Route::get('/reports', [ExaminationReportController::class, 'index'])->name('reports');
        Route::get('/reports/patients/{patient}', [ExaminationReportController::class, 'show'])->name('reports.show');
        Route::get('/profile', [Workspace::class, 'profile'])->name('profile');
    });
});
