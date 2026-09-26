<?php

namespace App\Http\Controllers;

use App\Http\Controllers\BaseController;
use Illuminate\Http\Request;
use App\Models\VendorMaster;
use App\Models\PoMaster;
use App\Models\PoItems;
use App\Models\PoSizes;
use App\Models\PoDmartSizes;
use App\Models\PrefixSetting;
use App\Models\SizeChartMaster;
use App\Models\CartonMaster;
use App\Models\PackingListMaster;
use App\Models\PackingListItem;
use App\Models\PackingListConfigMaster;
use App\Models\PackingListConfigItem;
use App\Models\JobOrderMaster;
use App\Models\JobOrderSizeMaster;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;

class BulkExtractController extends BaseController
{
    protected $isSuperAdmin;

    public function __construct()
    {
        parent::__construct();
        $this->isSuperAdmin = request()->attributes->get('isSuperAdmin', false);
        $this->middleware('auth');
    }

    /**
     * Render Bulk PO Import Page (Store PO Alone)
     */
    public function po_import()
    {
        $page_data = [
            'page_title' => "Bulk PO Import",
            'page_main_title' => "Bulk Import",
            'page_child_title' => "PO Import",
            'isSuperAdmin' => $this->isSuperAdmin,
        ];

        $page_data['vendors'] = VendorMaster::whereIn('status', [0, 1])
            ->orderBy('id', 'asc')
            ->get();

        return view('bulk_import.po_import', $page_data);
    }

    /**
     * Process multiple uploaded PO PDFs and prepare previews for PO Alone import
     */
    public function po_import_process(Request $request)
    {
        try {
            $vendor_id = $request->input('vendor_id');
            $files = $request->file('pdf_files');

            if (empty($vendor_id)) {
                return response()->json(['error' => 'Please select a vendor.'], 400);
            }

            if (empty($files) || !is_array($files)) {
                return response()->json(['error' => 'No PDF files were uploaded.'], 400);
            }

            $vendor = VendorMaster::find($vendor_id);
            if (!$vendor) {
                return response()->json(['error' => 'Selected vendor not found.'], 404);
            }

            $extraction_no = (string)($vendor->extraction_no ?? $vendor_id);
            $extracted_pos = [];
            $errors = [];

            foreach ($files as $index => $file) {
                $originalName = $file->getClientOriginalName();
                $pdfBase64 = base64_encode(file_get_contents($file->getRealPath()));

                try {
                    $response = Http::timeout(60)->post('http://localhost:8000/process', [
                        'extraction_no' => $extraction_no,
                        'pdf_base64' => $pdfBase64,
                    ]);

                    if (!$response->successful()) {
                        $errors[] = "Error processing '{$originalName}': Microservice returned status " . $response->status();
                        continue;
                    }

                    $res_data = $response->json();
                    $data = $res_data['data'] ?? [];

                    // Handle different vendor structures
                    $poListToProcess = [];

                    if (($extraction_no === '7' || $vendor_id === '9') && isset($data['pos']) && is_array($data['pos'])) {
                        foreach ($data['pos'] as $subIdx => $subPo) {
                            $poListToProcess[] = [
                                'is_multi' => true,
                                'data' => $subPo,
                                'sub_index' => $subIdx
                            ];
                        }
                    } elseif ($vendor_id === '9' && is_array($data) && array_is_list($data)) {
                        foreach ($data as $subIdx => $subPo) {
                            $poListToProcess[] = [
                                'is_multi' => true,
                                'data' => $subPo,
                                'sub_index' => $subIdx
                            ];
                        }
                    } else {
                        $poListToProcess[] = [
                            'is_multi' => false,
                            'data' => $data,
                            'sub_index' => 0
                        ];
                    }

                    foreach ($poListToProcess as $pItemEntry) {
                        $pData = $pItemEntry['data'];
                        $parsedPo = $this->parsePoDataForVendor($vendor_id, $extraction_no, $pData, $originalName, $pdfBase64, $vendor);
                        if ($parsedPo) {
                            $extracted_pos[] = $parsedPo;
                        }
                    }

                } catch (\Exception $subEx) {
                    $errors[] = "Failed processing '{$originalName}': " . $subEx->getMessage();
                }
            }

            if (empty($extracted_pos) && !empty($errors)) {
                return response()->json([
                    'error' => implode('<br>', $errors)
                ], 500);
            }

            // Map vendor extraction_no to single extract response view
            if ($extraction_no === '1') {
                $singleView = 'pdf_extract.jack_jones_response_view';
            } elseif ($extraction_no === '2') {
                $singleView = 'pdf_extract.skechers_response_view';
            } elseif ($extraction_no === '3') {
                $singleView = 'pdf_extract.puma_response_view';
            } elseif ($extraction_no === '4') {
                $singleView = 'pdf_extract.benetton_response_view';
            } elseif ($extraction_no === '5') {
                $singleView = 'pdf_extract.aditiya_response_view';
            } elseif ($extraction_no === '6') {
                $singleView = 'pdf_extract.dmart_response_view';
            } elseif ($extraction_no === '7') {
                $singleView = 'pdf_extract.rare_rabbit_response_view';
            } else {
                $singleView = 'pdf_extract.jack_jones_response_view';
            }

            // Vendor size chart for DMart
            $sizes = [];
            if ($extraction_no === '6' || $vendor_id == 8) {
                $sizes = SizeChartMaster::where('vendor_id', $vendor_id)
                    ->where('status', 0)
                    ->orderBy('id', 'asc')
                    ->pluck('size')
                    ->toArray();
            }

            $html = view('bulk_import.po_import_response_view', [
                'pos' => $extracted_pos,
                'vendor_id' => $vendor_id,
                'vendor' => $vendor,
                'extraction_no' => $extraction_no,
                'singleView' => $singleView,
                'sizes' => $sizes,
            ])->render();

            return response()->json([
                'status' => true,
                'html' => $html,
                'pos' => $extracted_pos,
                'errors' => $errors,
                'total_extracted' => count($extracted_pos),
                'total_files' => count($files)
            ]);

        } catch (\Exception $e) {
            return response()->json(['error' => 'An error occurred while processing bulk PDFs: ' . $e->getMessage()], 500);
        }
    }

