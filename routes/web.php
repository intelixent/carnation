<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\UsersController;
use App\Http\Controllers\PdfExtractController;
use App\Http\Controllers\VendorController;
use App\Http\Controllers\POController;
use App\Http\Controllers\PackingListController;

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
        Route::post('/processpdf', [PdfExtractController::class, 'processpdf'])->name('pdf_process');
        Route::get('/get_po_table', [POController::class, 'get_po_table'])->name('get_po_table');
        Route::post('/details', [PdfExtractController::class, 'get_po_details'])->name('get_po_details');
    });

    Route::group(['prefix' => 'packing_list'], function () {
        Route::get('/master', [PackingListController::class, 'index'])->name('packing_list_master');
        Route::get('/add', [PackingListController::class, 'add'])->name('packing_list_add');
        Route::get('/edit/{id}', [PackingListController::class, 'edit'])->name('packing_list_edit');
        Route::get('/items_by_id', [PackingListController::class, 'get_packing_list_items_by_id'])->name('packing_list_items_by_id');
        Route::get('/po_search', [PackingListController::class, 'search_po'])->name('packing_list_search');
        Route::get('/po_details', [PackingListController::class, 'get_packing_po_details'])->name('get_packing_po_details');
        Route::post('/details', [PackingListController::class, 'packing_list_details'])->name('packing_list_details');
        Route::get('/items', [PackingListController::class, 'get_packing_list_items'])->name('packing_list_items');
        Route::post('/item_add', [PackingListController::class, 'item_add'])->name('packing_list_item_add');
        Route::get('/item_sizes', [PackingListController::class, 'get_sizes'])->name('packing_list_sizes');
        Route::post('/item_store', [PackingListController::class, 'item_store'])->name('packing_list_item_store');
        Route::post('/item_edit', [PackingListController::class, 'item_edit'])->name('packing_list_item_edit');
        Route::post('/item_update', [PackingListController::class, 'item_update'])->name('packing_list_item_update');
        Route::delete('/item_delete', [PackingListController::class, 'item_delete'])->name('packing_list_item_delete');
        Route::post('/delete', [PackingListController::class, 'delete'])->name('packing_list_delete');
        Route::get('/print/{id}', [PackingListController::class, 'po_print'])->name('packing_list_print');
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

            Route::group(['prefix' => 'carton'], function () {
                Route::get('/jack_master', [VendorController::class, 'carton_jack_master'])->name('carton_jack_master');
                Route::get('/skecher_master', [VendorController::class, 'carton_skecher_master'])->name('carton_skecher_master');
                Route::get('/puma_master', [VendorController::class, 'carton_puma_master'])->name('carton_puma_master');
                Route::get('/benetton_master', [VendorController::class, 'carton_benetton_master'])->name('carton_benetton_master');
                Route::get('/selected_master', [VendorController::class, 'carton_selected_master'])->name('carton_selected_master');
                Route::get('/vero_master', [VendorController::class, 'carton_vero_master'])->name('carton_vero_master');
                Route::post('/add', [VendorController::class, 'carton_add'])->name('carton_add');
                Route::post('/store', [VendorController::class, 'carton_store'])->name('carton_store');
                Route::post('/details', [VendorController::class, 'get_carton_details'])->name('get_carton_details');
                Route::post('/edit', [VendorController::class, 'carton_edit'])->name('carton_edit');
                Route::post('/update', [VendorController::class, 'carton_update'])->name('carton_update');
                Route::post('/delete', [VendorController::class, 'carton_delete'])->name('carton_delete');
            });
        });
    });
});
