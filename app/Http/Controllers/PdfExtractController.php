<?php

namespace App\Http\Controllers;

use App\Http\Controllers\BaseController;
use Illuminate\Http\Request;
use App\Models\VendorMaster;
use App\Models\PoMaster;
use App\Models\PoItems;
use App\Models\PoDmartSizes;
use App\Models\PrefixSetting;
use App\Models\PoSizes;
use App\Models\SizeChartMaster;
use App\Utils\POutils;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use App\Models\JobOrderMaster;
use Yajra\DataTables\Facades\DataTables;

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

    public function amended()
    {
        $page_data = [
            'page_title' => "PO Amended Master",
            'page_main_title' => "PO Amended Master",
            'page_child_title' => "Amended Master",
            'isSuperAdmin' => $this->isSuperAdmin,

        ];
        $page_data['vendors'] = VendorMaster::whereIn('status', [0, 1])
            ->orderBy('id', 'asc')
            ->get();

        return view('pdf_extract.amended', $page_data);
    }

    public function all()
    {
        $page_data = [
            'page_title' => "PO All Master",
            'page_main_title' => "PO All Master",
            'page_child_title' => "All Master",
            'isSuperAdmin' => $this->isSuperAdmin,

        ];
        $page_data['vendors'] = VendorMaster::whereIn('status', [0, 1])
            ->orderBy('id', 'asc')
            ->get();

        return view('pdf_extract.all', $page_data);
    }

    public function get_po_table(Request $request)
    {
        $query = POutils::getPoQuery($request, $this->isSuperAdmin);

        return DataTables::of($query)
            ->filter(function ($q) use ($request) {
                if ($search = trim($request->get('search')['value'] ?? '')) {
                    $q->where(function ($sub) use ($search) {
                        // Across PO ref and job numbers and num
                        $sub->orWhere('po_ref_num', 'like', "%{$search}%")
                            ->orWhere('po_job_num', 'like', "%{$search}%")
                            ->orWhere('po_num', 'like', "%{$search}%");

                        // Vendor name
                        $sub->orWhereHas('vendor', function ($vq) use ($search) {
                            $vq->where('name', 'like', "%{$search}%");
                        });

                        // PO date
                        $sub->orWhereDate('po_date', $search)
                            ->orWhere('po_date', 'like', "%{$search}%");
                    });
                }
            })
            ->addIndexColumn()
            ->addColumn('po_ref_num', function ($row) use ($request) {
                if ($request->type === 'amended') {
                    $displayText = $row->po_job_num;
                } else {
                    $displayText = $row->po_ref_num;
                }

                $row->pdf_file_exists = $row->pdf_file && Storage::exists('public/po/' . $row->pdf_file);
                $row->pdf_file_url = $row->pdf_file_exists ? url('storage/app/public/po/' . $row->pdf_file) : null;

                $viewDetails = '';
                $viewDetails .= '<li><a class="dropdown-item po-details-link" data-id="' . $row->id . '" href="javascript:void(0);">View</a></li>';

                if ($this->isSuperAdmin && $row->status == 0) {
                    $viewDetails .= '<li><a class="dropdown-item po-amend" data-id="' . $row->id . '" href="javascript:void(0);">Amend PO</a></li>';
                }

                if ($row->pdf_file_exists) {
                    $viewDetails .= '<li><a class="dropdown-item" href="' . $row->pdf_file_url . '" target="_blank" download>
                <i class="fas fa-download me-1"></i>Download PO
            </a></li>';
                }

                return '<div class="dropdown">
                <button class="btn btn-sm btn-primary dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">' . $displayText . '</button>
                <ul class="dropdown-menu">
                ' . $viewDetails . '
                </ul>
            </div>';
            })
            ->addColumn('status', function ($row) {
                if ($row->status == 0) {
                    return '<span class="badge bg-warning text-dark">Not Amended</span>';
                } elseif ($row->status == 1) {
                    return '<span class="badge bg-success">Amended</span>';
                } else {
                    return '<span class="badge bg-secondary">Unknown</span>';
                }
            })
            ->addColumn('vendor', function ($row) {
                $vendor_name = $row->vendor->name ?? 'N/A';

                return "<strong>{$vendor_name}</strong>";
            })
            ->addColumn('created', function ($row) use ($request) {
                if ($request->type  === 'amended') {
                    $amended_date = \Carbon\Carbon::parse($row->amended_at)->format('d-m-Y h:i A');
                    $amended_elapsed = \Carbon\Carbon::parse($row->amended_at)->diffForHumans();
                    $amender_name = $row->amend->full_name ?? 'N/A';

                    $output = "<div>{$amended_date}<br>
                    <span class='text-muted'>($amended_elapsed)</span></div>
                    <small class='text-muted'>Created By: {$amender_name}</small>";
                } else {
                    $created_date = \Carbon\Carbon::parse($row->created_at)->format('d-m-Y h:i A');
                    $time_elapsed = \Carbon\Carbon::parse($row->created_at)->diffForHumans();
                    $creator_name = $row->creator->full_name ?? 'N/A';

                    $output = "<div>{$created_date}<br>
                    <span class='text-muted'>($time_elapsed)</span></div>
                    <small class='text-muted'>Created By: {$creator_name}</small>";
                }

                return $output;
            })
            ->rawColumns(['po_ref_num', 'po_num', 'created', 'po_date', 'vendor', 'status'])
            ->make(true);
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

    public function get_vendor_custom_field(Request $request)
    {
        try {
            $vendor_id = $request->input('vendor_id');

            $vendor = VendorMaster::where('id', $vendor_id)->first();

            if ($vendor) {
                return response()->json([
                    'success' => true,
                    'custom_field_no' => $vendor->custom_field_no,
                    'extraction_no' => $vendor->extraction_no,
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Vendor not found'
                ]);
            }
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error fetching vendor data: ' . $e->getMessage()
            ]);
        }
    }

    public function pdf_process(Request $request)
    {
        $extraction_no = $request->input('extraction_no');
        $pdfBase64 = $request->input('pdf_base64');
        $vendor_id = $request->input('vendor_id');

        $response = Http::post('http://localhost:8000/process', [
            'extraction_no' => $extraction_no,
            'pdf_base64' => $pdfBase64,
        ]);

        if ($response->successful()) {
            $res_data = $response->json();
            $data = $res_data['data'];

            // Handle different company data structures
            $viewData = ['data' => $data];

            if ($extraction_no === '1') {
                $view = 'pdf_extract.jack_jones_response_view';
            } elseif ($extraction_no === '2') {
                $view = 'pdf_extract.skechers_response_view';
            } elseif ($extraction_no === '3') {
                $data['po_details']['customer_address'] = $data['customer_details']['address'] ?? '';
                unset($data['customer_details']);
                $view = 'pdf_extract.puma_response_view';
                $viewData['data'] = $data;
            } elseif ($extraction_no === '4') {
                $view = 'pdf_extract.benetton_response_view';
            } elseif ($extraction_no === '5') {
                $view = 'pdf_extract.aditiya_response_view';
            } elseif ($extraction_no === '6') {
                $view = 'pdf_extract.dmart_response_view';

                // Sizes come from the vendor's size chart - drives the carton qty/size table columns.
                // status = 0 means active for size_chart_master rows (same convention used when
                // these rows are created elsewhere), shown in the id order they were added.
                $sizes = SizeChartMaster::where('vendor_id', $vendor_id)
                    ->where('status', 0)
                    ->orderBy('id', 'asc')
                    ->pluck('size')
                    ->toArray();

                // Sum of the per-line "case lot" values extracted from the PDF, used as the
                // starting value for the editable Case Lot field
                $totalCaseLot = 0;
                foreach ($data['po_items'] ?? [] as $item) {
                    $totalCaseLot += (float) str_replace(',', '', $item['case_lot'] ?? 0);
                }

                $viewData['sizes'] = $sizes;
                $viewData['totalCaseLot'] = $totalCaseLot;
            } else {
                $view = 'pdf_extract.jack_jones_response_view';
            }

            $html = view($view, $viewData)->render();
            return response()->json(['status' => true, 'html' => $html]);
        } else {
            return response()->json(['error' => "Error processing PDF"], 500);
        }
    }

    public function check_po_exists(Request $request)
    {
        try {
            $vendor_id = $request->input('vendor_id');
            $po_num = null;

            if ($request->has('po_data')) {
                $data = json_decode($request->input('po_data'), true);
                if ($vendor_id === "2") {
                    $po_num = $data['po_details']['order_no'] ?? null;
                } elseif ($vendor_id === "4") {
                    $po_num = $data['order_no'] ?? null;
                } elseif ($vendor_id === "7") {
                    $po_num = $data['po_number'] ?? null;
                } elseif ($vendor_id === "8") {
                    $po_num = $data['po_number'] ?? null;
                }
            } else {
                $po_details = json_decode($request->input('po_details'), true);
                if (in_array($vendor_id, ["1", "5", "6", "7"])) {
                    $po_num = $po_details['PO Number'] ?? null;
                } elseif ($vendor_id === "3") {
                    $po_num = $po_details['po_number'] ?? null;
                }
            }

            if (!$po_num) {
                return response()->json([
                    'exists' => false,
                    'message' => 'PO number not found'
                ]);
            }

            // Check if PO exists with status = 1 (amended)
            $amendedPo = PoMaster::where('po_num', $po_num)->where('status', 1)->first();

            if ($amendedPo) {
                return response()->json([
                    'exists' => true,
                    'amended' => true,
                    'po_num' => $po_num,
                    'po_id' => $amendedPo->id,
                    'message' => 'PO number already exists with amended status'
                ]);
            }

            // Check if PO exists with status = 0 (not amended)
            $unamendedPo = PoMaster::where('po_num', $po_num)->where('status', 0)->first();

            if ($unamendedPo) {
                return response()->json([
                    'exists' => true,
                    'amended' => false,
                    'po_num' => $po_num,
                    'po_id' => $unamendedPo->id,
                    'message' => 'PO number already exists but not amended'
                ]);
            }

            return response()->json([
                'exists' => false,
                'po_num' => $po_num
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'exists' => false,
                'message' => 'Error checking PO existence: ' . $e->getMessage()
            ]);
        }
    }

    public function store(Request $request)
    {
        try {
            $prefixSetting = PrefixSetting::where('id', 1)->first();

            if (!$prefixSetting) {
                throw new \Exception('PO prefix setting not found');
            }

            // Handle PDF file upload with random name
            $pdfFileName = null;
            if ($request->hasFile('pdf_file')) {
                $file = $request->file('pdf_file');

                // Generate random filename with original extension
                $randomName = Str::random(40);
                $extension = $file->getClientOriginalExtension();
                $pdfFileName = $randomName . '.' . $extension;

                // Store file in storage/app/public/po directory
                $file->storeAs('public/po', $pdfFileName);
            }

            $currentNumber = $prefixSetting->number;
            $poNo = $prefixSetting->format . str_pad($currentNumber, 5, '0', STR_PAD_LEFT);

            $vendor_id = $request->input('vendor_id');
            $hsn_code = $request->input('hsn_code');

            // The color/size carton breakdown the user builds in the UI (D-Mart only) - goes
            // into po_carton_qty_sizes, never into po_items
            $carton_qty_sizes = json_decode($request->input('carton_qty_sizes'), true) ?? [];

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
                if ($vendor_id === "3") {
                    $po_details['article_details'] = $article_details;
                }
            }

            // Create PO Master
            $pomaster = $this->createPoMaster($vendor_id, $poNo, $po_details, $article_details, $request, $article_details_input, $pdfFileName);

            // Update prefix number
            $prefixSetting->number = $currentNumber + 1;
            $prefixSetting->save();

            // Create PO Items
            $this->createPoItems($vendor_id, $pomaster->id, $po_items, $po_details, $hsn_code, $carton_qty_sizes);

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

    private function createPoMaster($vendor_id, $poNo, $po_details, $article_details, $request, $article_details_input, $pdfFileName)
    {
        $actualVendorId = $this->getVendorIdByName($vendor_id);

        $poData = [
            'vendor_id' => $actualVendorId,
            'po_ref_num' => $poNo,
            'pdf_file' => $pdfFileName,
            'created_by' => auth()->user()->id,
            'created_at' => now(),
        ];

        switch ($vendor_id) {
            case "1":
            case "5":
            case "6":
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

            case "2":
                $skecherDetails = $po_details['po_details'] ?? [];
                $poData = array_merge($poData, [
                    'po_num' => $skecherDetails['order_no'],
                    'po_date' => $skecherDetails['order_date'],
                    'vendor_customer_name' => $skecherDetails['customer_name'],
                    'vendor_com_adr' => $skecherDetails['customer_address'],
                    'vendor_gst' => $skecherDetails['customer_gstin'],
                    'vendor_del_adr' => json_encode($skecherDetails['ship_to_address']),
                ]);
                break;

            case "3":
                $poData = array_merge($poData, [
                    'po_num' => $po_details['po_number'],
                    'po_date' => $po_details['po_release_date'],
                    'goods_ready_date' => $po_details['po_ehd'],
                    'vendor_com_adr' => $po_details['customer_address'],
                    'vendor_del_adr' => $po_details['delivery_address'],
                    'article_info' => json_encode($article_details),
                ]);
                break;

            case "4":
                $poData = array_merge($poData, [
                    'po_num' => $po_details['order_no'] ?? null,
                    'po_date' => $po_details['order_date'] ?? null,
                    'goods_ready_date' => $po_details['delivery_date'] ?? null,
                    'vendor_gst' => $po_details['gstin'] ?? null,
                    'vendor_del_adr' => json_encode($po_details['ship_to_address'] ?? []),
                    'season' => $po_details['season'] ?? null,
                ]);
                break;

            case "7":
                $totalQty = 0;
                $unitPrice = 0;
                $colors = [];

                // Extract vendor information
                $vendorInfo = [];
                if (isset($po_details['vendor_info'])) {
                    $vendorInfo = $po_details['vendor_info'];
                } elseif (isset($po_details['Vendor'])) {
                    // If vendor info is at root level
                    $vendorInfo = [
                        'Vendor' => $po_details['Vendor'] ?? null,
                        'Price per unit' => $po_details['Price per unit'] ?? null,
                        'Total unit' => $po_details['Total unit'] ?? null,
                        'Net Value' => $po_details['Net Value'] ?? null,
                    ];
                }

                if (isset($po_details['po_items']) && is_array($po_details['po_items'])) {
                    foreach ($po_details['po_items'] as $item) {
                        $totalQty += floatval($item['Qty'] ?? 0);
                        if ($unitPrice == 0) {
                            $unitPrice = floatval($item['Rate/Unit'] ?? 0);
                        }
                    }
                }

                // If no unit price from items, try to get from vendor info
                if ($unitPrice == 0 && isset($vendorInfo['Price per unit'])) {
                    $unitPrice = floatval(str_replace(',', '', $vendorInfo['Price per unit']));
                }

                // If no total qty from items, try to get from vendor info
                if ($totalQty == 0 && isset($vendorInfo['Total unit'])) {
                    $totalQty = floatval(str_replace(',', '', $vendorInfo['Total unit']));
                }

                if (isset($po_details['material_descriptions']) && is_array($po_details['material_descriptions'])) {
                    foreach ($po_details['material_descriptions'] as $material) {
                        if (!empty($material['Colour'])) {
                            $colors[] = $material['Colour'];
                        }
                    }
                }

                $poData = array_merge($poData, [
                    'po_num' => $po_details['po_number'] ?? null,
                    'po_date' => $po_details['po_date'] ?? null,
                    'goods_ready_date' => $po_details['po_items'][0]['Delivery Date'] ?? null,
                    'vendor_del_adr' => is_array($po_details['bill_to_address'] ?? null)
                        ? implode(', ', $po_details['bill_to_address'])
                        : ($po_details['bill_to_address'] ?? null),
                    'vendor_com_adr' => is_array($po_details['ship_to_address'] ?? null)
                        ? implode(', ', $po_details['ship_to_address'])
                        : ($po_details['ship_to_address'] ?? null),
                    'vendor_gst' => $po_details['gstin'] ?? $po_details['gst_number'] ?? null,
                    'colors' => implode(', ', array_unique($colors)),
                    'po_unit_price' => $unitPrice,
                    'po_qty' => $totalQty,
                    'article_info' => json_encode([
                        'vendor_number' => $vendorInfo['Vendor'] ?? $po_details['vendor_number'] ?? null,
                        'price_per_unit' => $vendorInfo['Price per unit'] ?? null,
                        'total_unit' => $vendorInfo['Total unit'] ?? null,
                        'net_value' => $vendorInfo['Net Value'] ?? null,
                    ]), // Store vendor info in article_info as JSON
                ]);
                break;

            case "8":
                // Case Lot is a per-line field on the extracted PDF - roll it up to a PO-level total
                $totalCaseLot = 0;
                $itemDescriptions = [];
                foreach ($po_details['po_items'] ?? [] as $item) {
                    $totalCaseLot += (float) str_replace(',', '', $item['case_lot'] ?? 0);
                    if (!empty($item['description'])) {
                        $itemDescriptions[] = $item['description'];
                    }
                }

                $poData = array_merge($poData, [
                    'po_num'           => $po_details['po_number'] ?? null,
                    'po_date'          => $po_details['po_date'] ?? null,
                    'goods_ready_date' => $po_details['exp_delivery_dt'] ?? null,
                    'vendor_del_adr'   => $po_details['buyer_address'] ?? null,
                    'vendor_com_adr'   => $po_details['vendor_address'] ?? null,
                    'vendor_gst'       => $po_details['vendor_gstin'] ?? null,
                    'po_unit_price'    => $po_details['po_items'][0]['net_price'] ?? 0,
                    'po_qty'           => (float) str_replace(',', '', $po_details['total_qty'] ?? 0),
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
                        'po_items'          => $po_details['po_items'] ?? [],
                    ]),
                ]);
                break;
        }

        return PoMaster::create($poData);
    }

    private function getVendorIdByName($vendorName)
    {
        $vendor = VendorMaster::where('id', $vendorName)->first();

        return $vendor->id;
    }

    private function createPoItems($vendor_id, $po_id, $po_items, $po_details, $hsn_code, $carton_qty_sizes = [])
    {
        switch ($vendor_id) {
            case "1":
            case "5":
            case "6":
                $this->createJackJonesItems($po_id, $po_items);
                break;

            case "2":
                $this->createSkechersItems($po_id, $po_items, $hsn_code);
                break;

            case "3":
                $this->createPumaItems($po_id, $po_items, $po_details, $hsn_code);
                break;

            case "4":
                $this->createBenettonItems($po_id, $po_items, $po_details, $vendor_id);
                break;

            case "7":
                $this->createAdityaItems($po_id, $po_items, $po_details, $hsn_code);
                break;

            case "8":
                $this->createDmartItems($po_id, $po_items, $po_details, $hsn_code, $carton_qty_sizes);
                break;
        }
    }

    private function createJackJonesItems($po_id, $po_items)
    {
        foreach ($po_items as $po_item) {
            $quantityUom = $po_item['quatity_uom'];

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

            $poitemData = [
                'po_id' => $po_id,
                'sno' => $po_item['item_sno'],
                'article_number' => $po_item['article_number'],
                'id_color' => $colorId,
                'color' => $colorName,
                'size' => $po_item['size_years'],
                'qty' => $qty,
                'uom' => $uom,
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

    private function createSkechersItems($po_id, $po_items, $hsn_code)
    {
        $sizeColumns = ['XS', 'S', 'M', 'L', 'XL', 'XXL', 'XXXL'];

        // Group items by style and color, calculate totals
        $styleColorGroups = [];
        foreach ($po_items as $index => $po_item) {
            if (empty($po_item['Style No.']) || stripos($po_item['Style No.'], 'total') !== false) {
                continue;
            }

            $key = $po_item['Style No.'] . '_' . ($po_item['Color'] ?? '');
            $totalQty = array_sum(array_map(function ($size) use ($po_item) {
                return (int) str_replace(',', '', $po_item[$size] ?? '0');
            }, $sizeColumns));

            $styleColorGroups[$key][] = ['index' => $index, 'total_qty' => $totalQty, 'data' => $po_item];
        }

        // Determine country for each item
        $countryMap = [];
        foreach ($styleColorGroups as $group) {
            if (count($group) === 1) {
                $countryMap[$group[0]['index']] = 'India';
            } else {
                // Sort by total quantity (highest first)
                usort($group, fn($a, $b) => $b['total_qty'] <=> $a['total_qty']);
                foreach ($group as $i => $item) {
                    $countryMap[$item['index']] = $i === 0 ? 'India' : 'UAE';
                }
            }
        }

        // Create PO items with country assignment
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
                    'hsn_code' => $hsn_code,
                    'country' => $countryMap[$index] ?? 'India',
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

    private function createPumaItems($po_id, $po_items, $po_details, $hsn_code)
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
            ];

            PoItems::create($poitemData);
        }
    }

    private function createBenettonItems($po_id, $po_items, $po_details, $vendor_id)
    {
        $size_tables     = $po_details['size_tables'] ?? [];
        $processedColors = [];

        foreach ($po_items as $po_item) {
            $color = $po_item['Col'] ?? null;
            // skip totals or blank color for PoItems
            if (empty($color) || $color === 'Total') {
                continue;
            }

            // create master PoItems
            $masterQty  = (int) str_replace(',', '', $po_item['Qty'] ?? 0);
            $masterCost = (float) str_replace(',', '', $po_item['Basic Cost'] ?? 0);
            $master     = PoItems::create([
                'po_id'              => $po_id,
                'sno'                => $po_item['S.N o'] ?? 0,
                'article_number'     => $po_item['Part No'] ?? null,
                'part_description'   => $po_item['Part Description'] ?? null,
                'id_color'           => $color,
                'color'              => $color,
                'qty'                => $masterQty,
                'unit_price'         => $masterCost,
                'material_value'     => $masterQty * $masterCost,
                'igst_per'           => $po_item['IGST %'] ?? 0,
                'igst_taxable_value' => $masterQty * $masterCost * (($po_item['IGST %'] ?? 0) / 100),
                'total_value'        => $masterQty * $masterCost * (1 + (($po_item['IGST %'] ?? 0) / 100)),
                'due_date'           => $po_item['Due Date'] ?? null,
                'mrp'                => $po_item['MRP/UNIT'] ?? 0,
                'hsn_code'           => $po_item['HSN Code'] ?? null,
                'created_at'         => now(),
                'created_by'         => auth()->user()->id,
            ]);

            // skip config entries if already processed this color
            if (in_array($color, $processedColors, true)) {
                continue;
            }

            // Find size breakdown for this color
            $sizes   = [];
            $qtyData = [];
            foreach ($size_tables as $table) {
                foreach ($table['rows'] ?? [] as $row) {
                    if ($row[0] == $color) {
                        $sizes   = $table['headers'];
                        $qtyData = explode(' ', $row[1]);
                        break 2;
                    }
                }
            }

            // no breakdown: mark and skip
            if (empty($sizes) || empty($qtyData)) {
                $processedColors[] = $color;
                continue;
            }

            // insert size configuration items
            foreach ($sizes as $idx => $size) {
                $qty = isset($qtyData[$idx]) ? (int) str_replace(',', '', $qtyData[$idx]) : 0;
                if ($qty <= 0) {
                    continue;
                }

                PoSizes::create([
                    'po_id'       => $po_id,
                    'vendor_id'   => $vendor_id,
                    'color'       => $color,
                    'size'        => $size,
                    'qty'      => $qty,
                    'created_at'  => now(),
                    'created_by'  => auth()->user()->id,
                ]);
            }

            // mark this color done for config only
            $processedColors[] = $color;
        }
    }

    private function createAdityaItems($po_id, $po_items, $po_details, $hsn_code)
    {
        $materialDescriptions = $po_details['material_descriptions'] ?? [];

        foreach ($po_items as $index => $item) {
            // Get corresponding material description
            $materialDesc = $materialDescriptions[$index] ?? [];

            $poItemData = [
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
                'mrp' => floatval($item['MRP'] ?? 0),
                'location' => $item['Stor e Loc'] ?? null,
                'due_date' => $item['Delivery Date'] ?? null,
                'content' => $materialDesc['Material'] ?? null,
                'style_description' => $materialDesc['Material description'] ?? null,
                'color' => $materialDesc['Colour'] ?? null,
                'product_character' => $materialDesc['Warer Trail'] ?? null,
                'mrp' => $item['Mrp'] ?? null,
                'created_at' => now(),
                'created_by' => auth()->user()->id,
                'status' => 0,
            ];

            PoItems::create($poItemData);
        }
    }

    private function createDmartItems($po_id, $po_items, $po_details, $hsn_code, $carton_qty_sizes = [])
    {
        // Representative article info taken from the extracted PDF line item(s)
        $firstItem = $po_items[0] ?? [];
        $articleDescription = $this->extractArticleDescription($firstItem['description'] ?? null);
        $eanCode = $firstItem['ean'] ?? null;
        $hsnCode = $firstItem['hsn'] ?: $hsn_code;

        $gstPercentage = $this->cleanPercentage($firstItem['cgst_igst_pct'] ?? 0);
        $price = $this->cleanNumber($firstItem['l_price'] ?? 0);
        $mrpPrice = $this->cleanNumber($firstItem['mrp'] ?? 0);

        // Total qty is a PO-level figure from the PDF (not a per-line sum)
        $totalQtyFromPdf = (float) str_replace(',', '', $po_details['total_qty'] ?? 0);

        // Ratio = case lot / number of distinct colors entered by the user
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

    public function get_po_details(Request $request)
    {
        $po_id = $request->input('po_id');
        $po_master = PoMaster::findOrFail($po_id);
        $po_items = PoItems::where('po_id', $po_id)->get();
        $article_info = json_decode($po_master->article_info, true);

        $vendor_id = $po_master->vendor_id;
        $hsn_code = $po_items->isNotEmpty() ? $po_items->first()->hsn_code : '';

        // For Benetton (vendor 4), fetch config items for size breakdown
        $size_breakdown = [];
        if ($vendor_id == 4) {
            $colors = $po_items->pluck('id_color')->unique()->values();
            $configItems = PoSizes::where('vendor_id', $vendor_id)
                ->whereIn('color', $colors)
                ->get();

            $sizes = $configItems->pluck('size')->unique()->sort()->values()->toArray();

            $breakdown = [];
            foreach ($colors as $color) {
                $row = ['Color' => $color];
                $total = 0;
                foreach ($sizes as $size) {
                    $qty = $configItems->where('color', $color)
                        ->where('size', $size)
                        ->sum('qty');
                    $row[$size] = $qty ?: '-';
                    if ($qty) $total += $qty;
                }
                $row['TOTAL'] = $total;
                $breakdown[] = $row;
            }

            $size_breakdown = [
                'data' => $breakdown,
                'sizes' => $sizes,
            ];
        }

        // For D-Mart (vendor 8), pull the carton qty/size breakdown from its own table
        $carton_qty_sizes = [];
        if ($vendor_id == 8) {
            $cartonRows = PoDmartSizes::where('po_id', $po_id)->get();
            $carton_qty_sizes = [
                'data' => $cartonRows,
                'sizes' => $cartonRows->pluck('size')->unique()->values()->toArray(),
            ];
        }

        // Format PO items for different vendors
        $formatted_po_items = [];
        if (in_array($vendor_id, [1, 5, 6])) {
            // Jack Jones format
            foreach ($po_items as $item) {
                $formatted_po_items[] = [
                    'sno' => $item->sno,
                    'article_number' => $item->article_number,
                    'color' => $item->id_color,
                    'size' => $item->size,
                    'quatity_uom' => $item->qty,
                    'uom' => $item->uom,
                    'igst_taxable_value' => $item->igst_taxable_value,
                    'igst_per' => $item->igst_per,
                    'mrp' => $item->mrp,
                    'ean_code' => $item->ean_code
                ];
            }
        } elseif ($vendor_id == 7) {
            $po_items_array = [];
            $material_descriptions_array = [];

            foreach ($po_items as $index => $item) {
                $po_items_array[] = [
                    'Material Code' => $item->article_number,
                    'HSN Number' => $item->hsn_code,
                    'Qty' => $item->qty,
                    'Unit' => $item->uom,
                    'Per' => '1', // Default value
                    'Rate/Unit' => $item->material_value,
                    'Net Value' => $item->igst_taxable_value,
                    'IGST %' => $item->igst_per,
                    'CGST %' => '', // Empty as specified
                    'SGST %' => '', // Empty as specified
                    'UGST %' => '', // Empty as specified
                    'Val1' => $item->total_amount, // Tax amount
                    'Val2' => '', // Empty as specified
                    'Delivery Date' => $item->due_date,
                    'Size' => $item->size,
                    'Sizewise Qty' => $item->qty,
                    'MRP' => $item->mrp,
                    'Stor e Loc' => $item->location,
                ];

                $material_descriptions_array[] = [
                    'Material' => $item->content,
                    'Material description' => $item->style_description,
                    'Colour' => $item->color,
                    'Warer Trail' => $item->product_character,
                ];
            }

            $data = [
                'po_number' => $po_master->po_num,
                'po_date' => $po_master->po_date,
                'vendor_number' => $article_info['vendor_number'] ?? '',
                'bill_to_address' => explode(', ', $po_master->vendor_com_adr ?? ''),
                'ship_to_address' => explode(', ', $po_master->vendor_del_adr ?? ''),
                'po_items' => $po_items_array,
                'material_descriptions' => $material_descriptions_array,
            ];
        }

        $data = compact(
            'po_master',
            'article_info',
            'po_items',
            'hsn_code',
            'formatted_po_items',
            'size_breakdown',
            'carton_qty_sizes'
        );

        // Add reconstructed data for Aditiya
        if ($vendor_id == 7) {
            $data['data'] = $data;
        }

        // Choose view by vendor
        switch ($vendor_id) {
            case 1:
            case 5:
            case 6:
                $view = 'pdf_extract.jack_jones_details';
                break;
            case 2:
                $view = 'pdf_extract.skechers_details';
                break;
            case 3:
                $view = 'pdf_extract.puma_details';
                break;
            case 4:
                $view = 'pdf_extract.benetton_details';
                break;
            case 7:
                $view = 'pdf_extract.aditiya_details';
                break;
            case 8:
                $view = 'pdf_extract.dmart_details';
                break;
            default:
                $view = 'pdf_extract.details';
        }

        return view($view, compact('data'));
    }

    public function get_amend_details(Request $request)
    {
        $po_id = $request->input('po_id');
        $po_master = PoMaster::findOrFail($po_id);

        $existingAmendedPo = PoMaster::where('po_num', $po_master->po_num)
            ->where('id', '!=', $po_id)
            ->where('status', 1)
            ->first();

        if ($existingAmendedPo) {
            return response()->json([
                'success' => false,
                'message' => 'Another PO with the same PO number has already been amended. Cannot amend this PO.',
                'amended_po_id' => $existingAmendedPo->id
            ]);
        }

        $job_orders = JobOrderMaster::where('vendor_id', $po_master->vendor_id)
            ->where('status', 0)
            ->select('id', 'job_no', 'type')
            ->get();

        return view('pdf_extract.amend_details', compact('po_master', 'job_orders'));
    }

    public function po_amended(Request $request)
    {
        $request->validate([
            'po_id' => 'required',
            'job_order_id' => 'required|exists:job_order_master,id',
        ]);

        $po = PoMaster::findOrFail($request->po_id);
        $job_order = JobOrderMaster::findOrFail($request->job_order_id);

        $po->status = 1;
        $po->po_job_num = $job_order->job_no;
        $po->po_job_id = $job_order->id;
        $po->po_job_type = $job_order->type;
        $po->remarks = $request->remarks;
        $po->amended_at = now();
        $po->amended_by = auth()->user()->id;
        $po->save();

        $job_order->status = 1;
        $job_order->save();

        return response()->json([
            'success' => true,
            'message' => 'Purchase Order amended successfully.',
        ]);
    }
}
