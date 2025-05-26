<?php

namespace App\Http\Controllers;

use App\Http\Controllers\BaseController;
use Illuminate\Http\Request;
use App\Models\VendorMaster;
use App\Models\PoMaster;
use App\Models\PoItems;
use App\Models\PrefixSetting;
use Illuminate\Support\Facades\Http;


class PdfExtractController extends BaseController
{
    protected $isSuperAdmin;

    public function __construct()
    {
        parent::__construct();
        $this->isSuperAdmin = request()->attributes->get('isSuperAdmin', false);
        $this->middleware('auth');

        if (!$this->isSuperAdmin) {
        }
    }

    public function index()
    {
        $page_data = [
            'page_title' => "PO Master",
            'page_main_title' => "PO Master",
            'page_child_title' => "Master",
            'isSuperAdmin' => $this->isSuperAdmin,

        ];
        $page_data['vendors'] = VendorMaster::whereIn('status', [0, 1])
            ->orderBy('id', 'asc')
            ->get();

        return view('pdf_extract.index', $page_data);
    }

    public function add()
    {
        $page_data = [
            'page_title' => "Add",
            'page_main_title' => "PDF Extract",
        ];

        $page_data['vendors'] = VendorMaster::whereIn('status', [0, 1])
            ->orderBy('id', 'asc')
            ->get();

        return view('pdf_extract.add', $page_data);
    }

    public function processpdf(Request $request)
    {
        $company = $request->input('company');
        $pdfBase64 = $request->input('pdf_base64');

        $response = Http::post('http://localhost:8000/process', [
            'company' => $company,
            'pdf_base64' => $pdfBase64,
        ]);

        if ($response->successful()) {
            $res_data = $response->json();
            $data = $res_data['data'];

            // print_r($data);

            // Handle different company data structures
            if ($company === 'Jack Jones') {
                $view = 'pdf_extract.pdf_response_view';
            } elseif ($company === 'Skecher') {
                $view = 'pdf_extract.skechers_response_view';
            } elseif ($company === 'Puma') {
                $data['po_details']['customer_address'] = $data['customer_details']['address'] ?? '';
                unset($data['customer_details']);
                $view = 'pdf_extract.puma_response_view';
            } elseif ($company === 'Benetton') {
                $view = 'pdf_extract.benetton_response_view';
            } else {
                $view = 'pdf_extract.pdf_response_view';
            }

            $html = view($view, compact('data'))->render();
            return response()->json(['status' => true, 'html' => $html]);
        } else {
            return response()->json(['error' => "Error processing PDF"], 500);
        }
    }

    public function store(Request $request)
    {
        try {
            $prefixSetting = PrefixSetting::where('id', 1)->first();

            if (!$prefixSetting) {
                throw new \Exception('PO prefix setting not found');
            }

            $currentNumber = $prefixSetting->number;
            $poNo = $prefixSetting->format . str_pad($currentNumber, 5, '0', STR_PAD_LEFT);

            $vendor_id = $request->input('vendor_name');

            // Modified data extraction logic
            if ($request->has('po_data')) {
                // For Skechers and Benetton - use entire po_data as po_details
                $data = json_decode($request->input('po_data'), true);
                $po_details = $data; // Use full data instead of nested array
                $po_items = $data['po_items'] ?? [];
                $article_details = null;
                $article_details_input = null;
            } else {
                // For Jack Jones and Puma - original logic
                $po_details = json_decode($request->input('po_details'), true);
                $article_details_input = $request->input('article_details');
                $article_details = json_decode($article_details_input, true); // Decode article_details
                $po_items = json_decode($request->input('po_items'), true);

                // Merge article_details into po_details for Puma
                if ($vendor_id === "Puma") {
                    $po_details['article_details'] = $article_details;
                }
            }

            // Create PO Master
            $pomaster = $this->createPoMaster($vendor_id, $poNo, $po_details, $article_details, $request, $article_details_input);

            // Update prefix number
            $prefixSetting->number = $currentNumber + 1;
            $prefixSetting->save();

            // Create PO Items
            $this->createPoItems($vendor_id, $pomaster->id, $po_items, $po_details);

            return response()->json([
                'success' => true,
                'message' => 'PO Stored successfully.',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while adding the PO: ' . $e->getMessage()
            ]);
        }
    }

