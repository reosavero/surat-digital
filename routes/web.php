<?php

/**
 * Web Routes - Surat Digital TVRI Jawa Timur
 */

use Router as Route;

// Auth Routes
Route::get('/', 'AuthController@showLoginForm');
Route::get('/login', 'AuthController@showLoginForm');
Route::post('/login', 'AuthController@login');
Route::get('/logout', 'AuthController@logout');
Route::get('/profile', 'AuthController@profile');

// Multi-step Registration Routes
Route::get('/register', 'RegisterController@showRegisterForm');
Route::post('/register/send-otp', 'RegisterController@sendOtp');
Route::post('/register/verify-otp', 'RegisterController@verifyOtp');
Route::post('/register/complete', 'RegisterController@complete');

// Forgot Password Routes
Route::get('/forgot-password', 'AuthController@showForgotPassword');
Route::post('/forgot-password', 'AuthController@sendResetLink');

// Dashboard & Disposisi Routes
Route::get('/dashboard', 'DashboardController@index');
Route::get('/dashboard-2', 'DashboardController@index2');
Route::get('/disposisi', 'DisposisiController@index');

// Surat Management & Workflow Routes
Route::get('/surat', 'SuratController@index');
Route::get('/surat/revisi', 'SuratController@revisiList');
Route::get('/revisi', 'SuratController@revisiList');
Route::get('/surat/edit/{id}', 'SuratController@editSurat');
Route::get('/surat/revisi/{id}', 'SuratController@editSurat');
Route::get('/surat/detail/{id}', 'SuratController@detail');
Route::get('/surat/tracking/{id}', 'SuratController@tracking');
Route::post('/surat/update-rotation', 'SuratController@updateRotation');

// Admin Routes (Create Surat Izin Prinsip, Surat Izin Produksi & Surat Nota Dinas)
Route::get('/surat-prinsip', 'SuratController@createPrinsip');
Route::get('/surat/create-prinsip', 'SuratController@createPrinsip');
Route::post('/surat/store-prinsip', 'SuratController@storePrinsip');
Route::get('/surat-produksi', 'SuratController@createProduksi');
Route::get('/surat/create-produksi', 'SuratController@createProduksi');
Route::post('/surat/store-produksi', 'SuratController@storeProduksi');
Route::get('/surat-nota-dinas', 'SuratController@createNotaDinas');
Route::get('/surat/create-nota-dinas', 'SuratController@createNotaDinas');
Route::post('/surat/store-nota-dinas', 'SuratController@storeNotaDinas');

// KPP Bidang Routes (Signature Surat Permohonan & Paraf Nota Dinas)
Route::get('/surat/kpp-process/{id}', 'SuratController@kppProcess');
Route::post('/surat/kpp-submit/{id}', 'SuratController@kppSubmit');

// Tata Usaha Routes (Signature Nota Dinas)
Route::get('/surat/tu-process/{id}', 'SuratController@tuProcess');
Route::post('/surat/tu-submit/{id}', 'SuratController@tuSubmit');

// Sekretaris Routes (Review, Approve/Revisi, Upload Lampiran, Disposisi & History)
Route::get('/surat/sekre-process/{id}', 'SuratController@sekreProcess');
Route::post('/surat/sekre-submit/{id}', 'SuratController@sekreSubmit');
Route::get('/surat/upload-lampiran', 'SuratController@uploadLampiranForm');
Route::get('/surat/upload-lampiran/{id}', 'SuratController@uploadLampiranForm');
Route::post('/surat/upload-lampiran', 'SuratController@uploadLampiranSubmit');
Route::get('/surat/history', 'SuratController@history');
Route::get('/history', 'SuratController@history');

// Sekretaris & Superadmin: Kelola Rekap Disposisi Excel
Route::get('/excel-disposisi', 'ExcelDisposisiController@index');
Route::get('/excel-disposisi/view', 'ExcelDisposisiController@detail');
Route::get('/excel-disposisi/detail', 'ExcelDisposisiController@detail');
Route::get('/excel-disposisi/download', 'ExcelDisposisiController@download');
Route::post('/excel-disposisi/sync', 'ExcelDisposisiController@sync');
Route::any('/excel-disposisi/delete', 'ExcelDisposisiController@delete');



// Kepala Stasiun Routes (Checklist, Note & Signature)
Route::get('/surat/kepsta-process/{id}', 'SuratController@kepstaProcess');
Route::post('/surat/kepsta-submit/{id}', 'SuratController@kepstaSubmit');

// Disposisi Process Review & Print Route
Route::get('/surat/disposisi-process/{id}', 'SuratController@disposisiProcess');

// Superadmin Routes (User Management & Activity Log)
Route::get('/users', 'UserController@index');
Route::post('/users/store', 'UserController@store');
Route::post('/users/update/{id}', 'UserController@update');
Route::get('/users/delete/{id}', 'UserController@delete');
Route::get('/users/approve/{id}', 'UserController@approve');
Route::get('/users/reject/{id}', 'UserController@reject');
Route::post('/users/reject/{id}', 'UserController@reject');
Route::get('/activity-logs', 'ActivityLogController@index');
Route::post('/activity-logs/clear', 'ActivityLogController@clear');

// Superadmin Settings Routes
Route::get('/settings/gdrive', 'SettingsController@gdrive');
Route::post('/settings/gdrive', 'SettingsController@saveGdrive');
