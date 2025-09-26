<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\UsersController;
use App\Http\Controllers\PdfExtractController;
use App\Http\Controllers\VendorController;
use App\Http\Controllers\PackingListController;
use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\TransportController;
use App\Http\Controllers\EInvoiceController;
use App\Http\Controllers\AutoPackingListController;
use App\Http\Controllers\ReportController;

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

    Route::group(['prefix' => 'po'], function () {
        Route::get('/amended', [PdfExtractController::class, 'amended'])->name('pdf_extract_amended_master');
        Route::get('/all', [PdfExtractController::class, 'all'])->name('pdf_extract_all_master');
        Route::get('/add', [PdfExtractController::class, 'add'])->name('pdf_extract_add');
        Route::post('/store', [PdfExtractController::class, 'store'])->name('pdf_extract_store');
        Route::post('/details', [PdfExtractController::class, 'details'])->name('pdf_extract_details');
        Route::post('/delete', [PdfExtractController::class, 'delete'])->name('pdf_extract_delete');
        Route::post('/pdf_process', [PdfExtractController::class, 'pdf_process'])->name('pdf_process');
        Route::get('/get_po_table', [PdfExtractController::class, 'get_po_table'])->name('get_po_table');
        Route::post('/details', [PdfExtractController::class, 'get_po_details'])->name('get_po_details');
        Route::post('/get_vendor_custom_field', [PdfExtractController::class, 'get_vendor_custom_field'])->name('get_vendor_custom_field');
        Route::post('/check_po_exists', [PdfExtractController::class, 'check_po_exists'])->name('check_po_exists');
        Route::post('/get_amend_details', [PdfExtractController::class, 'get_amend_details'])->name('get_amend_details');
        Route::post('/po_amended', [PdfExtractController::class, 'po_amended'])->name('po_amended');
    });

    Route::group(['prefix' => 'packing_list'], function () {
        Route::get('/master', [PackingListController::class, 'index'])->name('packing_list_master');
        Route::get('/add', [PackingListController::class, 'add'])->name('packing_list_add');
        Route::get('/edit/{id}', [PackingListController::class, 'edit'])->name('packing_list_edit');
        Route::get('/items_by_id', [PackingListController::class, 'get_packing_list_items_by_id'])->name('packing_list_items_by_id');
        Route::get('/po_search', [PackingListController::class, 'search_po'])->name('packing_list_search');
        Route::get('/po_details', [PackingListController::class, 'get_packing_po_details'])->name('get_packing_po_details');
        Route::get('/get-_po_locations', [PackingListController::class, 'get_po_locations'])->name('get_po_locations');
        Route::get('/get_location_colors', [PackingListController::class, 'get_location_colors'])->name('get_location_colors');
        Route::get('/po_colors', [PackingListController::class, 'get_po_colors'])->name('get_po_colors');
        Route::get('/sizes_with_qty', [PackingListController::class, 'get_sizes_with_qty'])->name('get_sizes_with_qty');
        Route::post('/details', [PackingListController::class, 'packing_list_details'])->name('packing_list_details');
        Route::get('/items', [PackingListController::class, 'get_packing_list_items'])->name('packing_list_items');
        Route::post('/item_add', [PackingListController::class, 'item_add'])->name('packing_list_item_add');
        Route::get('/item_sizes', [PackingListController::class, 'get_sizes'])->name('packing_list_sizes');
        Route::post('/item_store', [PackingListController::class, 'item_store'])->name('packing_list_item_store');
        Route::post('/item_edit', [PackingListController::class, 'item_edit'])->name('packing_list_item_edit');
        Route::post('/item_update', [PackingListController::class, 'item_update'])->name('packing_list_item_update');
        Route::delete('/item_delete', [PackingListController::class, 'item_delete'])->name('packing_list_item_delete');
        Route::post('/delete', [PackingListController::class, 'delete'])->name('packing_list_delete');
        Route::post('/complete', [PackingListController::class, 'packing_list_complete'])->name('packing_list_complete');
        Route::get('/print/{id}', [PackingListController::class, 'po_print'])->name('packing_list_print');
        Route::get('/config', [PackingListController::class, 'config'])->name('packing_list_config');
        Route::post('/get_config_vendor_po', [PackingListController::class, 'get_config_vendor_po'])->name('get_config_vendor_po');
        Route::post('/get_config_po_details', [PackingListController::class, 'get_config_po_details'])->name('get_config_po_details');
        Route::post('/save_config_po_details', [PackingListController::class, 'save_config_po_details'])->name('save_config_po_details');
        Route::get('/get-available-sizes', [PackingListController::class, 'getAvailableSizes'])->name('get_available_sizes');
        Route::get('/check-size-availability', [PackingListController::class, 'checkSizeAvailability'])->name('check_size_availability');
        Route::get('/auto', [AutoPackingListController::class, 'auto'])->name('packing_list_auto');
        Route::get('/auto_items', [AutoPackingListController::class, 'get_auto_packing_list_items'])->name('auto_packing_list_items');
        Route::get('auto-packing-list/print/{po_id}/{color}', [AutoPackingListController::class, 'print'])->name('auto_packing_list_print');
        Route::post('/update_packing_list_po_num', [PackingListController::class, 'updatePackingListPoNumber'])->name('update_packing_list_po_num');
    });

    Route::group(['prefix' => 'invoice'], function () {
        Route::get('/genrate', [InvoiceController::class, 'genrate'])->name('invoice_genrate');
        Route::post('/check_duplicate_invoice', [InvoiceController::class, 'check_duplicate_invoice'])->name('check_duplicate_invoice');
        Route::get('/master', [InvoiceController::class, 'master'])->name('invoice_master');
        Route::post('/table', [InvoiceController::class, 'table'])->name('invoice_table');
        Route::post('/get_complete_vendor_packing_list', [InvoiceController::class, 'get_complete_vendor_packing_list'])->name('get_complete_vendor_packing_list');
        Route::post('/get_packging_list', [InvoiceController::class, 'get_packging_list'])->name('get_packging_list');
        Route::post('/invoice_details_edit', [InvoiceController::class, 'invoice_details_edit'])->name('invoice_details_edit');
        Route::post('/invoice_details_update', [InvoiceController::class, 'invoice_details_update'])->name('invoice_details_update');
        Route::post('/store_invoice', [InvoiceController::class, 'store_invoice'])->name('store_invoice');
        Route::get('/download', [InvoiceController::class, 'generateInvoice'])->name('generateInvoice');
        Route::get('/e_invoicemaster', [EInvoiceController::class, 'e_invoice_master'])->name('e_invoice_master');
        Route::post('/grn_details_edit', [InvoiceController::class, 'grn_details_edit'])->name('grn_details_edit');
        Route::post('/grn_details_update', [InvoiceController::class, 'grn_details_update'])->name('grn_details_update');
        Route::post('/bulk-status-update', [InvoiceController::class, 'bulkStatusUpdate'])->name('invoice_bulk_status_update');
        Route::get('/grn_entry', [InvoiceController::class, 'grn_entry'])->name('grn_entry');
        Route::post('/grn_entry_details_update', [InvoiceController::class, 'grn_entry_details_update'])->name('grn_entry_details_update');
    });

    Route::group(['prefix' => 'e_invoice'], function () {
        //Route::get('/master', [EInvoiceController::class, 'e_invoice_master'])->name('e_invoice_master');
        Route::post('/table', [EInvoiceController::class, 'e_invoice_master_table'])->name('e_invoice_master_table');
        Route::get('/excel_download', [EInvoiceController::class, 'e_invoice_excel_download'])->name('e_invoice_excel_download');
    });

    Route::group(['prefix' => 'report'], function () {
        Route::get('/dispatch_status', [ReportController::class, 'dispatch_status_report_master'])->name('dispatch_status_report_master');
        Route::post('/dispatch_status_table', [ReportController::class, 'dispatch_status_report_table'])->name('dispatch_status_report_table');
        Route::post('/dispatch_status_report_edit', [ReportController::class, 'dispatch_status_report_edit'])->name('dispatch_status_report_edit');
        Route::post('/dispatch_status_report_update', [ReportController::class, 'dispatch_status_report_update'])->name('dispatch_status_report_update');
        Route::get('/dispatch_status_excel_download', [ReportController::class, 'dispatch_status_report_excel_download'])->name('dispatch_status_report_excel_download');
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

        Route::group(['prefix' => 'transport'], function () {
            Route::get('/master', [TransportController::class, 'index'])->name('transport_master');
            Route::post('/add', [TransportController::class, 'add'])->name('transport_add');
            Route::post('/store', [TransportController::class, 'store'])->name('transport_store');
            Route::post('/details', [TransportController::class, 'get_transport_details'])->name('get_transport_details');
            Route::post('/edit', [TransportController::class, 'edit'])->name('transport_edit');
            Route::post('/update', [TransportController::class, 'update'])->name('transport_update');
            Route::post('/delete', [TransportController::class, 'delete'])->name('transport_delete');
        });
    });
});