    private function createPoMaster($vendor_id, $poNo, $po_details, $article_details, $request, $article_details_input)
    {
        $poData = [
            'vendor_id' => $vendor_id,
            'po_ref_num' => $poNo,
            'created_by' => auth()->user()->id,
            'created_at' => now(),
        ];

        switch ($vendor_id) {
            case "Jack Jones":
                $poData = array_merge($poData, [
                    'po_num' => $po_details['PO Number'],
                    'po_date' => $po_details['PO Date'],
                    'goods_ready_date' => $po_details['Goods Ready Date'],
                    'mrp' => $po_details['MRP'],
                    'vcp' => $po_details['VCP'],
                    'colors' => $po_details['Colors'],
                    'vendor_del_adr' => $po_details['Delivery Address'],
                    'vendor_com_adr' => $po_details['Communication Address'],
                    'vendor_gst' => $po_details['GSTIN'],
                    'vendor_cin' => $po_details['CIN'],
                    'article_info' => $article_details_input,
                    'po_unit_price' => $request->input('po_unit_price'),
                    'po_qty' => $request->input('po_qty'),
                ]);
                break;

            case "Skecher":
                $skecherDetails = $po_details['po_details'] ?? [];
                $poData = array_merge($poData, [
                    'po_num' => $skecherDetails['order_no'],
                    'po_date' => $skecherDetails['order_date'],
                    'vendor_com_adr' => $skecherDetails['customer_address'],
                    'vendor_gst' => $skecherDetails['customer_gstin'],
                    'vendor_del_adr' => json_encode($skecherDetails['ship_to_address']),
                ]);
                break;

            case "Puma":
                $poData = array_merge($poData, [
                    'po_num' => $po_details['po_number'],
                    'po_date' => $po_details['po_release_date'],
                    'goods_ready_date' => $po_details['po_ehd'],
                    'vendor_com_adr' => $po_details['customer_address'],
                    'vendor_del_adr' => $po_details['delivery_address'],
                    'article_info' => json_encode($article_details),
                ]);
                break;

            case "Benetton":
                $poData = array_merge($poData, [
                    'po_num' => $po_details['order_no'] ?? null,
                    'po_date' => $po_details['order_date'] ?? null,
                    'goods_ready_date' => $po_details['delivery_date'] ?? null,
                    'vendor_gst' => $po_details['gstin'] ?? null,
                    'vendor_del_adr' => json_encode($po_details['ship_to_address'] ?? []),
                    'season' => $po_details['season'] ?? null,
                ]);
                break;
        }

        return PoMaster::create($poData);
    }

    private function createPoItems($vendor_id, $po_id, $po_items, $po_details)
    {
        switch ($vendor_id) {
            case "Jack Jones":
                $this->createJackJonesItems($po_id, $po_items);
                break;

            case "Skecher":
                $this->createSkechersItems($po_id, $po_items);
                break;

            case "Puma":
                $this->createPumaItems($po_id, $po_items, $po_details);
                break;

            case "Benetton":
                $this->createBenettonItems($po_id, $po_items, $po_details);
                break;
        }
    }

    private function createJackJonesItems($po_id, $po_items)
    {
        foreach ($po_items as $po_item) {
            $poitemData = [
                'po_id' => $po_id,
                'sno' => $po_item['item_sno'],
                'article_number' => $po_item['article_number'],
                'id_color' => $po_item['artcicle_id_color'],
                'size' => $po_item['size_years'],
                'qty' => $po_item['quatity_uom'],
                'uom' => $po_item['quatity_uom'],
                'igst_taxable_value' => $po_item['igst_taxable_value'],
                'igst_per' => $po_item['igst_percentage'],
                'mrp' => $po_item['mrp'],
                'ean_code' => $po_item['ean_code'],
                'hsn_code' => $po_item['hsn_code'],
                'created_at' => now(),
                'created_by' => auth()->user()->id,
            ];

            PoItems::create($poitemData);
        }
    }