    private function parsePoDataForVendor($vendor_id, $extraction_no, $data, $originalName, $pdfBase64, $vendor)
    {
        $po_num = '';
        $po_date = '';
        $delivery_date = '';
        $colors = '';
        $unit_price = 0;
        $total_qty = 0;
        $total_amount = 0;
        $po_details = [];
        $article_info = [];
        $po_items = [];

        if ($extraction_no === '2' || $vendor_id == 2) {
            // Skechers
            $po_details = $data['po_details'] ?? [];
            $po_items = $data['po_items'] ?? [];

            $po_num = $po_details['order_no'] ?? '';
            $po_date = $po_details['order_date'] ?? '';
            $delivery_date = '';
            $sizeColumns = ['XS', 'S', 'M', 'L', 'XL', 'XXL', 'XXXL'];

            $colorList = [];
            foreach ($po_items as $pItem) {
                if (!empty($pItem['Color'])) $colorList[] = $pItem['Color'];
                foreach ($sizeColumns as $sz) {
                    $total_qty += (int)str_replace(',', '', $pItem[$sz] ?? '0');
                }
                $total_amount += $this->cleanNumber($pItem['Amount (INR) - (c = a x b)'] ?? 0);
                if ($unit_price == 0) {
                    $unit_price = $this->cleanNumber($pItem['Unit Price (INR) - (b)'] ?? 0);
                }
            }
            $colors = implode(', ', array_unique($colorList));

        } elseif ($extraction_no === '3' || $vendor_id == 3) {
            // Puma
            $po_details = $data['po_details'] ?? [];
            $article_info = $data['article_details'] ?? ($data['article_info'] ?? []);
            $po_items = $data['po_items'] ?? [];

            $po_num = $po_details['po_number'] ?? '';
            $po_date = $po_details['po_release_date'] ?? '';
            $delivery_date = $po_details['po_ehd'] ?? '';
            $colors = $article_info['color'] ?? '';

            foreach ($po_items as $item) {
                if (isset($item['size']) && strtolower($item['size']) == 'total') continue;
                $q = floatval($item['quantity'] ?? 0);
                $u = floatval($item['unit_price'] ?? 0);
                $total_qty += $q;
                $total_amount += ($q * $u);
                if ($unit_price == 0 && $u > 0) $unit_price = $u;
            }

        } elseif ($extraction_no === '4' || $vendor_id == 4) {
            // Benetton
            $po_details = $data['po_details'] ?? [];
            $po_items = $data['po_items'] ?? [];

            $po_num = $po_details['order_no'] ?? '';
            $po_date = $po_details['order_date'] ?? '';
            $delivery_date = $po_details['delivery_date'] ?? '';

            $colorList = [];
            foreach ($po_items as $item) {
                $c = $item['Col'] ?? null;
                if (empty($c) || $c === 'Total') continue;
                $colorList[] = $c;
                $q = (int)str_replace(',', '', $item['Qty'] ?? 0);
                $cost = (float)str_replace(',', '', $item['Basic Cost'] ?? 0);
                $total_qty += $q;
                $total_amount += ($q * $cost);
                if ($unit_price == 0 && $cost > 0) $unit_price = $cost;
            }
            $colors = implode(', ', array_unique($colorList));

        } elseif ($extraction_no === '5' || $vendor_id == 7) {
            // Aditya Birla (Vendor 7)
            $po_details = $data['po_details'] ?? $data;
            $po_items = $data['po_items'] ?? ($po_details['po_items'] ?? []);
            $materialDescriptions = $data['material_descriptions'] ?? ($po_details['material_descriptions'] ?? []);
            $vendorInfo = $data['vendor_info'] ?? ($po_details['vendor_info'] ?? []);
            if (empty($vendorInfo) && isset($data['Vendor'])) {
                $vendorInfo = [
                    'Vendor' => $data['Vendor'] ?? null,
                    'Price per unit' => $data['Price per unit'] ?? null,
                    'Total unit' => $data['Total unit'] ?? null,
                    'Net Value' => $data['Net Value'] ?? null,
                ];
            }
            $article_info = $vendorInfo;

            $po_num = $po_details['po_number'] ?? ($data['po_number'] ?? '');
            $po_date = $po_details['po_date'] ?? ($data['po_date'] ?? '');
            $delivery_date = $po_items[0]['Delivery Date'] ?? '';

            $colorList = [];
            if (is_array($materialDescriptions)) {
                foreach ($materialDescriptions as $mat) {
                    if (!empty($mat['Colour'])) $colorList[] = $mat['Colour'];
                }
            }
            $colors = implode(', ', array_unique($colorList));

            foreach ($po_items as $item) {
                $q = floatval(str_replace(',', '', $item['Qty'] ?? 0));
                $r = floatval(str_replace(',', '', $item['Rate/Unit'] ?? 0));
                $total_qty += $q;
                $total_amount += ($q * $r);
                if ($unit_price == 0 && $r > 0) $unit_price = $r;
            }

            if ($unit_price == 0 && isset($vendorInfo['Price per unit'])) {
                $unit_price = $this->parseNumericAmount($vendorInfo['Price per unit']);
            }
            if ($total_qty == 0 && isset($data['total_quantity'])) {
                $total_qty = floatval(str_replace(',', '', $data['total_quantity']));
            }
            if ($total_amount == 0 && isset($data['total_value'])) {
                $total_amount = floatval(str_replace(',', '', $data['total_value']));
            }

        } elseif ($extraction_no === '6' || $vendor_id == 8) {
            // D-Mart (Vendor 8)
            $po_details = $data['po_details'] ?? $data;
            $po_items = $data['po_items'] ?? ($po_details['po_items'] ?? []);

            $po_num = $po_details['po_number'] ?? ($data['po_number'] ?? '');
            $po_date = $po_details['po_date'] ?? ($data['po_date'] ?? '');
            $delivery_date = $po_details['exp_delivery_dt'] ?? ($data['exp_delivery_dt'] ?? '');
            $total_qty = (float)str_replace(',', '', $po_details['total_qty'] ?? ($data['total_qty'] ?? 0));
            $total_amount = (float)str_replace(',', '', $po_details['total_value'] ?? ($data['total_value'] ?? 0));
            $unit_price = $this->cleanNumber($po_items[0]['net_price'] ?? 0);

        } elseif ($extraction_no === '7' || $vendor_id == 9) {
            // Rare Rabbit (Vendor 9)
            $po_details = $data['po_details'] ?? $data;
            $po_items = $po_details['po_items'] ?? ($data['po_items'] ?? []);

            $po_num = $po_details['order_no'] ?? ($po_details['po_number'] ?? '');
            $po_date = $po_details['order_date'] ?? ($po_details['po_date'] ?? '');
            $delivery_date = $po_details['delivery_date'] ?? '';
            $colors = $this->extractRareRabbitColors($po_details);

            $total_qty = (float)str_replace(',', '', $po_details['total_qty'] ?? 0);
            $total_amount = $this->cleanNumber($po_details['net_amount'] ?? ($po_details['total_basic_amount'] ?? 0));
            $unit_price = $this->cleanNumber($po_items[0]['rate'] ?? 0);

        } else {
            // Jack & Jones / Default
            $po_details = $data['po_details'] ?? $data;
            $article_info = $data['article_info'] ?? ($data['article_details'] ?? []);
            $po_items = $data['po_items'] ?? [];

            $po_num = $po_details['PO Number'] ?? ($po_details['po_number'] ?? ($po_details['order_no'] ?? ''));
            $po_date = $po_details['PO Date'] ?? ($po_details['po_date'] ?? ($po_details['order_date'] ?? ''));
            $delivery_date = $po_details['Goods Ready Date'] ?? ($po_details['delivery_date'] ?? '');
            $colors = $po_details['Colors'] ?? ($article_info['color'] ?? '');

            $vcpRaw = $po_details['VCP'] ?? ($po_details['vcp'] ?? null);
            $vcpRate = $this->parseNumericAmount($vcpRaw);
            $fallbackPrice = $this->parseNumericAmount($article_info['Price per unit'] ?? 0);
            $unit_price = ($vcpRate > 0) ? $vcpRate : $fallbackPrice;

            foreach ($po_items as $item) {
                preg_match('/[\d,]+/', $item['quatity_uom'] ?? '0', $matches);
                $total_qty += floatval(str_replace(',', '', $matches[0] ?? '0'));
            }
            $total_amount = $total_qty * $unit_price;
        }

        if (empty($po_num)) {
            $po_num = 'PO-' . strtoupper(Str::random(6));
        }

        // Duplicate Check
        $existingPo = PoMaster::where('po_num', $po_num)->first();
        $dupStatus = 'new';
        $dupMsg = 'Ready to save';

        if ($existingPo) {
            if ($existingPo->status == 1) {
                $dupStatus = 'amended';
                $dupMsg = "Already exists (Amended / In Progress).";
            } else {
                $dupStatus = 'unamended';
                $dupMsg = "Already exists (Unamended). Will update on save.";
            }
        }

        // Format single_view_data to match single extract view requirements
        $single_view_data = $data;
        $total_case_lot = 0;

        if ($extraction_no === '3' || $vendor_id == 3) {
            if (isset($data['customer_details'])) {
                $single_view_data['po_details']['customer_address'] = $data['customer_details']['address'] ?? '';
                unset($single_view_data['customer_details']);
            }
        } elseif ($extraction_no === '4' || $vendor_id == 4) {
            if (isset($data['po_details']) && is_array($data['po_details'])) {
                $single_view_data = array_merge($data['po_details'], $data);
            }
        } elseif ($extraction_no === '5' || $vendor_id == 7) {
            $single_view_data = $data;
            if (isset($data['po_details']) && is_array($data['po_details'])) {
                $single_view_data = array_merge($data['po_details'], $data);
            }
        } elseif ($extraction_no === '6' || $vendor_id == 8) {
            $single_view_data = $data;
            if (isset($data['po_items']) && is_array($data['po_items'])) {
                foreach ($data['po_items'] as $item) {
                    $total_case_lot += (float) str_replace(',', '', $item['case_lot'] ?? 0);
                }
            }
        } elseif ($extraction_no === '7' || $vendor_id == 9) {
            if (!isset($data['pos'])) {
                $single_view_data = ['pos' => [$data]];
            }
        }

        return [
            'po_key' => 'po_' . md5($po_num . '_' . uniqid()),
            'original_filename' => $originalName,
            'pdf_base64' => $pdfBase64,
            'po_num' => $po_num,
            'po_date' => $po_date,
            'delivery_date' => $delivery_date,
            'colors' => $colors ?: 'N/A',
            'unit_price' => round($unit_price, 2),
            'total_qty' => $total_qty,
            'total_amount' => round($total_amount, 2),
            'items_count' => count($po_items),
            'hsn_code' => $this->extractHsnCode($article_info, $po_items, $po_details),
            'po_details' => $po_details,
            'article_info' => $article_info,
            'po_items' => $po_items,
            'duplicate_status' => $dupStatus,
            'duplicate_message' => $dupMsg,
            'raw_data' => $data,
            'single_view_data' => $single_view_data,
            'total_case_lot' => $total_case_lot,
            'carton_qty_sizes' => [],
        ];
    }

    /**
     * Store verified bulk POs alone into Database (status = 0, no job order, no packing list, no invoice)
     */
    public function po_import_store(Request $request)
    {
        try {
            $vendor_id = $request->input('vendor_id');
            $pos_data_json = $request->input('pos_data');

            if (empty($vendor_id)) {
                return response()->json(['success' => false, 'message' => 'Vendor is required.']);
            }

            if (empty($pos_data_json)) {
                return response()->json(['success' => false, 'message' => 'No PO data provided for saving.']);
            }

            $pos = is_array($pos_data_json) ? $pos_data_json : json_decode($pos_data_json, true);

            if (empty($pos) || !is_array($pos)) {
                return response()->json(['success' => false, 'message' => 'Invalid PO payload.']);
            }

            $vendor = VendorMaster::find($vendor_id);
            if (!$vendor) {
                return response()->json(['success' => false, 'message' => 'Vendor not found.']);
            }

            $extraction_no = (string)($vendor->extraction_no ?? $vendor_id);

            $savedPoNumbers = [];
            $updatedPoNumbers = [];
            $skippedPoNumbers = [];

            DB::transaction(function () use ($pos, $vendor_id, $extraction_no, &$savedPoNumbers, &$updatedPoNumbers, &$skippedPoNumbers) {
                $prefixSetting = PrefixSetting::where('id', 1)->first();
                if (!$prefixSetting) {
                    throw new \Exception('PO prefix setting not found');
                }

                foreach ($pos as $singlePo) {
                    $poNum = $singlePo['po_num'] ?? '';

                    if (!empty($poNum)) {
                        $existingPo = PoMaster::where('po_num', $poNum)->first();
                        if ($existingPo) {
                            if ($existingPo->status == 1) {
                                $skippedPoNumbers[] = "PO #{$poNum} (Already Amended)";
                                continue;
                            } else {
                                // Delete existing items of unamended PO
                                PoItems::where('po_id', $existingPo->id)->delete();
                                PoSizes::where('po_id', $existingPo->id)->delete();
                                PoDmartSizes::where('po_id', $existingPo->id)->delete();
                                $existingPo->delete();
                                $updatedPoNumbers[] = "PO #{$poNum}";
                            }
                        }
                    }

                    // Store PDF
                    $pdfFileName = null;
                    if (!empty($singlePo['pdf_base64'])) {
                        $randomName = Str::random(40) . '.pdf';
                        Storage::put('public/po/' . $randomName, base64_decode($singlePo['pdf_base64']));
                        $pdfFileName = $randomName;
                    }

                    $currentNumber = $prefixSetting->number;
                    $poNo = $prefixSetting->format . str_pad($currentNumber, 5, '0', STR_PAD_LEFT);
                    $prefixSetting->number = $currentNumber + 1;
                    $prefixSetting->save();

                    // Create PO alone (status = 0, no job orders, no packing lists, no invoices)
                    $poMaster = $this->createPoMasterAlone($vendor_id, $extraction_no, $poNo, $singlePo, $pdfFileName);

                    // Create PO Items alone
                    $this->createPoItemsAlone($vendor_id, $extraction_no, $poMaster->id, $singlePo);

                    $savedPoNumbers[] = $poMaster->po_num;
                }
            });

            $totalSaved = count($savedPoNumbers);
            $totalUpdated = count($updatedPoNumbers);
            $totalSkipped = count($skippedPoNumbers);

            $msg = "{$totalSaved} Purchase Order(s) saved successfully.";
            if ($totalUpdated > 0) {
                $msg .= " ({$totalUpdated} updated)";
            }
            if ($totalSkipped > 0) {
                $msg .= " {$totalSkipped} skipped (already amended).";
            }

            return response()->json([
                'success' => true,
                'message' => $msg,
                'saved' => $savedPoNumbers,
                'updated' => $updatedPoNumbers,
                'skipped' => $skippedPoNumbers,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while saving POs: ' . $e->getMessage()
            ], 500);
        }
    }

