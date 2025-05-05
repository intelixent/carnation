<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\UsersController;
use App\Http\Controllers\PdfExtractController;
use App\Http\Controllers\VendorController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::get('/', function () {
    return view('auth/login');
});

Auth::routes();
//Route::get('/home', [App\Http\Controllers\HomeController::class, 'index'])->name('home');
Route::get('/logout', [LoginController::class, 'logout'])->name('logout');

Route::middleware(['auth', 'superadmin'])->group(function () {

    Route::get('/home', [DashboardController::class, 'index'])->name('home');

    Route::group(['prefix' => 'users'], function () {

        Route::get('/get_role_permissions/{roleId}', [UsersController::class, 'get_role_permissions'])->name('get_role_permissions');

        Route::group(['prefix' => 'role'], function () {
            Route::get('/index', [UsersController::class, 'role_index'])->name('role_index');
            Route::get('/add', [UsersController::class, 'role_add'])->name('role_add');
            Route::post('/store', [UsersController::class, 'role_store'])->name('role_store');
            Route::get('/edit/{id}', [UsersController::class, 'role_edit'])->name('role_edit');
            Route::put('/update/{id}', [UsersController::class, 'role_update'])->name('role_update');
            Route::post('/details', [UsersController::class, 'get_role_details'])->name('get_role_details');
        });

        Route::group(['prefix' => 'user'], function () {
            Route::get('/index', [UsersController::class, 'user_index'])->name('user_index');
            Route::get('/add', [UsersController::class, 'user_add'])->name('user_add');
            Route::post('/store', [UsersController::class, 'user_store'])->name('user_store');
            Route::get('/edit/{id}', [UsersController::class, 'user_edit'])->name('user_edit');
            Route::put('/update/{id}', [UsersController::class, 'user_update'])->name('user_update');
            Route::post('/details', [UsersController::class, 'get_user_details'])->name('get_user_details');
            Route::post('/delete', [UsersController::class, 'user_delete'])->name('user_delete');
            Route::post('/get_user_password', [UsersController::class, 'get_user_password'])->name('get_user_password');
            Route::post('/user_password_update', [UsersController::class, 'user_password_update'])->name('user_password_update');
        });
    });

    Route::group(['prefix' => 'extract'], function () {
        Route::get('/', [PdfExtractController::class, 'index'])->name('pdf_extract_master');
        Route::get('/add', [PdfExtractController::class, 'add'])->name('pdf_extract_add');
        Route::post('/store', [PdfExtractController::class, 'store'])->name('pdf_extract_store');
        Route::post('/details', [PdfExtractController::class, 'details'])->name('pdf_extract_details');
        Route::post('/delete', [PdfExtractController::class, 'delete'])->name('pdf_extract_delete');
    });

    Route::group(['prefix' => 'settings'], function () {

        Route::group(['prefix' => 'vendor'], function () {
            Route::get('/master', [VendorController::class, 'index'])->name('vendor_index');
            Route::post('/add', [VendorController::class, 'add'])->name('vendor_add');
            Route::post('/store', [VendorController::class, 'store'])->name('vendor_store');
            Route::post('/details', [VendorController::class, 'get_vendor_details'])->name('get_vendor_details');
            Route::post('/edit', [VendorController::class, 'edit'])->name('vendor_edit');
            Route::post('/update', [VendorController::class, 'update'])->name('vendor_update');
            Route::post('/delete', [VendorController::class, 'delete'])->name('vendor_delete');
            Route::post('/update_status', [VendorController::class, 'update_status'])->name('vendor_update_status');
        });
    });
});