    private function createSkechersItems($po_id, $po_items)
    {
        $sizeColumns = ['XS', 'S', 'M', 'L', 'XL', 'XXL', 'XXXL'];

        foreach ($po_items as $index => $po_item) {
            // Skip invalid or summary rows
            if (empty($po_item['Style No.']) || stripos($po_item['Style No.'], 'total') !== false) {
                continue;
            }

            // Process each size column
            foreach ($sizeColumns as $size) {
                $qty = (int) str_replace(',', '', $po_item[$size] ?? '0');

                if ($qty <= 0) continue;

                PoItems::create([
                    'po_id' => $po_id,
                    'sno' => $po_item['Sr. No.'] ?? $index + 1,
                    'article_number' => $po_item['Style No.'],
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
                    'created_at' => now(),
                    'created_by' => auth()->user()->id,
                ]);
            }
        }
    }

    private function cleanNumber($value)
    {
        return (float) str_replace([',', '₹', ' '], '', $value);
    }

    private function cleanPercentage($value)
    {
        return (float) str_replace('%', '', $value);
    }

    private function createPumaItems($po_id, $po_items, $po_details)
    {
        // Access article_details from po_details
        $article_info = $po_details['article_details'] ?? [];

        foreach ($po_items as $index => $po_item) {
            // Skip total row
            if (isset($po_item['size']) && strtolower($po_item['size']) == 'total') {
                continue;
            }

            $poitemData = [
                'po_id' => $po_id,
                'sno' => $index + 1,
                'article_number' => $article_info['article_number'] ?? null,
                'style_description' => $article_info['style_description'] ?? null,
                'id_color' => $article_info['color'] ?? null, // Ensure this matches the key in article_details
                'product_character' => $article_info['product_character'] ?? null,
                'size' => $po_item['size'] ?? null,
                'qty' => $po_item['quantity'] ?? 0,
                'unit_price' => $po_item['unit_price'] ?? 0,
                'pack_factor' => $po_item['pack_factor'] ?? 0,
                'sku_line_no' => $po_item['sku_line_no'] ?? null,
                'incoterm' => $po_item['incoterm'] ?? null,
                'named_place' => $po_item['named_place'] ?? null,
                'created_at' => now(),
                'created_by' => auth()->user()->id,
            ];

            PoItems::create($poitemData);
        }
    }