    private function createPoMasterAlone($vendor_id, $extraction_no, $poNo, $singlePo, $pdfFileName)
    {
        $po_details = $singlePo['po_details'] ?? [];
        $article_info = $singlePo['article_info'] ?? [];

        $poData = [
            'vendor_id' => $vendor_id,
            'po_ref_num' => $poNo,
            'pdf_file' => $pdfFileName,
            'status' => 0, // Unamended / PO Alone
            'created_by' => auth()->user()->id,
            'created_at' => now(),
        ];

        switch ((string)$vendor_id) {
            case "1":
            case "5":
            case "6":
                $poData = array_merge($poData, [
                    'po_num' => $singlePo['po_num'] ?? ($po_details['PO Number'] ?? ''),
                    'po_date' => $singlePo['po_date'] ?? ($po_details['PO Date'] ?? null),
                    'goods_ready_date' => $singlePo['delivery_date'] ?? ($po_details['Goods Ready Date'] ?? null),
                    'mrp' => $po_details['MRP'] ?? null,
                    'vcp' => $po_details['VCP'] ?? null,
                    'colors' => $singlePo['colors'] ?? ($po_details['Colors'] ?? null),
                    'vendor_del_adr' => $po_details['Delivery Address'] ?? null,
                    'vendor_com_adr' => $po_details['Communication Address'] ?? null,
                    'vendor_gst' => $po_details['GSTIN'] ?? null,
                    'vendor_cin' => $po_details['CIN'] ?? null,
                    'article_info' => is_array($article_info) ? json_encode($article_info) : $article_info,
                    'po_unit_price' => $singlePo['unit_price'] ?? 0,
                    'po_qty' => $singlePo['total_qty'] ?? 0,
                ]);
                break;

            case "2":
                $poData = array_merge($poData, [
                    'po_num' => $singlePo['po_num'] ?? ($po_details['order_no'] ?? ''),
                    'po_date' => $singlePo['po_date'] ?? ($po_details['order_date'] ?? null),
                    'vendor_customer_name' => $po_details['customer_name'] ?? null,
                    'vendor_com_adr' => $po_details['customer_address'] ?? null,
                    'vendor_gst' => $po_details['customer_gstin'] ?? null,
                    'vendor_del_adr' => isset($po_details['ship_to_address']) ? (is_array($po_details['ship_to_address']) ? json_encode($po_details['ship_to_address']) : $po_details['ship_to_address']) : null,
                    'po_unit_price' => $singlePo['unit_price'] ?? 0,
                    'po_qty' => $singlePo['total_qty'] ?? 0,
                ]);
                break;

            case "3":
                $poData = array_merge($poData, [
                    'po_num' => $singlePo['po_num'] ?? ($po_details['po_number'] ?? ''),
                    'po_date' => $singlePo['po_date'] ?? ($po_details['po_release_date'] ?? null),
                    'goods_ready_date' => $singlePo['delivery_date'] ?? ($po_details['po_ehd'] ?? null),
                    'vendor_com_adr' => $po_details['customer_address'] ?? null,
                    'vendor_del_adr' => $po_details['delivery_address'] ?? null,
                    'article_info' => is_array($article_info) ? json_encode($article_info) : $article_info,
                    'po_unit_price' => $singlePo['unit_price'] ?? 0,
                    'po_qty' => $singlePo['total_qty'] ?? 0,
                ]);
                break;

            case "4":
                $poData = array_merge($poData, [
                    'po_num' => $singlePo['po_num'] ?? ($po_details['order_no'] ?? null),
                    'po_date' => $singlePo['po_date'] ?? ($po_details['order_date'] ?? null),
                    'goods_ready_date' => $singlePo['delivery_date'] ?? ($po_details['delivery_date'] ?? null),
                    'vendor_gst' => $po_details['gstin'] ?? null,
                    'vendor_del_adr' => isset($po_details['ship_to_address']) ? (is_array($po_details['ship_to_address']) ? json_encode($po_details['ship_to_address']) : $po_details['ship_to_address']) : null,
                    'season' => $po_details['season'] ?? null,
                    'po_unit_price' => $singlePo['unit_price'] ?? 0,
                    'po_qty' => $singlePo['total_qty'] ?? 0,
                ]);
                break;

            case "7":
                $totalQty = 0;
                $unitPrice = 0;
                $colors = [];

                $vendorInfo = [];
                if (isset($po_details['vendor_info'])) {
                    $vendorInfo = $po_details['vendor_info'];
                } elseif (isset($po_details['Vendor'])) {
                    $vendorInfo = [
                        'Vendor' => $po_details['Vendor'] ?? null,
                        'Price per unit' => $po_details['Price per unit'] ?? null,
                        'Total unit' => $po_details['Total unit'] ?? null,
                        'Net Value' => $po_details['Net Value'] ?? null,
                    ];
                }

                $poItems = $singlePo['po_items'] ?? ($po_details['po_items'] ?? []);
                if (is_array($poItems)) {
                    foreach ($poItems as $item) {
                        $totalQty += floatval(str_replace(',', '', $item['Qty'] ?? 0));
                        if ($unitPrice == 0) {
                            $unitPrice = floatval(str_replace(',', '', $item['Rate/Unit'] ?? 0));
                        }
                    }
                }

                if ($unitPrice == 0 && isset($vendorInfo['Price per unit'])) {
                    $unitPrice = floatval(str_replace(',', '', $vendorInfo['Price per unit']));
                }
                if ($unitPrice == 0) {
                    $unitPrice = floatval($singlePo['unit_price'] ?? 0);
                }

                if ($totalQty == 0 && isset($vendorInfo['Total unit'])) {
                    $totalQty = floatval(str_replace(',', '', $vendorInfo['Total unit']));
                }
                if ($totalQty == 0) {
                    $totalQty = floatval($singlePo['total_qty'] ?? 0);
                }

                $materialDescriptions = $po_details['material_descriptions'] ?? ($singlePo['raw_data']['material_descriptions'] ?? []);
                if (is_array($materialDescriptions)) {
                    foreach ($materialDescriptions as $material) {
                        if (!empty($material['Colour'])) {
                            $colors[] = $material['Colour'];
                        }
                    }
                }

                $colorStr = $singlePo['colors'] ?? '';
                if (empty($colorStr) || $colorStr === 'N/A') {
                    $colorStr = implode(', ', array_unique($colors));
                }

                $poData = array_merge($poData, [
                    'po_num' => $singlePo['po_num'] ?? ($po_details['po_number'] ?? null),
                    'po_date' => $singlePo['po_date'] ?? ($po_details['po_date'] ?? null),
                    'goods_ready_date' => $poItems[0]['Delivery Date'] ?? ($singlePo['delivery_date'] ?? null),
                    'vendor_del_adr' => is_array($po_details['bill_to_address'] ?? null)
                        ? implode(', ', $po_details['bill_to_address'])
                        : ($po_details['bill_to_address'] ?? null),
                    'vendor_com_adr' => is_array($po_details['ship_to_address'] ?? null)
                        ? implode(', ', $po_details['ship_to_address'])
                        : ($po_details['ship_to_address'] ?? null),
                    'vendor_gst' => $po_details['gstin'] ?? ($po_details['gst_number'] ?? null),
                    'colors' => $colorStr ?: null,
                    'po_unit_price' => $unitPrice,
                    'po_qty' => $totalQty,
                    'article_info' => json_encode([
                        'vendor_number' => $vendorInfo['Vendor'] ?? ($po_details['vendor_number'] ?? null),
                        'price_per_unit' => $vendorInfo['Price per unit'] ?? null,
                        'total_unit' => $vendorInfo['Total unit'] ?? null,
                        'net_value' => $vendorInfo['Net Value'] ?? null,
                    ]),
                ]);
                break;

            case "8":
                $totalCaseLot = $singlePo['total_case_lot'] ?? 0;
                $itemDescriptions = [];
                $poItems = $singlePo['po_items'] ?? ($po_details['po_items'] ?? []);
                foreach ($poItems as $item) {
                    if ($totalCaseLot == 0) {
                        $totalCaseLot += (float) str_replace(',', '', $item['case_lot'] ?? 0);
                    }
                    if (!empty($item['description'])) {
                        $itemDescriptions[] = $item['description'];
                    }
                }

                $poData = array_merge($poData, [
                    'po_num'           => $singlePo['po_num'] ?? ($po_details['po_number'] ?? null),
                    'po_date'          => $singlePo['po_date'] ?? ($po_details['po_date'] ?? null),
                    'goods_ready_date' => $singlePo['delivery_date'] ?? ($po_details['exp_delivery_dt'] ?? null),
                    'vendor_del_adr'   => $po_details['buyer_address'] ?? null,
                    'vendor_com_adr'   => $po_details['vendor_address'] ?? null,
                    'vendor_gst'       => $po_details['vendor_gstin'] ?? null,
                    'po_unit_price'    => $singlePo['unit_price'] ?? ($poItems[0]['net_price'] ?? 0),
                    'po_qty'           => (float) str_replace(',', '', $singlePo['total_qty'] ?? ($po_details['total_qty'] ?? 0)),
                    'article_info'     => json_encode([
                        'buyer_name'        => $po_details['buyer_name'] ?? null,
                        'buyer_cin'         => $po_details['buyer_cin'] ?? null,
                        'buyer_gstin'       => $po_details['buyer_gstin'] ?? null,
                        'buyer_attn'        => $po_details['buyer_attn'] ?? null,
                        'buyer_email'       => $po_details['buyer_email'] ?? null,
                        'buyer_buyer'       => $po_details['buyer_buyer'] ?? null,
                        'vendor_name'       => $po_details['vendor_name'] ?? null,
                        'vendor_phone'      => $po_details['vendor_phone'] ?? null,
                        'vendor_email'      => $po_details['vendor_email'] ?? null,
                        'total_boxes'       => $po_details['total_boxes'] ?? null,
                        'total_value'       => $po_details['total_value'] ?? null,
                        'amount_in_words'   => $po_details['amount_in_words'] ?? null,
                        'total_ctn'         => $po_details['total_boxes'] ?? null,
                        'total_caselot'     => $totalCaseLot,
                        'total_qty'         => $po_details['total_qty'] ?? null,
                        'item_descriptions' => $itemDescriptions,
                        'po_items'          => $poItems,
                    ]),
                ]);
                break;

            case "9":
                $poData = array_merge($poData, [
                    'po_num' => $singlePo['po_num'] ?? ($po_details['order_no'] ?? null),
                    'po_date' => $singlePo['po_date'] ?? ($po_details['order_date'] ?? null),
                    'goods_ready_date' => $singlePo['delivery_date'] ?? null,
                    'vendor_customer_name' => $po_details['warehouse_name'] ?? null,
                    'vendor_del_adr' => $po_details['warehouse_address'] ?? null,
                    'vendor_com_adr' => $po_details['vendor_address'] ?? null,
                    'vendor_gst' => $po_details['vendor_gstin'] ?? ($po_details['buyer_gstin'] ?? null),
                    'vendor_cin' => $po_details['cin'] ?? null,
                    'po_unit_price' => $singlePo['unit_price'] ?? 0,
                    'po_qty' => $singlePo['total_qty'] ?? 0,
                    'colors' => $singlePo['colors'] ?? null,
                    'article_info' => json_encode([
                        'category' => $po_details['category'] ?? null,
                        'channel' => $po_details['channel'] ?? null,
                        'warehouse_city_name' => $po_details['warehouse_name'] ?? null,
                        'vendor_name' => $po_details['vendor_name'] ?? null,
                        'total_basic_amount' => $po_details['total_basic_amount'] ?? null,
                        'net_amount' => $po_details['net_amount'] ?? null,
                    ]),
                ]);
                break;

            default:
                $poData = array_merge($poData, [
                    'po_num' => $singlePo['po_num'] ?? '',
                    'po_date' => $singlePo['po_date'] ?? null,
                    'goods_ready_date' => $singlePo['delivery_date'] ?? null,
                    'colors' => $singlePo['colors'] ?? null,
                    'po_unit_price' => $singlePo['unit_price'] ?? 0,
                    'po_qty' => $singlePo['total_qty'] ?? 0,
                    'article_info' => is_array($article_info) ? json_encode($article_info) : $article_info,
                ]);
                break;
        }

        return PoMaster::create($poData);
    }

