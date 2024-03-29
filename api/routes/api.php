<?php

use App\Http\Controllers\ClientController;
use App\Http\Controllers\CopsisController;
use App\Http\Controllers\CRMController;
use App\Http\Controllers\LoginController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

use App\Http\Controllers\QuotationController;
use App\Http\Controllers\ToolsController;

Route::post('/login', [LoginController::class, 'login']);
Route::post('/register', [LoginController::class, 'register']);
Route::post('/sendSecureCode', [LoginController::class, 'sendSecureCode']);
Route::post('/actualizePassword', [LoginController::class, 'actualizePassword']);

Route::post('/quotations/lastUpdate', [QuotationController::class, 'lastUpdate']);
Route::resource('/quotations', QuotationController::class);
Route::resource('/clients', ClientController::class);
Route::resource('/crm', CRMController::class);

Route::post('/copsis/token', [CopsisController::class, 'token']);
Route::post('/copsis/brand', [CopsisController::class, 'consultBrand']);
Route::post('/copsis/type', [CopsisController::class, 'consultType']);
Route::post('/copsis/version', [CopsisController::class, 'consultVersion']);
Route::post('/copsis/homologation', [CopsisController::class, 'homologation']);
Route::post('/copsis/primeroQuotation', [CopsisController::class, 'primeroQuotation']);
Route::post('/copsis/primeroEmission', [CopsisController::class, 'primeroEmission']);
Route::post('/copsis/qualitasQuotation', [CopsisController::class, 'qualitasQuotation']);
Route::post('/copsis/qualitasEmission', [CopsisController::class, 'qualitasEmission']);
Route::post('/copsis/chuubQuotation', [CopsisController::class, 'chuubQuotation']);
Route::post('/copsis/chubbEmission', [CopsisController::class, 'chubbEmission']);
// Route::post('/copsis/anaQuotation', [CopsisController::class, 'anaQuotation']);
// Route::post('/copsis/anaEmission', [CopsisController::class, 'anaEmission']);
Route::post('/copsis/confirmPayment', [CopsisController::class, 'confirmPayment']);
Route::post('/copsis/printPDF', [CopsisController::class, 'printPDF']);

Route::post('/tools/permissions/create', [ToolsController::class, 'storePermissions']);
Route::post('/tools/roles/create', [ToolsController::class, 'storeRoles']);
Route::post('/tools/permissions/assign', [ToolsController::class, 'assignPermissions']);
Route::post('/tools/roles/assign', [ToolsController::class, 'assignRoles']);
