<?php

namespace App\Http\Controllers;

use App\Http\Controllers\BaseController;
use Illuminate\Http\Request;
use App\Models\VendorMaster;
use App\Models\PoMaster;
use App\Models\PoItems;
use App\Models\PrefixSetting;
use App\Models\CartonMaster;
use App\Models\SizeChartMaster;
use App\Models\JobOrderMaster;
use App\Models\JobOrderSizeMaster;
use App\Models\PackingListMaster;
use App\Models\PackingListItem;
use App\Models\PackingListConfigMaster;
use App\Models\PackingListConfigItem;
use App\Models\InvoiceMaster;
use App\Models\InvoiceHistoryMaster;
use App\Models\TransportMaster;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class BulkPoExtractController extends BaseController
{
    protected $isSuperAdmin;

    public function __construct()
    {
        parent::__construct();
        $this->isSuperAdmin = request()->attributes->get('isSuperAdmin', false);
        $this->middleware('auth');
    }

    /**
     * Render Bulk PO Import Page
     */
    public function import()
    {
        $page_data = [
            'page_title' => "Bulk Import PO",
            'page_main_title' => "Purchase Order",
            'page_child_title' => "Bulk Import",
            'isSuperAdmin' => $this->isSuperAdmin,
        ];

        $page_data['vendors'] = VendorMaster::whereIn('status', [0, 1])
            ->orderBy('id', 'asc')
            ->get();

        return view('bulk_import.bulk_import', $page_data);
    }

    /**
     * Process multiple uploaded PO PDFs and prepare previews according to full flow:
     * Vendor Master (Excess %, Shortage %, Discount %, Addresses, Cartons, Size Chart),
     * Size Chart Master Ordering, Full standalone cartons ONLY (leftover pieces kept in balance pool for Mixed Cartons),
     * Manual Mixed Carton Addition & Deletion, and Invoice Header Inputs (No, Date, GST %, Transport, Supply Date).
     */
    public function process(Request $request)
    {
        try {
            $vendor_id = $request->input('vendor_id', 1);
            $files = $request->file('pdf_files');

            if (empty($files) || !is_array($files)) {
                return response()->json(['error' => 'No PDF files were uploaded.'], 400);
            }

            // Fetch Vendor Master Settings
            $vendor = VendorMaster::find($vendor_id);
            if (!$vendor) {
                return response()->json(['error' => 'Selected vendor not found.'], 404);
            }

            $extraction_no = (string)($vendor->extraction_no ?? $vendor_id);
            $vendorExcess = floatval($vendor->excess ?? 0);
            $vendorShortage = floatval($vendor->shortage ?? 0);
            $vendorDiscount = floatval($vendor->discount ?? 0);

            // Fetch Transporters
            $transports = TransportMaster::whereIn('status', [0, 1])->orderBy('name', 'asc')->get();

            // Billing Address from Vendor Master or default
            $billingAddress = implode(', ', array_filter([
                $vendor->billing_legal_name ?? $vendor->name,
                $vendor->billing_address_1,
                $vendor->billing_address_2,
                $vendor->billing_city_town_village,
                $vendor->billing_pincode ? ('PIN: ' . $vendor->billing_pincode) : null,
                $vendor->billing_gst_no ? ('GSTIN: ' . $vendor->billing_gst_no) : null,
            ]));

            // Shipping Address from Vendor Master or default
            $shippingAddress = implode(', ', array_filter([
                $vendor->shipping_legal_name ?? $vendor->name,
                $vendor->shipping_address_1,
                $vendor->shipping_address_2,
                $vendor->shipping_city_town_village,
                $vendor->shipping_pincode ? ('PIN: ' . $vendor->shipping_pincode) : null,
                $vendor->shipping_gst_no ? ('GSTIN: ' . $vendor->shipping_gst_no) : null,
            ]));

            // Carton Master Records for Vendor
            $allCartons = CartonMaster::where('vendor_id', $vendor_id)->whereIn('status', [0, 1])->get();
            if ($allCartons->isEmpty()) {
                $allCartons = collect([(object)[
                    'id' => null,
                    'vendor_id' => $vendor_id,
                    'length' => 60,
                    'breadth' => 40,
                    'height' => 40,
                    'weight' => 1.2
                ]]);
            }

            $firstCarton = $allCartons->first();
            $cartonLength = $firstCarton->length ?? 60;
            $cartonBreadth = $firstCarton->breadth ?? 40;
            $cartonHeight = $firstCarton->height ?? 40;
            $cartonWeight = $firstCarton->weight ?? 1.2;
            $cartonId = $firstCarton->id ?? null;

            // Size Chart Master ordering for vendor
            $sizeChartMaster = SizeChartMaster::where('vendor_id', $vendor_id)
                ->whereIn('status', [0, 1])
                ->orderBy('id', 'asc')
                ->get();
            
            $sizeOrderMap = [];
            foreach ($sizeChartMaster as $sIdx => $sc) {
                $cleanSz = strtoupper(preg_replace('/\s+/', '', $sc->size));
                $sizeOrderMap[$cleanSz] = $sIdx;
            }

            $extracted_pos = [];

            foreach ($files as $index => $file) {
                $originalName = $file->getClientOriginalName();
                $pdfBase64 = base64_encode(file_get_contents($file->getRealPath()));

                // Call Python extraction microservice
                $response = Http::timeout(60)->post('http://localhost:8000/process', [
                    'extraction_no' => $extraction_no,
                    'pdf_base64' => $pdfBase64,
                ]);

                if (!$response->successful()) {
                    return response()->json(['error' => "Error processing PDF '{$originalName}'. Make sure PDF extraction service is running on port 8000."], 500);
                }

                $res_data = $response->json();
                $data = $res_data['data'] ?? [];

                $po_details = $data['po_details'] ?? [];
                $article_info = $data['article_info'] ?? [];
                $po_items = $data['po_items'] ?? [];

                // Address fallbacks from PO if vendor master addresses are incomplete
                if (empty($billingAddress)) {
                    $billingAddress = $po_details['Communication Address'] ?? '';
                }
                if (empty($shippingAddress)) {
                    $shippingAddress = $po_details['Delivery Address'] ?? '';
                }

                // Helper to parse numeric float from strings like "VCP to be 199.50" or "Rs. 199.50"
                // Parse VCP Rate first (use VCP rate for all calculations if present, else fallback to Price per unit)
                $vcpRaw = $po_details['VCP'] ?? ($po_details['vcp'] ?? null);
                $vcpRate = $this->parseNumericAmount($vcpRaw);
                $price_str = $article_info['Price per unit'] ?? '0';
                $fallbackPrice = $this->parseNumericAmount($price_str);

                $per_unit_price = ($vcpRate > 0) ? $vcpRate : $fallbackPrice;

                // Total PO Quantity & Values
                $total_qty = 0;
                foreach ($po_items as $item) {
                    preg_match('/[\d,]+/', $item['quatity_uom'] ?? '0', $matches);
                    $total_qty += floatval(str_replace(',', '', $matches[0] ?? '0'));
                }

                $total_value = $per_unit_price * $total_qty;
                $tax_rate = 0.05; // 5% IGST
                $tax_amount = $total_value * $tax_rate;
                $final_total = $total_value + $tax_amount;

                // Group PO items by Color
                $colorGroups = [];
                foreach ($po_items as $item) {
                    $idColorField = $item['artcicle_id_color'] ?? '';
                    if (strpos($idColorField, '/') !== false) {
                        $colorParts = explode('/', $idColorField, 2);
                        $cName = trim($colorParts[1]);
                    } else {
                        $cName = trim($idColorField);
                    }
                    if (!$cName) {
                        $cName = $po_details['Colors'] ?? 'DEFAULT';
                    }
                    $colorGroups[$cName][] = $item;
                }

                $packing_lists_by_color = [];
                $invoices_by_color = [];
                $po_num = $po_details['PO Number'] ?? ('PO-' . ($index + 1));
                $userJobNo = '';
                $plSeq = 1;

                foreach ($colorGroups as $colorName => $cItems) {
                    $cartonCounter = 1;
                    $cartonList = [];

                    $sizeQuantities = [];
                    $sizePackQuantities = [];
                    $sizeDetailedItems = [];
                    $color_total_pack_qty = 0;

                    // STRICT SORTING BY size_chart_master ORDER
                    usort($cItems, function($a, $b) use ($sizeOrderMap) {
                        $szA = strtoupper(preg_replace('/\s+/', '', $a['size_years'] ?? ''));
                        $szB = strtoupper(preg_replace('/\s+/', '', $b['size_years'] ?? ''));
                        $posA = $sizeOrderMap[$szA] ?? 999;
                        $posB = $sizeOrderMap[$szB] ?? 999;
                        return $posA <=> $posB;
                    });

                    // Process Standalone Cartons in Size Chart Master Order
                    // (Full capacity cartons ONLY; remainders left for Mixed Cartons)
                    foreach ($cItems as $ci) {
                        preg_match('/[\d,]+/', $ci['quatity_uom'] ?? '0', $m);
                        $qOrder = floatval(str_replace(',', '', $m[0] ?? '0'));
                        $sz = $ci['size_years'] ?? 'OS';
                        $cleanSz = strtoupper(preg_replace('/\s+/', '', $sz));
                        $art = $ci['article_number'] ?? '';
                        $ean = $ci['ean_code'] ?? '';

                        // EXCESS % CAP: Pack Qty = floor(Order Qty * (1 + excess%/100))
                        $maxPackQtyCap = floor($qOrder * (1 + ($vendorExcess / 100)));
                        $qPack = $maxPackQtyCap;
                        $color_total_pack_qty += $qPack;

                        if (!isset($sizeQuantities[$sz])) {
                            $sizeQuantities[$sz] = 0;
                            $sizePackQuantities[$sz] = 0;
                        }
                        $sizeQuantities[$sz] += $qOrder;
                        $sizePackQuantities[$sz] += $qPack;

                        if (!isset($sizeDetailedItems[$sz])) {
                            $sizeDetailedItems[$sz] = [
                                'size' => $sz,
                                'article_number' => $art,
                                'ean_code' => $ean,
                                'order_qty' => 0,
                                'pack_qty' => 0,
                                'article_description' => $article_info['Article description'] ?? '',
                            ];
                        }
                        $sizeDetailedItems[$sz]['order_qty'] += $qOrder;
                        $sizeDetailedItems[$sz]['pack_qty'] += $qPack;

                        // Per Carton Capacity: 50 for 9/10Y, 11/12Y, 13/14Y, XL, XXL, XXXL; 60 for others
                        $cap = in_array($cleanSz, ['9/10Y', '11/12Y', '13/14Y', 'XL', 'XXL', 'XXXL']) ? 50 : 60;

                        if ($qPack <= $cap) {
                            // Single standalone carton for entire size quantity
                            $cNameTag = 'C' . $cartonCounter;
                            $netWeight = round($qPack * 0.25, 2);
                            $grossWeight = round($netWeight + $cartonWeight, 2);
                            $cbm = round(($cartonLength * $cartonBreadth * $cartonHeight) / 1e6, 4);

                            $cartonList[] = [
                                'carton_name' => $cNameTag,
                                'po_no' => $po_num,
                                'article_number' => $art,
                                'article_description' => $article_info['Article description'] ?? '',
                                'ean_code' => $ean,
                                'color' => $colorName,
                                'size' => $sz,
                                'quantity' => $qPack,
                                'net_weight' => $netWeight,
                                'gross_weight' => $grossWeight,
                                'cbm' => $cbm,
                                'carton_length' => $cartonLength,
                                'carton_breadth' => $cartonBreadth,
                                'carton_height' => $cartonHeight,
                                'carton_id' => $cartonId,
                                'is_mixed' => false,
                            ];
                            $cartonCounter++;
                        } else {
                            // Full cartons of $cap pcs ONLY (remainders left in balance pool for mixed cartons)
                            $fullCount = intval($qPack / $cap);

                            for ($fc = 0; $fc < $fullCount; $fc++) {
                                $cNameTag = 'C' . $cartonCounter;
                                $netWeight = round($cap * 0.25, 2);
                                $grossWeight = round($netWeight + $cartonWeight, 2);
                                $cbm = round(($cartonLength * $cartonBreadth * $cartonHeight) / 1e6, 4);

                                $cartonList[] = [
                                    'carton_name' => $cNameTag,
                                    'po_no' => $po_num,
                                    'article_number' => $art,
                                    'article_description' => $article_info['Article description'] ?? '',
                                    'ean_code' => $ean,
                                    'color' => $colorName,
                                    'size' => $sz,
                                    'quantity' => $cap,
                                    'net_weight' => $netWeight,
                                    'gross_weight' => $grossWeight,
                                    'cbm' => $cbm,
                                    'carton_length' => $cartonLength,
                                    'carton_breadth' => $cartonBreadth,
                                    'carton_height' => $cartonHeight,
                                    'carton_id' => $cartonId,
                                    'is_mixed' => false,
                                ];
                                $cartonCounter++;
                            }
                        }
                    }

                    // Total Cartons Count = Number of unique carton names
                    $totalCartonsForColor = count(array_unique(array_column($cartonList, 'carton_name')));
                    $packRefNo = !empty($userJobNo) ? ($userJobNo . '/' . $plSeq) : ((string)$plSeq);
                    $plSeq++;

                    $packing_lists_by_color[$colorName] = [
                        'color' => $colorName,
                        'pack_ref_no' => $packRefNo,
                        'po_no' => $po_num,
                        'po_date' => $po_details['PO Date'] ?? '',
                        'total_cartons' => $totalCartonsForColor,
                        'cartons' => $cartonList,
                        'size_summary' => $sizeQuantities,
                        'size_pack_summary' => $sizePackQuantities,
                    ];

                    // ----------------------------------------------------
                    // Build Tax Invoice Data for THIS Packing List (Color)
                    // EACH SIZE A SEPARATE ROW, ORDER NO OMITTED
                    // ----------------------------------------------------
                    $invoice_lines = [];
                    $colorTaxableTotal = 0;
                    $colorDiscountTotal = 0;
                    $colorIgstTotal = 0;
                    $colorFinalGrandTotal = 0;

                    $sno = 1;
                    foreach ($sizeDetailedItems as $sz => $sInfo) {
                        $lineQty = $sInfo['pack_qty'];
                        $grossLineAmount = $lineQty * $per_unit_price;
                        $lineDiscountAmount = $grossLineAmount * ($vendorDiscount / 100);
                        $lineTaxable = $grossLineAmount - $lineDiscountAmount;
                        $lineIgst = $lineTaxable * $tax_rate;
                        $lineTotal = $lineTaxable + $lineIgst;

                        $colorDiscountTotal += $lineDiscountAmount;
                        $colorTaxableTotal += $lineTaxable;
                        $colorIgstTotal += $lineIgst;
                        $colorFinalGrandTotal += $lineTotal;

                        $styleName = $article_info['Article description'] ?? 'JJOR WINDSOR POLO SS';
                        $goodsDesc = $styleName . ', ' . $sz;

                        $lineHsnCode = $this->extractHsnCode($article_info, $cItems, $po_details);

                        $invoice_lines[] = [
                            'sno' => $sno++,
                            'description' => $goodsDesc,
                            'hsn_code' => $lineHsnCode,
                            'style_no' => $styleName,
                            'color' => $colorName,
                            'unit' => 'PCS',
                            'qty' => $lineQty,
                            'rate' => $per_unit_price,
                            'amount' => $grossLineAmount,
                            'discount_percent' => $vendorDiscount,
                            'discount' => $lineDiscountAmount,
                            'taxable_value' => $lineTaxable,
                            'igst_rate' => 5.00,
                            'igst_amount' => $lineIgst,
                            'total_line_amount' => $lineTotal,
                        ];
                    }

                    $cleanColorTag = preg_replace('/[^A-Za-z0-9]/', '', $colorName);
                    $colorInvoiceRef = 'CCPL' . date('y') . str_pad($index + 1, 4, '0', STR_PAD_LEFT) . '/' . date('y') . '-' . (date('y') + 1);

                    $firstTransportId = $transports->first()->id ?? null;

                    $invoices_by_color[$colorName] = [
                        'color' => $colorName,
                        'ref_no' => $colorInvoiceRef,
                        'inv_date' => date('Y-m-d'),
                        'supply_date' => date('Y-m-d'),
                        'transport_id' => $firstTransportId,
                        'mode_of_transport' => 'By Road',
                        'seller' => [
                            'name' => 'CARNATION CREATIONS PRIVATE LIMITED',
                            'address' => '376/1, NARASIMHANAICKEN PALAYAM VILLAGE, COIMBATORE, TAMILNADU, INDIA. 641031',
                            'gstin' => '33AAHCC1371N1ZL',
                            'pan' => 'AAHCC1371N',
                            'state_code' => '33',
                            'udyam' => 'UDYAM-TN-03-0004047',
                        ],
                        'buyer' => [
                            'name' => $vendor->billing_legal_name ?? 'BEST UNITED INDIA COMFORTS PVT LTD',
                            'address' => $billingAddress,
                            'gstin' => $vendor->billing_gst_no ?? ($po_details['GSTIN'] ?? '27AABCU0772F1ZG'),
                            'state_code' => $vendor->billing_state_id ?? '27',
                        ],
                        'consignee' => [
                            'name' => $vendor->shipping_legal_name ?? 'BEST UNITED INDIA COMFORTS PVT LTD',
                            'address' => $shippingAddress,
                            'gstin' => $vendor->shipping_gst_no ?? ($po_details['GSTIN'] ?? '27AABCU0772F1ZG'),
                            'state_code' => $vendor->shipping_state_id ?? '27',
                            'place_of_supply' => $vendor->shipping_place_supply ?? 'BHIWANDI',
                        ],
                        'po_number' => $po_num,
                        'total_cartons' => $totalCartonsForColor,
                        'invoice_lines' => $invoice_lines,
                        'total_qty' => $color_total_pack_qty,
                        'gross_amount' => $colorTaxableTotal + $colorDiscountTotal,
                        'total_discount' => $colorDiscountTotal,
                        'taxable_value' => $colorTaxableTotal,
                        'igst_rate' => 5.00,
                        'igst_amount' => $colorIgstTotal,
                        'final_total' => $colorFinalGrandTotal,
                        'amount_in_words' => $this->convertNumberToWords($colorFinalGrandTotal),
                    ];
                }

                // Existing PO Duplicate Check
                $po_num_check = $po_details['PO Number'] ?? '';
                $existingPo = null;
                $hasPackingListOrInvoice = false;
                $existingPoStatusMsg = null;

                if (!empty($po_num_check)) {
                    $existingPo = PoMaster::where('po_num', $po_num_check)->first();
                    if ($existingPo) {
                        $hasPL = PackingListMaster::where('po_id', $existingPo->id)->exists();
                        $hasInv = InvoiceMaster::where('po_id', $existingPo->id)->exists();

                        if ($hasPL || $hasInv) {
                            $hasPackingListOrInvoice = true;
                            $existingPoStatusMsg = "PO #{$po_num_check} is already uploaded and has a Packing List / Invoice created or in progress. This PO cannot be saved again.";
                        } else {
                            $existingPoStatusMsg = "PO #{$po_num_check} was previously uploaded without a Packing List or Invoice. Saving will update this PO with the new Packing List & Invoice.";
                        }
                    }
                }

                $extracted_pos[] = [
                    'po_key' => 'po_' . $index,
                    'original_filename' => $originalName,
                    'pdf_base64' => $pdfBase64,
                    'job_no' => $userJobNo,
                    'po_details' => $po_details,
                    'article_info' => $article_info,
                    'po_items' => $po_items,
                    'vendor_excess' => $vendorExcess,
                    'vendor_shortage' => $vendorShortage,
                    'vendor_discount' => $vendorDiscount,
                    'per_unit_price' => $per_unit_price,
                    'total_qty' => $total_qty,
                    'total_value' => $total_value,
                    'tax_amount' => $tax_amount,
                    'final_total' => $final_total,
                    'packing_lists' => $packing_lists_by_color,
                    'invoices_by_color' => $invoices_by_color,
                    'size_order_map' => $sizeOrderMap,
                    'existing_po_status' => [
                        'exists' => $existingPo ? true : false,
                        'blocked' => $hasPackingListOrInvoice,
                        'message' => $existingPoStatusMsg,
                    ],
                ];
            }

            $html = view('bulk_import.bulk_response_view', [
                'pos' => $extracted_pos, 
                'vendor_id' => $vendor_id, 
                'vendor' => $vendor,
                'allCartons' => $allCartons,
                'transports' => $transports
            ])->render();

            return response()->json(['status' => true, 'html' => $html, 'pos' => $extracted_pos]);
        } catch (\Exception $e) {
            return response()->json(['error' => 'An error occurred while processing bulk PDFs: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Store verified bulk POs, Job Orders, Packing Lists, Configs, and Invoices into Database
     */
    public function store(Request $request)
    {
        try {
            $vendor_id = $request->input('vendor_id', 1);
            $pos_data_json = $request->input('pos_data');

            if (empty($pos_data_json)) {
                return response()->json(['success' => false, 'message' => 'No PO data provided for saving.']);
            }

            $pos = is_array($pos_data_json) ? $pos_data_json : json_decode($pos_data_json, true);

            if (empty($pos) || !is_array($pos)) {
                return response()->json(['success' => false, 'message' => 'Invalid PO payload.']);
            }

            $savedPoNumbers = [];
            $updatedPoNumbers = [];
            $skippedPoNumbers = [];

            DB::transaction(function () use ($pos, $vendor_id, &$savedPoNumbers, &$updatedPoNumbers, &$skippedPoNumbers) {
                $prefixSetting = PrefixSetting::where('id', 1)->first();

                if (!$prefixSetting) {
                    throw new \Exception('PO prefix setting not found');
                }

                // Fetch Vendor Master for Billing & Shipping Addresses
                $vendor = VendorMaster::find($vendor_id);
                $vendorBillingAddress = $vendor ? implode(', ', array_filter([
                    $vendor->billing_legal_name ?? $vendor->name,
                    $vendor->billing_address_1,
                    $vendor->billing_address_2,
                    $vendor->billing_city_town_village,
                    $vendor->billing_pincode ? ('PIN: ' . $vendor->billing_pincode) : null,
                    $vendor->billing_gst_no ? ('GSTIN: ' . $vendor->billing_gst_no) : null,
                ])) : null;

                $vendorShippingAddress = $vendor ? implode(', ', array_filter([
                    $vendor->shipping_legal_name ?? $vendor->name,
                    $vendor->shipping_address_1,
                    $vendor->shipping_address_2,
                    $vendor->shipping_city_town_village,
                    $vendor->shipping_pincode ? ('PIN: ' . $vendor->shipping_pincode) : null,
                    $vendor->shipping_gst_no ? ('GSTIN: ' . $vendor->shipping_gst_no) : null,
                ])) : null;

                foreach ($pos as $singlePo) {
                    $po_details = $singlePo['po_details'] ?? [];
                    $extractedPoNum = $po_details['PO Number'] ?? '';

                    // CHECK IF PO ALREADY EXISTS
                    if (!empty($extractedPoNum)) {
                        $existingPo = PoMaster::where('po_num', $extractedPoNum)->first();
                        if ($existingPo) {
                            $hasPL = PackingListMaster::where('po_id', $existingPo->id)->exists();
                            $hasInv = InvoiceMaster::where('po_id', $existingPo->id)->exists();

                            if ($hasPL || $hasInv) {
                                // DO NOT SAVE! Skip this PO
                                $skippedPoNumbers[] = "PO #{$extractedPoNum} (Packing list or Invoice already created/in progress)";
                                continue;
                            } else {
                                // PO alone was uploaded previously without PL or Invoice
                                // Clean up old records before saving new configured PO with PL & Invoice
                                $existingJobId = $existingPo->po_job_id;

                                PoItems::where('po_id', $existingPo->id)->delete();
                                PackingListConfigItem::where('po_id', $existingPo->id)->delete();
                                PackingListConfigMaster::where('po_id', $existingPo->id)->delete();

                                if ($existingJobId) {
                                    JobOrderSizeMaster::where('job_id', $existingJobId)->delete();
                                    JobOrderMaster::where('id', $existingJobId)->delete();
                                }

                                PoMaster::where('id', $existingPo->id)->delete();
                                $updatedPoNumbers[] = "PO #{$extractedPoNum}";
                            }
                        }
                    }

                    $pdfFileName = null;
                    if (!empty($singlePo['pdf_base64'])) {
                        $randomName = Str::random(40) . '.pdf';
                        Storage::put('public/po/' . $randomName, base64_decode($singlePo['pdf_base64']));
                        $pdfFileName = $randomName;
                    }

                    $article_info = $singlePo['article_info'] ?? [];
                    $po_items = $singlePo['po_items'] ?? [];
                    $packing_lists = $singlePo['packing_lists'] ?? [];
                    $invoices_by_color = $singlePo['invoices_by_color'] ?? [];

                    $currentNumber = $prefixSetting->number;
                    $poNo = $prefixSetting->format . str_pad($currentNumber, 5, '0', STR_PAD_LEFT);

                    // Determine Job Order Type from Size Chart Master for the first size
                    $sizeChartRows = SizeChartMaster::where('vendor_id', $vendor_id)->whereIn('status', [0, 1])->get();
                    $jobType = $article_info['Article group'] ?? 'POLOS';

                    if (!empty($po_items)) {
                        $firstSizeName = strtoupper(trim($po_items[0]['size_years'] ?? ''));
                        $firstSc = $sizeChartRows->first(function($scRow) use ($firstSizeName) {
                            return strtoupper(trim($scRow->size)) === $firstSizeName;
                        });
                        if ($firstSc && !empty($firstSc->type)) {
                            $jobType = $firstSc->type;
                        }
                    }

                    $userJobNo = !empty($singlePo['job_no']) ? $singlePo['job_no'] : $poNo;

                    // 1. Create Job Order Master & Sizes
                    $jobOrder = JobOrderMaster::create([
                        'vendor_id' => $vendor_id,
                        'job_no' => $userJobNo,
                        'style' => $article_info['Article description'] ?? 'POLOS',
                        'color' => $po_details['Colors'] ?? null,
                        'type' => $jobType,
                        'created_by' => auth()->id(),
                        'created_at' => now(),
                        'status' => 1 // Status 1 = Assigned / Amended
                    ]);

                    // Map size charts for Job Order Sizes
                    foreach ($po_items as $pItem) {
                        $szName = strtoupper(trim($pItem['size_years'] ?? ''));
                        preg_match('/[\d,]+/', $pItem['quatity_uom'] ?? '0', $m);
                        $szQty = floatval(str_replace(',', '', $m[0] ?? '0'));
                        
                        $matchingSc = $sizeChartRows->first(function($scRow) use ($szName) {
                            return strtoupper(trim($scRow->size)) === $szName;
                        });

                        if ($matchingSc) {
                            JobOrderSizeMaster::create([
                                'job_id' => $jobOrder->id,
                                'size_id' => $matchingSc->id,
                                'qty' => $szQty,
                                'created_by' => auth()->id(),
                                'created_at' => now(),
                                'status' => 0
                            ]);
                        }
                    }

                    // 2. Create PO Master (Amended status = 1, linked to Job Order)
                    $pomaster = PoMaster::create([
                        'vendor_id' => $vendor_id,
                        'po_ref_num' => $poNo,
                        'po_job_num' => $jobOrder->job_no,
                        'po_job_id' => $jobOrder->id,
                        'po_job_type' => $jobOrder->type,
                        'po_num' => $po_details['PO Number'] ?? '',
                        'po_date' => $po_details['PO Date'] ?? null,
                        'goods_ready_date' => $po_details['Goods Ready Date'] ?? null,
                        'mrp' => $po_details['MRP'] ?? null,
                        'vcp' => $po_details['VCP'] ?? null,
                        'colors' => $po_details['Colors'] ?? null,
                        'vendor_del_adr' => !empty($vendorShippingAddress) ? $vendorShippingAddress : ($po_details['Delivery Address'] ?? null),
                        'vendor_com_adr' => !empty($vendorBillingAddress) ? $vendorBillingAddress : ($po_details['Communication Address'] ?? null),
                        'vendor_gst' => $po_details['GSTIN'] ?? null,
                        'vendor_cin' => $po_details['CIN'] ?? null,
                        'article_info' => json_encode($article_info),
                        'po_unit_price' => $singlePo['per_unit_price'] ?? 0,
                        'po_qty' => $singlePo['total_qty'] ?? 0,
                        'pdf_file' => $pdfFileName,
                        'created_by' => auth()->id(),
                        'created_at' => now(),
                        'amended_at' => $jobOrder->created_at,
                        'amended_by' => $jobOrder->created_by,
                        'status' => 1, // Amended status
                    ]);

                    $prefixSetting->number = $currentNumber + 1;
                    $prefixSetting->save();

                    // 3. Create PO Items
                    foreach ($po_items as $po_item) {
                        $quantityUom = $po_item['quatity_uom'] ?? '0';
                        preg_match('/^([\d,]+)\s*(.*)$/', $quantityUom, $matches);
                        $qty = isset($matches[1]) ? (int)str_replace(',', '', $matches[1]) : 0;
                        $uom = isset($matches[2]) ? trim($matches[2]) : null;

                        $idColorField = $po_item['artcicle_id_color'] ?? '';
                        if (strpos($idColorField, '/') !== false) {
                            $colorParts = explode('/', $idColorField, 2);
                            $colorId = trim($colorParts[0]);
                            $colorName = trim($colorParts[1]);
                        } else {
                            $colorId = null;
                            $colorName = trim($idColorField);
                        }

                        PoItems::create([
                            'po_id' => $pomaster->id,
                            'sno' => $po_item['item_sno'] ?? null,
                            'article_number' => $po_item['article_number'] ?? null,
                            'id_color' => $colorId,
                            'color' => $colorName,
                            'size' => $po_item['size_years'] ?? null,
                            'qty' => $qty,
                            'uom' => $uom,
                            'hsn_code' => $this->extractHsnCode($article_info, [$po_item], $po_details),
                            'unit_price' => $singlePo['per_unit_price'] ?? 0,
                            'total_amount' => $qty * ($singlePo['per_unit_price'] ?? 0),
                            'style_description' => $article_info['Article description'] ?? null,
                            'gender' => $article_info['Gender'] ?? null,
                            'type' => $article_info['Article group'] ?? null,
                            'content' => $article_info['Fabric composition'] ?? null,
                            'igst_taxable_value' => $po_item['igst_taxable_value'] ?? 0,
                            'igst_per' => $po_item['igst_percentage'] ?? 5,
                            'mrp' => $po_item['mrp'] ?? null,
                            'ean_code' => $po_item['ean_code'] ?? null,
                            'created_by' => auth()->id(),
                            'created_at' => now(),
                        ]);
                    }

                    $savedPoItems = PoItems::where('po_id', $pomaster->id)->get();

                    // 4. Create Packing List Config Master & Items
                    $firstCartonId = CartonMaster::where('vendor_id', $vendor_id)->value('id');
                    $configMaster = PackingListConfigMaster::create([
                        'po_id' => $pomaster->id,
                        'vendor_id' => $vendor_id,
                        'carton_id' => $singlePo['selected_carton_id'] ?? $firstCartonId,
                        'excess' => $singlePo['vendor_excess'] ?? 0,
                        'status' => 0,
                        'created_by' => auth()->id(),
                        'created_at' => now(),
                    ]);

                    foreach ($savedPoItems as $savedPi) {
                        $cleanSz = strtoupper(preg_replace('/\s+/', '', $savedPi->size));
                        $cap = in_array($cleanSz, ['9/10Y', '11/12Y', '13/14Y', 'XL', 'XXL', 'XXXL']) ? 50 : 60;
                        $packQty = floor($savedPi->qty * (1 + (($singlePo['vendor_excess'] ?? 0) / 100)));

                        PackingListConfigItem::create([
                            'config_id' => $configMaster->id,
                            'po_id' => $pomaster->id,
                            'vendor_id' => $vendor_id,
                            'po_item_id' => $savedPi->id,
                            'color' => $savedPi->color,
                            'size' => $savedPi->size,
                            'po_qty' => $savedPi->qty,
                            'pack_qty' => $packQty,
                            'per_carton_qty' => $cap,
                            'weight_per_piece' => 0.25,
                            'position' => 1,
                            'created_by' => auth()->id(),
                            'created_at' => now(),
                            'status' => 0,
                        ]);
                    }

                    // 5. Create Packing List & Invoice for EACH color packing list (marked completed)
                    foreach ($packing_lists as $colorName => $pData) {
                        $packRefNo = 'PL-AUTO-' . $pomaster->po_job_num . '/' . $colorName;

                        $plMaster = PackingListMaster::create([
                            'po_id' => $pomaster->id,
                            'pack_ref_no' => $packRefNo,
                            'vendor_id' => $vendor_id,
                            'po_no' => $pomaster->po_num,
                            'po_date' => $pomaster->po_date,
                            'color' => $colorName,
                            'status' => 0,
                            'pack_status' => 1, // Completed
                            'created_at' => now(),
                            'created_by' => auth()->id(),
                        ]);

                        $cartons = $pData['cartons'] ?? [];
                        foreach ($cartons as $cItem) {
                            $matchingPoItem = $savedPoItems->where('color', $colorName)->where('size', $cItem['size'])->first();

                            PackingListItem::create([
                                'packing_list_id' => $plMaster->id,
                                'vendor_id' => $vendor_id,
                                'po_item_id' => $matchingPoItem ? $matchingPoItem->id : null,
                                'carton_id' => $cItem['carton_id'] ?? null,
                                'carton_name' => $cItem['carton_name'] ?? 'C1',
                                'article_number' => $cItem['article_number'] ?? '',
                                'color' => $colorName,
                                'size' => $cItem['size'] ?? '',
                                'quantity' => $cItem['quantity'] ?? 0,
                                'net_weight' => $cItem['net_weight'] ?? 0,
                                'created_at' => now(),
                                'created_by' => auth()->id(),
                                'status' => 0,
                            ]);
                        }

                        // Create Invoice Master for THIS Packing List
                        $colorInvData = $invoices_by_color[$colorName] ?? null;
                        $invoiceRefNo = $colorInvData['ref_no'] ?? ('INV-' . $pomaster->po_num . '-' . preg_replace('/[^A-Za-z0-9]/', '', $colorName));

                        $existingInvCount = InvoiceMaster::where('ref_no', $invoiceRefNo)->count();
                        if ($existingInvCount > 0) {
                            $invoiceRefNo .= '-' . rand(10, 99);
                        }

                        $invDate = !empty($colorInvData['inv_date']) ? date('Y-m-d', strtotime($colorInvData['inv_date'])) : date('Y-m-d');
                        $supplyDate = !empty($colorInvData['supply_date']) ? date('Y-m-d', strtotime($colorInvData['supply_date'])) : date('Y-m-d');

                        // Vendor Billing Address JSON
                        $billToDetailsJson = json_encode([
                            'billed_legal_name' => $vendor->billing_legal_name ?? ($vendor->name ?? ''),
                            'billed_address_1' => $vendor->billing_address_1 ?? '',
                            'billed_address_2' => $vendor->billing_address_2 ?? '',
                            'billed_city' => $vendor->billing_city_town_village ?? '',
                            'billed_state' => $vendor->billing_state_id ?? '',
                            'billed_gst_no' => $vendor->billing_gst_no ?? ($po_details['GSTIN'] ?? ''),
                            'billed_pan_no' => $vendor->billing_pan_no ?? '',
                            'billed_pincode' => $vendor->billing_pincode ?? '',
                            'billed_gst_type' => $vendor->billing_gst_type ?? '',
                        ]);

                        // Vendor Shipping Address JSON
                        $shipToDetailsJson = json_encode([
                            'shipped_legal_name' => $vendor->shipping_legal_name ?? ($vendor->name ?? ''),
                            'shipped_address_1' => $vendor->shipping_address_1 ?? '',
                            'shipped_address_2' => $vendor->shipping_address_2 ?? '',
                            'shipped_city' => $vendor->shipping_city_town_village ?? '',
                            'shipped_state' => $vendor->shipping_state_id ?? '',
                            'shipped_gst_no' => $vendor->shipping_gst_no ?? ($po_details['GSTIN'] ?? ''),
                            'shipped_pan_no' => $vendor->shipping_pan_no ?? '',
                            'shipped_pincode' => $vendor->shipping_pincode ?? '',
                            'shipped_place_of_supply' => $vendor->shipping_place_supply ?? 'BHIWANDI',
                        ]);

                        // Transporter Details & Supply Date JSON
                        $transporterDetailsJson = json_encode([
                            'transport_name' => $colorInvData['transport_id'] ?? null,
                            'mode_of_transport' => $colorInvData['mode_of_transport'] ?? 'By Road',
                            'transport_doc_no' => null,
                            'transport_date_time' => $supplyDate,
                            'supply_date' => $supplyDate,
                            'transport_vehicle_no' => null,
                            'transport_distance' => null,
                        ]);

                        $invoiceMaster = InvoiceMaster::create([
                            'ref_no' => $invoiceRefNo,
                            'inv_date' => $invDate,
                            'gst' => floatval($colorInvData['igst_rate'] ?? 5.00),
                            'bill_to_details' => $billToDetailsJson,
                            'ship_to_details' => $shipToDetailsJson,
                            'po_id' => $pomaster->id,
                            'pack_ids' => (string)$plMaster->id,
                            'transporter_details' => $transporterDetailsJson,
                            'vendor_id' => $vendor_id,
                            'invoice_status_id' => 1,
                            'created_by' => auth()->id(),
                            'created_at' => now(),
                        ]);

                        InvoiceHistoryMaster::create([
                            'invoice_id' => $invoiceMaster->id,
                            'invoice_status_id' => 1,
                            'remarks' => 'Generated via Bulk PO Import for Packing List ' . $plMaster->pack_ref_no,
                            'created_by' => auth()->id(),
                            'created_at' => now(),
                        ]);
                    }

                    $savedPoNumbers[] = $pomaster->po_num;
                }
            });

            $msgParts = [];
            if (count($savedPoNumbers) > 0) {
                $msgParts[] = count($savedPoNumbers) . ' PO(s) saved: ' . implode(', ', $savedPoNumbers);
            }
            if (count($updatedPoNumbers) > 0) {
                $msgParts[] = count($updatedPoNumbers) . ' standalone PO(s) updated with PL & Invoice: ' . implode(', ', $updatedPoNumbers);
            }
            if (count($skippedPoNumbers) > 0) {
                $msgParts[] = count($skippedPoNumbers) . ' PO(s) skipped (PL / Invoice already in progress): ' . implode(', ', $skippedPoNumbers);
            }

            return response()->json([
                'success' => true,
                'message' => implode(' | ', $msgParts),
                'po_numbers' => $savedPoNumbers,
                'updated_pos' => $updatedPoNumbers,
                'skipped_pos' => $skippedPoNumbers,
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Error saving bulk POs: ' . $e->getMessage()]);
        }
    }

    /**
     * Helper to convert number to words for Invoice Total
     */
    private function convertNumberToWords($amount)
    {
        $number = floor($amount);
        $fraction = round(($amount - $number) * 100);

        $words = [
            0 => '', 1 => 'ONE', 2 => 'TWO', 3 => 'THREE', 4 => 'FOUR',
            5 => 'FIVE', 6 => 'SIX', 7 => 'SEVEN', 8 => 'EIGHT', 9 => 'NINE',
            10 => 'TEN', 11 => 'ELEVEN', 12 => 'TWELVE', 13 => 'THIRTEEN',
            14 => 'FOURTEEN', 15 => 'FIFTEEN', 16 => 'SIXTEEN', 17 => 'SEVENTEEN',
            18 => 'EIGHTEEN', 19 => 'NINETEEN', 20 => 'TWENTY', 30 => 'THIRTY',
            40 => 'FORTY', 50 => 'FIFTY', 60 => 'SIXTY', 70 => 'SEVENTY',
            80 => 'EIGHTY', 90 => 'NINETY'
        ];

        if ($number == 0) {
            return 'ZERO RUPEES';
        }

        $str = [];
        $crore = floor($number / 10000000);
        $number %= 10000000;

        $lakh = floor($number / 100000);
        $number %= 100000;

        $thousand = floor($number / 1000);
        $number %= 1000;

        $hundred = floor($number / 100);
        $number %= 100;

        if ($crore) {
            $str[] = $this->convertTwoDigit($crore, $words) . ' CRORE';
        }
        if ($lakh) {
            $str[] = $this->convertTwoDigit($lakh, $words) . ' LAKH';
        }
        if ($thousand) {
            $str[] = $this->convertTwoDigit($thousand, $words) . ' THOUSAND';
        }
        if ($hundred) {
            $str[] = $this->convertTwoDigit($hundred, $words) . ' HUNDRED';
        }
        if ($number) {
            $str[] = $this->convertTwoDigit($number, $words);
        }

        $result = implode(' ', $str) . ' RUPEES';

        if ($fraction > 0) {
            $result .= ' AND ' . $this->convertTwoDigit($fraction, $words) . ' PAISA';
        } else {
            $result .= ' ONLY';
        }

        return $result;
    }

    private function convertTwoDigit($num, $words)
    {
        if ($num < 20) {
            return $words[$num];
        }
        $tens = floor($num / 10) * 10;
        $units = $num % 10;
        return trim($words[$tens] . ' ' . $words[$units]);
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
}