    private function createPoItemsAlone($vendor_id, $extraction_no, $po_id, $singlePo)
    {
        $po_items = $singlePo['po_items'] ?? [];
        $po_details = $singlePo['po_details'] ?? [];
        $article_info = $singlePo['article_info'] ?? [];
        $hsn_code = $singlePo['hsn_code'] ?? '61051090';
        $carton_qty_sizes = $singlePo['carton_qty_sizes'] ?? [];

        switch ((string)$vendor_id) {
            case "1":
            case "5":
            case "6":
                $this->createJackJonesItemsAlone($po_id, $po_items);
                break;

            case "2":
                $this->createSkechersItemsAlone($po_id, $po_items, $hsn_code);
                break;

            case "3":
                $this->createPumaItemsAlone($po_id, $po_items, $article_info, $hsn_code);
                break;

            case "4":
                $this->createBenettonItemsAlone($po_id, $po_items, $po_details, $vendor_id);
                break;

            case "7":
                $this->createAdityaItemsAlone($po_id, $po_items, $po_details, $hsn_code);
                break;

            case "8":
                $this->createDmartItemsAlone($po_id, $po_items, $po_details, $hsn_code, $carton_qty_sizes);
                break;

            case "9":
                $this->createRareRabbitItemsAlone($po_id, $po_items, $hsn_code);
                break;

            default:
                $this->createJackJonesItemsAlone($po_id, $po_items);
                break;
        }
    }

    private function createJackJonesItemsAlone($po_id, $po_items)
    {
        foreach ($po_items as $po_item) {
            $quantityUom = $po_item['quatity_uom'] ?? '0';
            preg_match('/^([\d,]+)\s*(.*)$/', $quantityUom, $matches);
            $qty = isset($matches[1]) ? (int)str_replace(',', '', $matches[1]) : 0;
            $uom = isset($matches[2]) ? trim($matches[2]) : null;

            $idColorField = $po_item['artcicle_id_color'] ?? '';
            $colorId = null;
            $colorName = null;

            if (strpos($idColorField, '/') !== false) {
                $colorParts = explode('/', $idColorField, 2);
                $colorId = trim($colorParts[0]);
                $colorName = trim($colorParts[1]);
            } else {
                $colorName = trim($idColorField);
            }

            PoItems::create([
                'po_id' => $po_id,
                'sno' => $po_item['item_sno'] ?? null,
                'article_number' => $po_item['article_number'] ?? null,
                'id_color' => $colorId,
                'color' => $colorName,
                'size' => $po_item['size_years'] ?? null,
                'qty' => $qty,
                'uom' => $uom,
                'igst_taxable_value' => $po_item['igst_taxable_value'] ?? 0,
                'igst_per' => $po_item['igst_percentage'] ?? 5,
                'mrp' => $po_item['mrp'] ?? null,
                'ean_code' => $po_item['ean_code'] ?? null,
                'hsn_code' => $po_item['hsn_code'] ?? null,
                'created_at' => now(),
                'created_by' => auth()->user()->id,
            ]);
        }
    }

    private function createSkechersItemsAlone($po_id, $po_items, $hsn_code)
    {
        $sizeColumns = ['XS', 'S', 'M', 'L', 'XL', 'XXL', 'XXXL'];

        foreach ($po_items as $index => $po_item) {
            if (empty($po_item['Style No.']) || stripos($po_item['Style No.'], 'total') !== false) {
                continue;
            }

            foreach ($sizeColumns as $size) {
                $qty = (int) str_replace(',', '', $po_item[$size] ?? '0');
                if ($qty <= 0) continue;

                PoItems::create([
                    'po_id' => $po_id,
                    'sno' => $po_item['Sr. No.'] ?? $index + 1,
                    'article_number' => $po_item['Style No.'] ?? null,
                    'gender' => $po_item['Gender'] ?? null,
                    'type' => $po_item['Type'] ?? null,
                    'content' => $po_item['Content'] ?? null,
                    'color' => $po_item['Color'] ?? null,
                    'color_code' => $po_item['Color Code'] ?? null,
                    'size' => $size,
                    'qty' => $qty,
                    'unit_price' => $this->cleanNumber($po_item['Unit Price (INR) - (b)'] ?? 0),
                    'igst_per' => $this->cleanPercentage($po_item['IGST'] ?? '0'),
                    'igst_taxable_value' => $this->cleanNumber($po_item['Gst Total'] ?? 0),
                    'total_amount' => $this->cleanNumber($po_item['Amount (INR) - (c = a x b)'] ?? 0),
                    'fi_dates' => $po_item['FI dates'] ?? null,
                    'hsn_code' => $hsn_code,
                    'country' => 'India',
                    'created_at' => now(),
                    'created_by' => auth()->user()->id,
                ]);
            }
        }
    }

