<?php

use App\Http\Controllers\AdminUserApprovalController;
use App\Http\Controllers\AdminUserController;
use App\Http\Controllers\EmailVerificationController;
use App\Http\Controllers\LoginController;
use App\Http\Controllers\PasswordResetController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\RegistrationController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/inscription', [RegistrationController::class, 'create'])->name('register');
Route::post('/inscription', [RegistrationController::class, 'store'])->name('register.store');
Route::get('/email/en-attente', [RegistrationController::class, 'pending'])->name('verification.pending');

Route::get('/email/verification/{id}/{hash}', EmailVerificationController::class)
    ->middleware('signed')
    ->name('verification.verify');

Route::get('/connexion', [LoginController::class, 'create'])->name('login');
Route::post('/connexion', [LoginController::class, 'store'])->name('login.store');
Route::post('/deconnexion', [LoginController::class, 'destroy'])
    ->middleware('auth')
    ->name('logout');

Route::get('/mot-de-passe/oublie', [PasswordResetController::class, 'requestForm'])
    ->name('password.request');
Route::post('/mot-de-passe/oublie', [PasswordResetController::class, 'sendLink'])
    ->name('password.email');
Route::get('/mot-de-passe/reinitialiser/{token}', [PasswordResetController::class, 'resetForm'])
    ->name('password.reset');
Route::post('/mot-de-passe/reinitialiser', [PasswordResetController::class, 'reset'])
    ->name('password.update');

Route::get('/tableau-de-bord', fn () => view('welcome'))
    ->middleware(['auth', 'account.active'])
    ->name('dashboard');

Route::middleware(['auth', 'account.active'])->prefix('admin')->name('admin.')->group(function () {
    Route::post('/utilisateurs/{user}/approuver', [AdminUserApprovalController::class, 'approve'])
        ->name('users.approve');
    Route::post('/utilisateurs/{user}/refuser', [AdminUserApprovalController::class, 'refuse'])
        ->name('users.refuse');
    Route::post('/utilisateurs/{user}/reinitialiser-mot-de-passe', [AdminUserApprovalController::class, 'resetPassword'])
        ->name('users.reset-password');
    Route::get('/utilisateurs', [AdminUserController::class, 'index'])
        ->name('users.index');
    Route::get('/utilisateurs/{user}', [AdminUserController::class, 'show'])
        ->name('users.show');
    Route::put('/utilisateurs/{user}/role', [AdminUserController::class, 'updateRole'])
        ->name('users.role.update');
    Route::post('/utilisateurs/{user}/activer', [AdminUserController::class, 'activate'])
        ->name('users.activate');
    Route::post('/utilisateurs/{user}/desactiver', [AdminUserController::class, 'deactivate'])
        ->name('users.deactivate');
    Route::delete('/utilisateurs/{user}', [AdminUserController::class, 'destroy'])
        ->name('users.destroy');
});

Route::middleware(['auth', 'account.active'])->group(function () {
    Route::get('/profil', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::put('/profil', [ProfileController::class, 'update'])->name('profile.update');
    Route::get('/profil/email/{user}/{hash}', [ProfileController::class, 'verifyEmail'])
        ->middleware('signed')
        ->name('profile.email.verify');
});