    private function createBenettonItems($po_id, $po_items, $po_details)
    {
        foreach ($po_items as $po_item) {
            // Get size breakdown from size_tables if available
            if (empty($po_item['Col']) || $po_item['Col'] === 'Total') {
                continue;
            }

            $size_tables = $po_details['size_tables'] ?? [];
            $sizes = [];
            $sizeData = [];

            // Find matching size table data for this color
            foreach ($size_tables as $table) {
                $headers = $table['headers'] ?? [];
                $rows = $table['rows'] ?? [];

                foreach ($rows as $row) {
                    if ($row[0] == $po_item['Col']) {
                        $sizes = $headers;
                        $sizeData = explode(' ', $row[1]); // Split quantities by space
                        break 2; // Exit both loops
                    }
                }
            }

            // Create items for each size
            if (!empty($sizes) && !empty($sizeData)) {
                foreach ($sizes as $index => $size) {
                    $qty = isset($sizeData[$index]) ? (int) str_replace(',', '', $sizeData[$index]) : 0;

                    if ($qty > 0) {
                        $poitemData = [
                            'po_id' => $po_id,
                            'sno' => $po_item['S.N o'] ?? 0,
                            'article_number' => $po_item['Part No'] ?? null,
                            'part_description' => $po_item['Part Description'] ?? null,
                            'color_code' => $po_item['Col'] ?? null,
                            'size' => $size,
                            'qty' => $qty,
                            'unit_price' => $po_item['Basic Cost'] ?? 0,
                            'material_value' => ($qty * ($po_item['Basic Cost'] ?? 0)),
                            'igst_per' => $po_item['IGST %'] ?? 0,
                            'igst_taxable_value' => ($qty * ($po_item['Basic Cost'] ?? 0) * ($po_item['IGST %'] ?? 0) / 100),
                            'total_value' => ($qty * ($po_item['Basic Cost'] ?? 0) * (1 + ($po_item['IGST %'] ?? 0) / 100)),
                            'due_date' => $po_item['Due Date'] ?? null,
                            'mrp' => $po_item['MRP/UNIT'] ?? 0,
                            'hsn_code' => $po_item['HSN Code'] ?? null,
                            'created_at' => now(),
                            'created_by' => auth()->user()->id,
                        ];

                        PoItems::create($poitemData);
                    }
                }
            } else {
                // Fallback: create single item if no size breakdown
                $poitemData = [
                    'po_id' => $po_id,
                    'item_sno' => $po_item['S.N o'] ?? 0,
                    'item_article_number' => $po_item['Part No'] ?? null,
                    'part_description' => $po_item['Part Description'] ?? null,
                    'color_code' => $po_item['Col'] ?? null,
                    'qty' => $po_item['Qty'] ?? 0,
                    'unit_price' => $po_item['Basic Cost'] ?? 0,
                    'material_value' => $po_item['Material Value'] ?? 0,
                    'igst_per' => $po_item['IGST %'] ?? 0,
                    'igst_taxable_value' => $po_item['IGST Amount'] ?? 0,
                    'total_value' => $po_item['Total Value'] ?? 0,
                    'due_date' => $po_item['Due Date'] ?? null,
                    'mrp' => $po_item['MRP/UNIT'] ?? 0,
                    'hsn_code' => $po_item['HSN Code'] ?? null,
                    'created_at' => now(),
                    'created_by' => auth()->user()->id,
                ];

                PoItems::create($poitemData);
            }
        }
    }

    public function get_po_details(Request $request)
    {
        $po_id = $request->input('po_id');
        $po_master = PoMaster::findOrFail($po_id);
        $po_items = PoItems::where('po_id', $po_id)->get();

        // Format PO details in the expected structure
        $article_info = json_decode($po_master->article_info, true);

        $po_details = [
            'po_ref_num' => $po_master->po_ref_num,
            'PO Number' => $po_master->po_num,
            'PO Date' => $po_master->po_date,
            'Goods Ready Date' => $po_master->goods_ready_date,
            'MRP' => $po_master->mrp,
            'VCP' => $po_master->vcp,
            'Colors' => $po_master->colors,
            'GSTIN' => $po_master->vendor_gst,
            'CIN' => $po_master->vendor_cin,
            'Delivery Address' => $po_master->vendor_del_adr,
            'Communication Address' => $po_master->vendor_com_adr
        ];

        // Format PO items as expected by the view
        $formatted_po_items = [];
        foreach ($po_items as $item) {
            $formatted_item = [
                'sno' => $item->item_sno,
                'article_number' => $item->item_article_number,
                'color' => $item->item_id_color,
                'size' => $item->size_in_years,
                'quatity_uom' => $item->qty,
                'uom' => $item->uom,
                'igst_taxable_value' => $item->igst_taxable_value,
                'igst_per' => $item->igst_per,
                'mrp' => $item->mrp,
                'ean_code' => $item->ean_code
            ];
            $formatted_po_items[] = $formatted_item;
        }

        $data = [
            'po_details' => $po_details,
            'article_info' => $article_info,
            'po_items' => $formatted_po_items
        ];

        return view('pdf_extract.details', compact('data'));
    }
}