    private function createPumaItemsAlone($po_id, $po_items, $article_info, $hsn_code)
    {
        foreach ($po_items as $index => $po_item) {
            if (isset($po_item['size']) && strtolower($po_item['size']) == 'total') {
                continue;
            }

            PoItems::create([
                'po_id' => $po_id,
                'sno' => $index + 1,
                'article_number' => $article_info['article_number'] ?? null,
                'style_description' => $article_info['style_description'] ?? null,
                'color' => $article_info['color'] ?? null,
                'product_character' => $article_info['product_character'] ?? null,
                'size' => $po_item['size'] ?? null,
                'qty' => $po_item['quantity'] ?? 0,
                'unit_price' => $po_item['unit_price'] ?? 0,
                'pack_factor' => $po_item['pack_factor'] ?? 0,
                'sku_line_no' => $po_item['sku_line_no'] ?? null,
                'incoterm' => $po_item['incoterm'] ?? null,
                'named_place' => $po_item['named_place'] ?? null,
                'hsn_code' => $hsn_code,
                'created_at' => now(),
                'created_by' => auth()->user()->id,
            ]);
        }
    }

    private function createBenettonItemsAlone($po_id, $po_items, $po_details, $vendor_id)
    {
        $size_tables = $po_details['size_tables'] ?? [];
        $processedColors = [];

        foreach ($po_items as $po_item) {
            $color = $po_item['Col'] ?? null;
            if (empty($color) || $color === 'Total') {
                continue;
            }

            $masterQty = (int) str_replace(',', '', $po_item['Qty'] ?? 0);
            $masterCost = (float) str_replace(',', '', $po_item['Basic Cost'] ?? 0);

            PoItems::create([
                'po_id' => $po_id,
                'sno' => $po_item['S.N o'] ?? 0,
                'article_number' => $po_item['Part No'] ?? null,
                'part_description' => $po_item['Part Description'] ?? null,
                'id_color' => $color,
                'color' => $color,
                'qty' => $masterQty,
                'unit_price' => $masterCost,
                'material_value' => $masterQty * $masterCost,
                'igst_per' => $po_item['IGST %'] ?? 0,
                'igst_taxable_value' => $masterQty * $masterCost * (($po_item['IGST %'] ?? 0) / 100),
                'total_value' => $masterQty * $masterCost * (1 + (($po_item['IGST %'] ?? 0) / 100)),
                'due_date' => $po_item['Due Date'] ?? null,
                'mrp' => $po_item['MRP/UNIT'] ?? 0,
                'hsn_code' => $po_item['HSN Code'] ?? null,
                'created_at' => now(),
                'created_by' => auth()->user()->id,
            ]);

            if (in_array($color, $processedColors, true)) {
                continue;
            }

            $sizes = [];
            $qtyData = [];
            foreach ($size_tables as $table) {
                foreach ($table['rows'] ?? [] as $row) {
                    if ($row[0] == $color) {
                        $sizes = $table['headers'] ?? [];
                        $qtyData = explode(' ', $row[1] ?? '');
                        break 2;
                    }
                }
            }

            if (!empty($sizes) && !empty($qtyData)) {
                foreach ($sizes as $idx => $size) {
                    $sQty = isset($qtyData[$idx]) ? (int) str_replace(',', '', $qtyData[$idx]) : 0;
                    if ($sQty <= 0) continue;

                    PoSizes::create([
                        'po_id' => $po_id,
                        'vendor_id' => $vendor_id,
                        'color' => $color,
                        'size' => $size,
                        'qty' => $sQty,
                        'created_at' => now(),
                        'created_by' => auth()->user()->id,
                    ]);
                }
            }

            $processedColors[] = $color;
        }
    }

    private function createAdityaItemsAlone($po_id, $po_items, $po_details, $hsn_code)
    {
        $materialDescriptions = $po_details['material_descriptions'] ?? [];

        foreach ($po_items as $index => $item) {
            $materialDesc = $materialDescriptions[$index] ?? [];

            PoItems::create([
                'po_id' => $po_id,
                'sno' => $index + 1,
                'article_number' => $item['Material Code'] ?? null,
                'hsn_code' => $item['HSN Number'] ?? $hsn_code,
                'qty' => floatval($item['Qty'] ?? 0),
                'uom' => $item['Unit'] ?? null,
                'material_value' => floatval($item['Rate/Unit'] ?? 0),
                'igst_taxable_value' => $item['Net Value'] ?? 0,
                'igst_per' => floatval($item['IGST %'] ?? 0),
                'size' => $item['Size'] ?? null,
                'location' => $item['Stor e Loc'] ?? null,
                'due_date' => $item['Delivery Date'] ?? null,
                'content' => $materialDesc['Material'] ?? null,
                'style_description' => $materialDesc['Material description'] ?? null,
                'color' => $materialDesc['Colour'] ?? null,
                'product_character' => $materialDesc['Warer Trail'] ?? null,
                'mrp' => $item['MRP'] ?? ($item['Mrp'] ?? null),
                'created_at' => now(),
                'created_by' => auth()->user()->id,
                'status' => 0,
            ]);
        }
    }

    private function createDmartItemsAlone($po_id, $po_items, $po_details, $hsn_code, $carton_qty_sizes = [])
    {
        $firstItem = $po_items[0] ?? [];
        $articleDescription = $this->extractArticleDescription($firstItem['description'] ?? null);
        $eanCode = $firstItem['ean'] ?? null;
        $hsnCode = !empty($firstItem['hsn']) ? $firstItem['hsn'] : $hsn_code;
        $gstPercentage = $this->cleanPercentage($firstItem['cgst_igst_pct'] ?? 0);
        $price = $this->cleanNumber($firstItem['l_price'] ?? 0);
        $mrpPrice = $this->cleanNumber($firstItem['mrp'] ?? 0);
        $totalQtyFromPdf = (float) str_replace(',', '', $po_details['total_qty'] ?? 0);

        if (!empty($carton_qty_sizes)) {
            $colorCount = collect($carton_qty_sizes)->pluck('color')->filter()->unique()->count();

            foreach ($carton_qty_sizes as $row) {
                $color = trim($row['color'] ?? '');
                $size = $row['size'] ?? null;
                $qty = isset($row['qty']) ? (int) str_replace(',', '', $row['qty']) : 0;

                if ($color === '' || empty($size) || $qty <= 0) {
                    continue;
                }

                $caseLot = isset($row['case_lot']) ? (float) str_replace(',', '', $row['case_lot']) : 0;

                $ratio = isset($row['ratio'])
                    ? (float) $row['ratio']
                    : ($colorCount > 0 ? round($caseLot / $colorCount, 2) : 0);

                $totalCartons = isset($row['total_cartons'])
                    ? (float) $row['total_cartons']
                    : ($caseLot > 0 ? round($totalQtyFromPdf / $caseLot, 2) : 0);

                PoDmartSizes::create([
                    'po_id'                => $po_id,
                    'article_description'  => $articleDescription,
                    'ean_code'             => $eanCode,
                    'hsn_code'             => $hsnCode,
                    'color'                => $color,
                    'size'                 => $size,
                    'carton_qty'           => $qty,
                    'ratio'                => $ratio,
                    'total_cartons'        => $totalCartons,
                    'case_lot'             => $caseLot,
                    'total_qty'            => $totalQtyFromPdf,
                    'gst_percentage'       => $gstPercentage,
                    'price'                => $price,
                    'mrp_price'            => $mrpPrice,
                    'created_at'           => now(),
                    'created_by'           => auth()->user()->id,
                    'status'               => 0,
                ]);
            }
        } else {
            foreach ($po_items as $index => $item) {
                PoItems::create([
                    'po_id' => $po_id,
                    'sno' => $index + 1,
                    'article_number' => $item['ean'] ?? null,
                    'style_description' => $item['description'] ?? $articleDescription,
                    'qty' => (float) str_replace(',', '', $item['qty'] ?? 0),
                    'unit_price' => $this->cleanNumber($item['l_price'] ?? $price),
                    'mrp' => $this->cleanNumber($item['mrp'] ?? $mrpPrice),
                    'hsn_code' => $item['hsn'] ?? $hsnCode,
                    'igst_per' => $gstPercentage,
                    'created_at' => now(),
                    'created_by' => auth()->user()->id,
                    'status' => 0,
                ]);
            }
        }
    }

    private function createRareRabbitItemsAlone($po_id, $po_items, $hsn_code)
    {
        foreach ($po_items as $index => $item) {
            $description = trim($item['description'] ?? '');
            $words = $description !== '' ? explode(' ', $description) : [];
            $color = $words ? end($words) : null;

            PoItems::create([
                'po_id' => $po_id,
                'sno' => $index + 1,
                'article_number' => $item['ean'] ?? null,
                'content' => $description,
                'color' => $color,
                'size' => $item['size'] ?? null,
                'qty' => (int) str_replace(',', '', $item['quantity'] ?? 0),
                'uom' => $item['uom'] ?? null,
                'unit_price' => $this->cleanNumber($item['rate'] ?? 0),
                'total_amount' => $this->cleanNumber($item['amount'] ?? 0),
                'hsn_code' => $item['hsn'] ?? $hsn_code,
                'created_at' => now(),
                'created_by' => auth()->user()->id,
            ]);
        }
    }

    private function cleanNumber($value)
    {
        return (float) str_replace([',', '₹', ' '], '', $value ?? 0);
    }

    private function cleanPercentage($value)
    {
        return (float) str_replace('%', '', $value ?? 0);
    }

    private function parseNumericAmount($val)
    {
        if (is_numeric($val)) return floatval($val);
        if (empty($val)) return 0.0;
        $clean = str_replace(',', '', (string)$val);
        if (preg_match('/\d+(\.\d+)?/', $clean, $matches)) {
            return floatval($matches[0]);
        }
        return 0.0;
    }

    private function extractHsnCode($article_info = [], $po_items = [], $po_details = [])
    {
        if (is_array($po_items)) {
            foreach ($po_items as $item) {
                if (is_array($item)) {
                    foreach (['hsn_code', 'hsn', 'HSN', 'HSN Code', 'HSN Number'] as $key) {
                        if (!empty($item[$key])) {
                            return (string)$item[$key];
                        }
                    }
                }
            }
        }
        if (is_array($article_info)) {
            foreach (['Customs code', 'HSN Code', 'hsn_code', 'HSN', 'hsn', 'Customs Code'] as $key) {
                if (!empty($article_info[$key])) {
                    return (string)$article_info[$key];
                }
            }
        }
        if (is_array($po_details)) {
            foreach (['HSN Code', 'HSN', 'hsn_code', 'hsn'] as $key) {
                if (!empty($po_details[$key])) {
                    return (string)$po_details[$key];
                }
            }
        }
        return '61051090';
    }

    private function extractRareRabbitColors($po_details)
    {
        $colors = [];
        foreach ($po_details['po_items'] ?? [] as $item) {
            $description = trim($item['description'] ?? '');
            if ($description === '') {
                continue;
            }
            $words = explode(' ', $description);
            $colors[] = end($words);
        }
        return implode(', ', array_unique(array_filter($colors)));
    }

    /**
     * Render Bulk PL Import Page
     */
    public function pl_import()
    {
        $page_data = [
            'page_title' => "Bulk PL Import",
            'page_main_title' => "Bulk Import",
            'page_child_title' => "PL Import",
            'isSuperAdmin' => $this->isSuperAdmin,
        ];

        $page_data['vendors'] = VendorMaster::whereIn('status', [0, 1])
            ->orderBy('id', 'asc')
            ->get();

        return view('bulk_import.pl_import', $page_data);
    }

    /**
     * Process uploaded Excel file for Packing List Import
     */
    public function pl_import_process(Request $request)
    {
        try {
            $vendor_id = $request->input('vendor_id', 1);
            $file = $request->file('excel_file');

            if (empty($vendor_id)) {
                return response()->json(['error' => 'Please select a vendor.'], 400);
            }

            if (empty($file)) {
                return response()->json(['error' => 'Please select an Excel file (.xlsx, .xls, .csv).'], 400);
            }

            $vendor = VendorMaster::find($vendor_id);
            if (!$vendor) {
                return response()->json(['error' => 'Selected vendor not found.'], 404);
            }

            // Load Excel file using PhpSpreadsheet
            $spreadsheet = IOFactory::load($file->getRealPath());
            $sheet = $spreadsheet->getActiveSheet();
            $rawRows = $sheet->toArray(null, true, true, true);

            if (empty($rawRows) || count($rawRows) < 2) {
                return response()->json(['error' => 'The uploaded file is empty or missing data rows.'], 400);
            }

            // Detect header row (look for "PO Number" or "Color" in top 5 rows)
            $headerRowIndex = null;
            $headerMap = []; // column letter => normalized name

            foreach ($rawRows as $rIdx => $row) {
                if ($rIdx > 5) break;
                foreach ($row as $colLetter => $val) {
                    $cleanVal = strtoupper(preg_replace('/[^A-Z0-9]/i', '', trim((string)$val)));
                    if (in_array($cleanVal, ['PONUMBER', 'PONO', 'PO', 'COLOR', 'COLOUR'])) {
                        $headerRowIndex = $rIdx;
                        break 2;
                    }
                }
            }

            if ($headerRowIndex === null) {
                $headerRowIndex = 1; // Default to row 1
            }

            $headerRow = $rawRows[$headerRowIndex] ?? [];

            // Map standard column types
            $colPoNum = null;
            $colJobNo = null;
            $colPlNo = null;
            $colPackingTable = null;
            $colLocation = null;
            $colColor = null;
            $colCartonFrom = null;
            $colCartonTo = null;
            $colDimension = null;
            $colNetWeight = null;
            $sizeCols = []; // colLetter => sizeName

            foreach ($headerRow as $colLetter => $headerText) {
                $rawHeader = trim((string)$headerText);
                if (empty($rawHeader)) continue;

                $clean = strtoupper(preg_replace('/[^A-Z0-9]/i', '', $rawHeader));

                if (in_array($clean, ['PONUMBER', 'PONO', 'PO', 'PURCHASEORDER', 'PURCHASEORDERNUMBER'])) {
                    $colPoNum = $colLetter;
                } elseif (in_array($clean, ['JOBORDERNO', 'JOBNO', 'JOBORDER', 'JOBNUM', 'JOBNUMBER'])) {
                    $colJobNo = $colLetter;
                } elseif (in_array($clean, ['PACKINGLISTNO', 'PACKINGLISTNUMBER', 'PACKINGLIST', 'PLNO', 'PLNUM', 'PACKINGLISTREFNO', 'PL', 'PACKINGLIST1OR2', 'PACKINGLISTNO12'])) {
                    $colPlNo = $colLetter;
                } elseif (in_array($clean, ['PACKINGTABLE', 'PACKINGTABLE1OR2', 'PACKINGTABLENO', 'TABLE', 'PACKINGTABLE12'])) {
                    $colPackingTable = $colLetter;
                } elseif (in_array($clean, ['LOCATION', 'LOC', 'DELIVERYLOCATION', 'WAREHOUSE', 'DESTINATION', 'CONSIGNEELOCATION'])) {
                    $colLocation = $colLetter;
                } elseif (in_array($clean, ['COLOR', 'COLOUR'])) {
                    $colColor = $colLetter;
                } elseif (in_array($clean, ['CARTONFROM', 'FROMCARTON', 'CARTONSTART', 'CTNFROM', 'FROMCTN'])) {
                    $colCartonFrom = $colLetter;
                } elseif (in_array($clean, ['CARTONTO', 'TOCARTON', 'CARTONEND', 'CTNTO', 'TOCTN'])) {
                    $colCartonTo = $colLetter;
                } elseif (in_array($clean, ['CARTONDIMENSION', 'DIMENSION', 'CARTONDIMENSIONS', 'DIMENSIONS', 'CTNDIMENSION'])) {
                    $colDimension = $colLetter;
                } elseif (in_array($clean, ['NETWTCTNKG', 'NETWTCTN', 'NETWT', 'NETWEIGHT', 'NETWTKG', 'NETWEIGHTKG', 'NETWTCTNKG'])) {
                    $colNetWeight = $colLetter;
                } else {
                    // It's a Size column (e.g. XS, S, M, L, XL, XXL, 038H, 039H, 2/3Y, 3/4Y, etc.)
                    $sizeCols[$colLetter] = $rawHeader;
                }
            }

            // Verify essential headers
            if (!$colPoNum || !$colColor || !$colCartonFrom || !$colCartonTo) {
                return response()->json([
                    'error' => 'Required columns are missing. Please ensure your Excel includes: PO Number, Color, Carton From, Carton To, Carton Dimension, and Net Wt / Ctn (Kg).'
                ], 400);
            }

            // Fetch Vendor Cartons for dimension matching
            $allCartons = CartonMaster::where('vendor_id', $vendor_id)->whereIn('status', [0, 1])->get();
            $defaultCarton = $allCartons->first();

            // Group rows by PO Number and Packing List / Color / Location
            $groupedData = [];

            for ($r = $headerRowIndex + 1; $r <= count($rawRows); $r++) {
                $row = $rawRows[$r] ?? null;
                if (!$row) continue;

                $poNum = trim((string)($row[$colPoNum] ?? ''));
                $color = trim((string)($row[$colColor] ?? ''));

                if (empty($poNum) || empty($color)) {
                    continue; // Skip empty / spacer lines
                }

                $jobNo = $colJobNo ? trim((string)($row[$colJobNo] ?? '')) : '';
                $plNo = $colPlNo ? trim((string)($row[$colPlNo] ?? '')) : '';
                $location = $colLocation ? trim((string)($row[$colLocation] ?? '')) : '';
                $packingTable = $colPackingTable ? intval(trim((string)($row[$colPackingTable] ?? 1))) : 2;
                if (!in_array($packingTable, [1, 2])) {
                    $packingTable = 2;
                }

                $cartonFrom = intval(trim((string)($row[$colCartonFrom] ?? 0)));
                $cartonTo = intval(trim((string)($row[$colCartonTo] ?? $cartonFrom)));

                if ($cartonFrom <= 0) {
                    continue;
                }
                if ($cartonTo < $cartonFrom) {
                    $cartonTo = $cartonFrom;
                }

                $dimensionStr = $colDimension ? trim((string)($row[$colDimension] ?? '60x40x25')) : '60x40x25';
                $netWeight = $colNetWeight ? floatval(trim((string)($row[$colNetWeight] ?? 0))) : 0;

                // Extract sizes with qty > 0 for this row
                $rowSizes = [];
                foreach ($sizeCols as $cLetter => $sName) {
                    $sQty = intval(trim((string)($row[$cLetter] ?? 0)));
                    if ($sQty > 0) {
                        $rowSizes[$sName] = $sQty;
                    }
                }

                if (empty($rowSizes)) {
                    continue;
                }

                if (!isset($groupedData[$poNum])) {
                    $groupedData[$poNum] = [
                        'po_num' => $poNum,
                        'job_no' => $jobNo,
                        'packing_table_no' => $packingTable,
                        'packing_lists' => []
                    ];
                }

                // Group by PL No + Location + Color
                $plKeyParts = [];
                if ($plNo) $plKeyParts[] = 'PL' . $plNo;
                if ($location) $plKeyParts[] = $location;
                $plKeyParts[] = $color;
                $plKey = implode('_', $plKeyParts);

                if (!isset($groupedData[$poNum]['packing_lists'][$plKey])) {
                    $groupedData[$poNum]['packing_lists'][$plKey] = [
                        'pl_no' => $plNo,
                        'location' => $location,
                        'color' => $color,
                        'job_no' => $jobNo,
                        'packing_table_no' => $packingTable,
                        'carton_rows' => [],
                        'cartons_expanded' => []
                    ];
                }

                $groupedData[$poNum]['packing_lists'][$plKey]['carton_rows'][] = [
                    'carton_from' => $cartonFrom,
                    'carton_to' => $cartonTo,
                    'sizes' => $rowSizes,
                    'dimension' => $dimensionStr,
                    'net_weight' => $netWeight,
                ];

                // Expand carton ranges (e.g. 7 to 8 -> C7 and C8 for Bestseller, 7 and 8 for Puma)
                for ($c = $cartonFrom; $c <= $cartonTo; $c++) {
                    $cName = in_array($vendor_id, [1, 5, 6]) ? ('C' . $c) : (string)$c;
                    $groupedData[$poNum]['packing_lists'][$plKey]['cartons_expanded'][] = [
                        'carton_number' => $c,
                        'carton_name' => $cName,
                        'sizes' => $rowSizes,
                        'dimension' => $dimensionStr,
                        'net_weight' => $netWeight,
                        'total_qty' => array_sum($rowSizes),
                    ];
                }
            }

            if (empty($groupedData)) {
                return response()->json(['error' => 'No valid carton and size data found in the uploaded file.'], 400);
            }

            // Process PO verification, size breakdown, carton matching for each PO & Packing List
            $posProcessed = [];
            $allSizesEncountered = [];

            foreach ($groupedData as $poNum => $poGroup) {
                // Check if PO exists in database
                $dbPo = PoMaster::with(['po_items', 'vendor'])->where('po_num', $poNum)->first();

                $poExists = $dbPo ? true : false;
                $isAmended = ($dbPo && $dbPo->status == 1);
                $hasExistingPl = false;
                $existingPlList = [];

                if ($dbPo) {
                    $existingPls = PackingListMaster::where('po_id', $dbPo->id)->get();
                    if ($existingPls->isNotEmpty()) {
                        $hasExistingPl = true;
                        $existingPlList = $existingPls->pluck('pack_ref_no')->toArray();
                    }
                }

                $poJobNum = $dbPo ? $dbPo->po_job_num : ($poGroup['job_no'] ?: 'JJ-' . substr($poNum, -4));
                $poDate = $dbPo ? $dbPo->po_date : date('Y-m-d');
                $poItems = $dbPo ? $dbPo->po_items : collect();

                $poArticleInfo = $dbPo ? json_decode($dbPo->article_info, true) : [];
                $styleDesc = $poArticleInfo['Article description'] ?? ($poItems->first()->style_description ?? 'POLOS');

                $plList = [];
                $plSeqCounter = 1;

                foreach ($poGroup['packing_lists'] as $plKey => $plGroup) {
                    $colorName = $plGroup['color'];
                    $cartons = $plGroup['cartons_expanded'];
                    $plNo = !empty($plGroup['pl_no']) ? $plGroup['pl_no'] : (string)$plSeqCounter;
                    $jobNoFinal = $plGroup['job_no'] ?: $poJobNum;
                    
                    // Pack ref no formatted as {job_no}/{pl_no} e.g. JJ-3466/1, JJ-3519/1
                    $packRefNo = "{$jobNoFinal}/{$plNo}";
                    
                    // Sort cartons by carton number
                    usort($cartons, function ($a, $b) {
                        return $a['carton_number'] <=> $b['carton_number'];
                    });

                    // Calculate totals
                    $totalCartons = count($cartons);
                    $totalQty = 0;
                    $totalNetWeight = 0;
                    $sizeTotals = [];

                    foreach ($cartons as $cItem) {
                        $totalNetWeight += floatval($cItem['net_weight']);
                        foreach ($cItem['sizes'] as $sz => $qty) {
                            $totalQty += $qty;
                            $sizeTotals[$sz] = ($sizeTotals[$sz] ?? 0) + $qty;
                            if (!in_array($sz, $allSizesEncountered)) {
                                $allSizesEncountered[] = $sz;
                            }
                        }
                    }

                    // Check existing packing list in database
                    $existingPl = null;
                    if ($dbPo) {
                        $existingPl = PackingListMaster::where('po_id', $dbPo->id)
                            ->where(function($q) use ($packRefNo, $colorName) {
                                $q->where('pack_ref_no', $packRefNo);
                            })
                            ->first();
                    }

                    // Map sizes to PO Items for validation
                    $sizeValidation = [];
                    foreach ($sizeTotals as $sz => $excelQty) {
                        $matchingPoItem = $poItems->first(function ($pi) use ($colorName, $sz) {
                            return strcasecmp(trim($pi->color), trim($colorName)) === 0 &&
                                   strcasecmp(trim($pi->size), trim($sz)) === 0;
                        });

                        $poItemQty = $matchingPoItem ? floatval($matchingPoItem->qty) : null;
                        $sizeValidation[$sz] = [
                            'size' => $sz,
                            'excel_qty' => $excelQty,
                            'po_qty' => $poItemQty,
                            'matched' => $matchingPoItem ? true : false,
                            'article_number' => $matchingPoItem ? $matchingPoItem->article_number : ($poItems->first()->article_number ?? '')
                        ];
                    }

                    // Find matching carton ID from dimensions
                    $firstDim = !empty($cartons[0]['dimension']) ? $cartons[0]['dimension'] : '60x40x25';
                    $matchedCarton = $this->findMatchingCarton($vendor_id, $firstDim, $allCartons);

                    $plList[$plKey] = [
                        'pl_key' => $plKey,
                        'pl_no' => $plNo,
                        'location' => $plGroup['location'] ?? '',
                        'pack_ref_no' => $packRefNo,
                        'color' => $colorName,
                        'packing_table_no' => $plGroup['packing_table_no'],
                        'job_no' => $jobNoFinal,
                        'total_cartons' => $totalCartons,
                        'total_qty' => $totalQty,
                        'total_net_weight' => round($totalNetWeight, 2),
                        'size_totals' => $sizeTotals,
                        'size_validation' => $sizeValidation,
                        'carton_id' => $matchedCarton ? $matchedCarton->id : ($defaultCarton->id ?? 1),
                        'carton_dimension' => $firstDim,
                        'cartons' => $cartons,
                        'existing_pl' => $existingPl ? [
                            'id' => $existingPl->id,
                            'pack_ref_no' => $existingPl->pack_ref_no,
                            'pack_status' => $existingPl->pack_status
                        ] : null
                    ];

                    $plSeqCounter++;
                }

                $posProcessed[] = [
                    'po_num' => $poNum,
                    'job_no' => $poJobNum,
                    'po_date' => $poDate,
                    'style_description' => $styleDesc,
                    'po_exists' => $poExists,
                    'is_amended' => $isAmended,
                    'has_existing_packing_list' => $hasExistingPl,
                    'existing_packing_lists' => $existingPlList,
                    'db_po_id' => $dbPo ? $dbPo->id : null,
                    'packing_lists' => $plList,
                    'colors' => $plList // for backward compatibility
                ];
            }

            // Summary counts
            $summaryTotalPos = count($posProcessed);
            $summaryTotalPls = 0;
            $summaryTotalCartons = 0;
            $summaryTotalQty = 0;
            $summaryTotalWeight = 0;

            foreach ($posProcessed as $poEntry) {
                foreach ($poEntry['packing_lists'] as $plEntry) {
                    $summaryTotalPls++;
                    $summaryTotalCartons += $plEntry['total_cartons'];
                    $summaryTotalQty += $plEntry['total_qty'];
                    $summaryTotalWeight += $plEntry['total_net_weight'];
                }
            }

            $summary = [
                'total_pos' => $summaryTotalPos,
                'total_pls' => $summaryTotalPls,
                'total_cartons' => $summaryTotalCartons,
                'total_qty' => $summaryTotalQty,
                'total_weight' => round($summaryTotalWeight, 2),
            ];

            $html = view('bulk_import.pl_import_response_view', [
                'pos' => $posProcessed,
                'vendor_id' => $vendor_id,
                'vendor' => $vendor,
                'allSizes' => $allSizesEncountered,
                'summary' => $summary,
            ])->render();

            return response()->json([
                'status' => true,
                'html' => $html,
                'pos' => $posProcessed,
                'summary' => $summary
            ]);

        } catch (\Exception $e) {
            return response()->json(['error' => 'An error occurred while parsing the Excel file: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Store parsed Packing Lists & Config into Database
     */
    public function pl_import_store(Request $request)
    {
        try {
            $vendor_id = $request->input('vendor_id', 1);
            $pos_data_raw = $request->input('data');

            if (empty($pos_data_raw)) {
                return response()->json(['success' => false, 'message' => 'No packing list data received for saving.']);
            }

            $pos = is_array($pos_data_raw) ? $pos_data_raw : json_decode($pos_data_raw, true);

            if (empty($pos) || !is_array($pos)) {
                return response()->json(['success' => false, 'message' => 'Invalid packing list payload format.']);
            }

            $vendor = VendorMaster::find($vendor_id);
            $allCartons = CartonMaster::where('vendor_id', $vendor_id)->whereIn('status', [0, 1])->get();
            $defaultCarton = $allCartons->first();

            $createdPackingLists = [];
            $skippedPos = [];

            DB::transaction(function () use ($pos, $vendor_id, $vendor, $allCartons, $defaultCarton, &$createdPackingLists, &$skippedPos) {
                foreach ($pos as $singlePo) {
                    $poNum = $singlePo['po_num'];

                    // Locate PO Master
                    $po = PoMaster::where('po_num', $poNum)->first();

                    if (!$po) {
                        $skippedPos[] = "PO #{$poNum} (PO has not been uploaded yet. Please upload PO first).";
                        continue;
                    }

                    // CHECK IF PO IS ALREADY AMENDED -> SKIP
                    if ($po->status == 1) {
                        $skippedPos[] = "PO #{$poNum} (PO is already amended. Skipped).";
                        continue;
                    }

                    // CHECK IF PACKING LIST ALREADY EXISTS FOR THIS PO -> SKIP
                    $alreadyHasPl = PackingListMaster::where('po_id', $po->id)->exists();
                    if ($alreadyHasPl) {
                        $skippedPos[] = "PO #{$poNum} (Packing list is already added for this PO. Skipped).";
                        continue;
                    }

                    $plEntries = $singlePo['packing_lists'] ?? ($singlePo['colors'] ?? []);
                    $firstPlEntry = reset($plEntries) ?: [];

                    // 1. AMEND PO WITH JO NUMBER
                    $jobNo = !empty($singlePo['job_no']) ? $singlePo['job_no'] : (!empty($firstPlEntry['job_no']) ? $firstPlEntry['job_no'] : null);
                    if (empty($jobNo)) {
                        $jobNo = $po->po_job_num ?: ($po->po_ref_num ?: 'JO-' . $po->po_num);
                    }

                    $jobOrder = JobOrderMaster::where('job_no', $jobNo)
                        ->where('vendor_id', $vendor_id)
                        ->first();

                    if (!$jobOrder) {
                        $jobOrder = JobOrderMaster::where('job_no', $jobNo)->first();
                    }

                    if (!$jobOrder) {
                        $poArticleInfo = json_decode($po->article_info, true) ?: [];
                        $styleName = $poArticleInfo['Article description'] ?? ($poArticleInfo['style_description'] ?? 'POLOS');

                        $jobOrder = JobOrderMaster::create([
                            'vendor_id' => $vendor_id,
                            'job_no' => $jobNo,
                            'style' => $styleName,
                            'color' => $po->colors ?? null,
                            'type' => $po->po_job_type ?? null,
                            'created_by' => auth()->id() ?? 1,
                            'created_at' => now(),
                            'status' => 1 // Status 1 = Assigned / Amended
                        ]);
                    } else {
                        $jobOrder->status = 1;
                        $jobOrder->save();
                    }

                    // Update PO Master to Amended (status = 1) with JO details
                    $po->status = 1;
                    $po->po_job_num = $jobOrder->job_no;
                    $po->po_job_id = $jobOrder->id;
                    $po->po_job_type = $jobOrder->type ?? $po->po_job_type;
                    $po->amended_at = now();
                    $po->amended_by = auth()->id() ?? 1;
                    $po->save();

                    $poItems = PoItems::where('po_id', $po->id)->get();
                    $firstPoItem = $poItems->first();
                    $cartonId = $firstPlEntry['carton_id'] ?? ($defaultCarton->id ?? 1);

                    $configMaster = PackingListConfigMaster::updateOrCreate(
                        ['po_id' => $po->id, 'vendor_id' => $vendor_id],
                        [
                            'carton_id' => $cartonId,
                            'excess' => $vendor->excess ?? 0,
                            'shortage' => $vendor->shortage ?? 0,
                            'status' => 0,
                            'created_by' => auth()->id(),
                            'created_at' => now(),
                        ]
                    );

                    // Process each Packing List entry -> Create 1 PackingListMaster per Packing List
                    $plSeq = 1;
                    foreach ($plEntries as $plData) {
                        $colorName = $plData['color'] ?? '';
                        $packingTableNo = $plData['packing_table_no'] ?? 2;
                        $jobNo = $plData['job_no'] ?? ($po->po_job_num ?: $po->po_num);
                        $plNo = !empty($plData['pl_no']) ? $plData['pl_no'] : (string)$plSeq;
                        
                        // pack_ref_no stored as {job_no}/{pl_no} e.g. JJ-3466/1, JJ-3519/1
                        $packRefNo = !empty($plData['pack_ref_no']) ? $plData['pack_ref_no'] : "{$jobNo}/{$plNo}";

                        $plMaster = PackingListMaster::create([
                            'po_id' => $po->id,
                            'pack_ref_no' => $packRefNo,
                            'vendor_id' => $vendor_id,
                            'packing_po_num' => $po->po_num,
                            'po_no' => $po->po_num,
                            'po_date' => $po->po_date,
                            'color' => $colorName,
                            'location' => $plData['location'] ?? null,
                            'packing_table_no' => $packingTableNo,
                            'pack_status' => 1, // Completed
                            'status' => 0,
                            'created_by' => auth()->id(),
                            'created_at' => now(),
                        ]);

                        // Also update / create PackingListConfigItem for each size in this Color
                        $sizeTotals = $plData['size_totals'] ?? [];
                        foreach ($sizeTotals as $szName => $szPackQty) {
                            $matchingPi = $poItems->first(function ($pi) use ($colorName, $szName) {
                                return strcasecmp(trim($pi->color), trim($colorName)) === 0 &&
                                        strcasecmp(trim($pi->size), trim($szName)) === 0;
                            });
                            $poQty = $matchingPi ? $matchingPi->qty : $szPackQty;

                            PackingListConfigItem::updateOrCreate(
                                [
                                    'config_id' => $configMaster->id,
                                    'po_id' => $po->id,
                                    'color' => $colorName,
                                    'size' => $szName
                                ],
                                [
                                    'vendor_id' => $vendor_id,
                                    'po_item_id' => $matchingPi ? $matchingPi->id : null,
                                    'po_qty' => $poQty,
                                    'pack_qty' => $szPackQty,
                                    'per_carton_qty' => 60,
                                    'weight_per_piece' => 0.25,
                                    'position' => 1,
                                    'created_by' => auth()->id(),
                                    'created_at' => now(),
                                    'status' => 0,
                                ]
                            );
                        }

                        // Insert PackingListItems for each carton
                        $cartons = $plData['cartons'] ?? [];
                        $totalItemsCount = 0;

                        foreach ($cartons as $cItem) {
                            $cName = $cItem['carton_name'] ?? ('C' . ($cItem['carton_number'] ?? 1));
                            $cNetWt = $cItem['net_weight'] ?? 0;
                            $cDim = $cItem['dimension'] ?? '60x40x25';
                            $cCarton = $this->findMatchingCarton($vendor_id, $cDim, $allCartons);
                            $cCartonId = $cCarton ? $cCarton->id : ($defaultCarton->id ?? 1);

                            foreach ($cItem['sizes'] as $szName => $szQty) {
                                if ($szQty <= 0) continue;

                                $matchingPi = $poItems->first(function ($pi) use ($colorName, $szName) {
                                    return strcasecmp(trim($pi->color), trim($colorName)) === 0 &&
                                            strcasecmp(trim($pi->size), trim($szName)) === 0;
                                });
                                $articleNo = $matchingPi ? $matchingPi->article_number : ($firstPoItem->article_number ?? '');

                                PackingListItem::create([
                                    'packing_list_id' => $plMaster->id,
                                    'vendor_id' => $vendor_id,
                                    'po_item_id' => $matchingPi ? $matchingPi->id : null,
                                    'carton_id' => $cCartonId,
                                    'carton_name' => $cName,
                                    'article_number' => $articleNo,
                                    'color' => $colorName,
                                    'size' => $szName,
                                    'quantity' => $szQty,
                                    'net_weight' => $cNetWt,
                                    'created_by' => auth()->id(),
                                    'created_at' => now(),
                                    'status' => 0,
                                ]);

                                $totalItemsCount++;
                            }
                        }

                        $createdPackingLists[] = [
                            'po_num' => $po->po_num,
                            'color' => $colorName,
                            'pack_ref_no' => $plMaster->pack_ref_no,
                            'cartons_count' => count($cartons),
                            'items_count' => $totalItemsCount,
                        ];

                        $plSeq++;
                    }
                }
            });

            if (empty($createdPackingLists) && !empty($skippedPos)) {
                return response()->json([
                    'success' => false,
                    'message' => implode('<br>', $skippedPos)
                ], 400);
            }

            return response()->json([
                'success' => true,
                'message' => 'Successfully generated ' . count($createdPackingLists) . ' packing list(s)!',
                'created' => $createdPackingLists,
                'skipped' => $skippedPos,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while saving packing lists: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Helper to find matching carton from dimension string (e.g. 60x40x25 or 60*40*25)
     */
    private function findMatchingCarton($vendor_id, $dimensionStr, $cartonsCollection)
    {
        if (preg_match('/(\d+)\s*[xX*]\s*(\d+)\s*[xX*]\s*(\d+)/', $dimensionStr, $matches)) {
            $l = intval($matches[1]);
            $b = intval($matches[2]);
            $h = intval($matches[3]);

            $matched = $cartonsCollection->first(function ($c) use ($l, $b, $h) {
                return intval($c->length) == $l && intval($c->breadth) == $b && intval($c->height) == $h;
            });

            if ($matched) {
                return $matched;
            }
        }

        return $cartonsCollection->first();
    }

    private function extractArticleDescription(?string $description): ?string
    {
        if (empty($description)) {
            return null;
        }

        $description = trim($description);
        $sizeToken = '(?:XS|S|M|L|XL|XXL|XXXL|[2-9]XL)';
        $pattern = '/\s' . $sizeToken . '(?:-' . $sizeToken . ')?(?=[\s@\[]|$)/i';

        if (preg_match($pattern, $description, $matches, PREG_OFFSET_CAPTURE)) {
            $cutAt = $matches[0][1];
            $name = trim(substr($description, 0, $cutAt));
            if ($name !== '') {
                return $name;
            }
        }

        return $description;
    }
}

