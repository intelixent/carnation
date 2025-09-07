<?php

namespace App\Http\Controllers;

use App\Http\Controllers\BaseController;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use App\Models\VendorMaster;
use App\Models\PoMaster;
use App\Models\PoItems;
use App\Models\PoSizes;
use App\Models\PrefixSetting;
use Illuminate\Support\Facades\Http;
use App\Utils\POutils;
use Illuminate\Support\Str;
use Yajra\DataTables\Facades\DataTables;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;
use App\Models\PackingListMaster;
use App\Models\PackingListItem;
use App\Models\InvoiceMaster;
use App\Models\StateMaster;
use App\Models\TransportMaster;
use App\Models\BusinessSettingMaster;

class InvoiceController extends BaseController
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

    public function genrate()
    {
        $page_data = [
            'page_title' => "Genrate Invoice",
            'page_main_title' => "Genrate Invoice",
            'page_child_title' => "Genrate Invoice",
            'isSuperAdmin' => $this->isSuperAdmin,
            'vendors' => VendorMaster::where('status', 0)->get(),
        ];


        return view('invoice.genrate', $page_data);
    }

    public function get_complete_vendor_packing_list(Request $request)
    {
        $packingLists = PackingListMaster::with('vendor', 'po')
            ->where('vendor_id', $request->vendor_id)
            ->where('status', 0)
            ->where('pack_status', 1)
            ->get(['id', 'po_id', 'pack_ref_no', 'vendor_id', 'po_no']);

        if ($packingLists->isEmpty()) {
            return response()->json(['error' => 'No completed packing lists available for this vendor'], 404);
        }

        $transformedPackingLists = $packingLists->map(function ($packingList) {
            return [
                'id' => $packingList->po_id,
                'po_num' => $packingList->po_no,
                'po_ref_num' => $packingList->po->po_ref_num ?? 'N/A',
                'po_job_num' => $packingList->po->po_job_num ?? 'N/A',
                'vendor_name' => $packingList->vendor->name ?? 'N/A',
                'pack_ref_no' => $packingList->pack_ref_no
            ];
        });

        return response()->json($transformedPackingLists);
    }

    public function get_packging_list(Request $request)
    {
        $poId = $request->id;
        $vendor_id = $request->vendor_id;
        // $packed_lists = PackingListMaster::where(array('po_id' => $poId, 'pack_status' => 1))->withCount('items')->get();
        $packed_lists = DB::table('packing_list_items as pli')
            ->join('packing_list_masters as plm', 'pli.packing_list_id', '=', 'plm.id')
            ->select(
                'pli.packing_list_id as id',
                'plm.pack_ref_no',
                DB::raw('MIN(pli.created_at) as created_at'),
                DB::raw('MIN(pli.color) as color'),
                DB::raw('COUNT(DISTINCT pli.carton_name) as carton_count')
            )
            ->where('plm.po_id', $poId)
            ->where('plm.pack_status', 1)
            ->groupBy('pli.packing_list_id', 'plm.pack_ref_no')
            ->get();

        if (!$packed_lists) {
            return response()->json(['error' => 'Not found'], 404);
        }


        return view('invoice.packing_details', compact(
            'packed_lists',
            'poId',
            'vendor_id'
        ));
    }

    public function store_invoice(Request $request)
    {
        $data = $request->validate([
            'selectedpackids'    => 'required|array|min:1',
            'selectedpackids.*'  => 'integer|exists:packing_list_masters,id',
            'selected_po'        => 'required|integer|exists:po_masters,id',
            'selected_vendor_id' => 'required|integer|exists:vendor_master,id',
            'invoice_no'         => 'required|string|max:100',
            'invoice_date'       => 'required|date',
            'gst'                => 'required|numeric|min:0|max:100', // Added GST validation
        ]);

        try {
            $invoice = InvoiceMaster::create([
                'ref_no'           => $data['invoice_no'],
                'inv_date'         => $data['invoice_date'],
                'gst'              => $data['gst'], // Store GST value
                'bill_to_details'  => '',
                'ship_to_details'  => '',
                'po_id'            => $data['selected_po'],
                'pack_ids'         => implode(',', $data['selectedpackids']),
                'vendor_id'        => $data['selected_vendor_id'],
                'created_by'       => auth()->id(),
                'created_at'       => now(),
            ]);

            PackingListMaster::whereIn('id', $data['selectedpackids'])
                ->update(['pack_status' => 2]);

            $invoiceData = $this->getInvoicePreviewData($invoice->id);

            return response()->json([
                'success' => true,
                'message' => "Invoice {$invoice->ref_no} saved successfully.",
                'invoice_data' => $invoiceData
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to save invoice: ' . $e->getMessage()
            ], 500);
        }
    }

    private function getInvoicePreviewData($invoiceId)
    {
        $invoice = InvoiceMaster::with(['po.vendor'])->findOrFail($invoiceId);
        $po_details = $invoice->po;
        $vendor = $po_details->vendor;

        // Get packing list IDs for this invoice
        $packIds = explode(',', $invoice->pack_ids);

        // Get invoice GST rate
        $invoiceGstRate = floatval($invoice->gst);

        // Group items by size, aggregate all colors, sum quantities
        $packedItems = PackingListItem::whereIn('packing_list_id', $packIds)
            ->select([
                'size',
                DB::raw('GROUP_CONCAT(DISTINCT color ORDER BY color SEPARATOR ", ") AS colors'),
                DB::raw('SUM(quantity) AS total_quantity'),
                DB::raw('COUNT(DISTINCT carton_name) AS carton_counts'),
            ])
            ->groupBy('size')
            ->get();

        // Build invoice line items array
        $items = [];
        $poUnitPrice = floatval($po_details->vcp);
        $articleInfo = json_decode($po_details->article_info, true) ?: [];

        foreach ($packedItems as $pi) {
            $description = $style = $hsn_code = '';
            $uom = 'PCS';
            $unit_price = $poUnitPrice;

            // Choose a representative color for lookups
            $colors = explode(',', $pi->colors);
            $firstColor = trim($colors[0]);

            // Fetch PoItems by representative color & size
            $itm = PoItems::where('po_id', $po_details->id)
                ->where('size', $pi->size)
                ->where('color', $firstColor)
                ->first();

            // Use invoice GST rate instead of item-specific rates
            $gstRate = $invoiceGstRate;

            switch ($vendor->id) {
                case 1:
                case 5:
                case 6:
                    $description = $articleInfo['Article description'] ?? '';
                    $style = $articleInfo['ARTICLE'] ?? '';
                    if ($itm) {
                        $hsn_code = $itm->hsn_code;
                        $uom = $itm->uom;
                    }
                    break;

                case 4:
                    $poSize = PoSizes::where('po_id', $po_details->id)
                        ->where('size', $pi->size)
                        ->where('color', $firstColor)
                        ->first();
                    if ($poSize) {
                        $unit_price = $poSize->unit_price ?: $unit_price;
                        $hsn_code = $poSize->hsn_code ?? '';
                        $uom = $poSize->uom ?? 'PCS';
                    }
                    if ($itm) {
                        $unit_price = $unit_price ?: floatval($itm->unit_price);
                        $hsn_code = $hsn_code ?: $itm->hsn_code;
                        $uom = $uom ?: $itm->uom;
                        $description = $itm->part_description ?? '';
                        $style = $itm->article_number ?? '';
                    }
                    break;

                case 2:
                case 3:
                    if ($itm) {
                        $unit_price = floatval($itm->unit_price ?? 0);
                        $hsn_code = $itm->hsn_code ?? '';
                        $uom = $itm->uom ?? 'PCS';
                        if ($vendor->id === 2) {
                            $description = $itm->type;
                            $style = $itm->article_number;
                        } else {
                            $description = $itm->style_description;
                            $style = $itm->article_number;
                        }
                    }
                    break;

                default:
                    $description = $articleInfo['Article description'] ?? '';
                    $style = $articleInfo['ARTICLE'] ?? '';
                    break;
            }

            // Fallback hsn_code
            if (empty($hsn_code) && $itm) {
                $hsn_code = $itm->hsn_code;
                $uom = $itm->uom;
            }

            // Compute amounts
            $amount = $pi->total_quantity * $unit_price;
            $discountPct = $vendor->discount ?? 0;
            $discountAmount = ($amount * $discountPct) / 100;
            $taxableValue = $amount - $discountAmount;
            $gstAmount = ($taxableValue * $gstRate) / 100;

            $items[] = [
                'description' => $description,
                'hsn_code' => $hsn_code,
                'style' => $style,
                'colors' => $pi->colors,
                'total_cartons' => $pi->carton_counts,
                'unit' => $uom,
                'size' => $pi->size,
                'qty' => $pi->total_quantity,
                'rate' => $unit_price,
                'amount' => $amount,
                'discount' => $discountAmount,
                'taxable_value' => $taxableValue,
                'gst_rate' => $gstRate,
                'gst_amount' => $gstAmount,
            ];
        }

        return [
            'invoice' => [
                'ref_no' => $invoice->ref_no,
                'inv_date' => $invoice->inv_date,
                'gst_rate' => $invoiceGstRate,
            ],
            'po_details' => $po_details,
            'vendor' => $vendor,
            'items' => $items
        ];
    }

    public function generateInvoice(Request $request)
    {
        // Load invoice with PO → vendor → state
        $invoice = InvoiceMaster::with(['po.vendor.state'])->findOrFail($request->id);
        $po_details = $invoice->po;
        $vendor = $po_details->vendor;
        $state = $vendor->state;

        // Business settings
        $businessSettings = BusinessSettingMaster::whereIn('name', [
            'nsme_register_no',
            'nsme_register_date',
            'nsme_type',
            'nsme_sector',
            'business_pan_no',
            'business_gst_no'
        ])->pluck('value', 'name')->toArray();

        // All packing‑list IDs for this invoice
        $packIds = explode(',', $invoice->pack_ids);

        // Carton counts _per_ packing list
        $packCartonCounts = PackingListItem::whereIn('packing_list_id', $packIds)
            ->select('packing_list_id', DB::raw('COUNT(DISTINCT carton_name) AS carton_count'))
            ->groupBy('packing_list_id')
            ->pluck('carton_count', 'packing_list_id')
            ->toArray();

        // Grand total cartons across all packing lists
        $totalCartonsInInvoice = array_sum($packCartonCounts);

        // Get invoice GST rate
        $invoiceGstRate = floatval($invoice->gst);

        // Group items by size, aggregate all colors, sum quantities & cartons
        $packedItems = PackingListItem::whereIn('packing_list_id', $packIds)
            ->select([
                'size',
                DB::raw('GROUP_CONCAT(DISTINCT color ORDER BY color SEPARATOR ", ") AS colors'),
                DB::raw('SUM(quantity) AS total_quantity'),
                DB::raw('COUNT(DISTINCT carton_name) AS carton_counts'),
            ])
            ->groupBy('size')
            ->get();

        // Build invoice line‑items array
        $items = [];
        $poUnitPrice = floatval($po_details->vcp);
        $articleInfo = json_decode($po_details->article_info, true) ?: [];

        foreach ($packedItems as $pi) {
            $description = $style = $hsn_code = '';
            $uom = 'PCS';
            $unit_price = $poUnitPrice;

            // Choose a representative color for lookups
            $colors = explode(',', $pi->colors);
            $firstColor = trim($colors[0]);

            // Fetch PoItems by representative color & size
            $itm = PoItems::where('po_id', $po_details->id)
                ->where('size', $pi->size)
                ->where('color', $firstColor)
                ->first();

            // Use invoice GST rate
            $igstRate = $invoiceGstRate;

            switch ($vendor->id) {
                case 1:
                case 5:
                case 6:
                    $description = $articleInfo['Article description'] ?? '';
                    $style = $articleInfo['ARTICLE'] ?? '';
                    if ($itm) {
                        $hsn_code = $itm->hsn_code;
                        $uom = $itm->uom;
                    }
                    break;

                case 4:
                    $poSize = PoSizes::where('po_id', $po_details->id)
                        ->where('size', $pi->size)
                        ->where('color', $firstColor)
                        ->first();
                    if ($poSize) {
                        $unit_price = $poSize->unit_price ?: $unit_price;
                        $hsn_code = $poSize->hsn_code ?? '';
                        $uom = $poSize->uom ?? 'PCS';
                    }
                    if ($itm) {
                        $unit_price = $unit_price ?: floatval($itm->unit_price);
                        $hsn_code = $hsn_code ?: $itm->hsn_code;
                        $uom = $uom ?: $itm->uom;
                        $description = $itm->part_description ?? '';
                        $style = $itm->article_number ?? '';
                    }
                    break;

                case 2:
                case 3:
                    if ($itm) {
                        $unit_price = floatval($itm->unit_price ?? 0);
                        $hsn_code = $itm->hsn_code ?? '';
                        $uom = $itm->uom ?? 'PCS';
                        if ($vendor->id === 2) {
                            $description = $itm->type;
                            $style = $itm->article_number;
                        } else {
                            $description = $itm->style_description;
                            $style = $itm->article_number;
                        }
                    }
                    break;

                default:
                    $description = $articleInfo['Article description'] ?? '';
                    $style = $articleInfo['ARTICLE'] ?? '';
                    break;
            }

            // Fallback hsn_code
            if (empty($hsn_code) && $itm) {
                $hsn_code = $itm->hsn_code;
                $uom = $itm->uom;
            }

            // Compute amounts
            $amount = $pi->total_quantity * $unit_price;
            $discountPct = $vendor->discount ?? 0;
            $discountAmount = ($amount * $discountPct) / 100;
            $taxableValue = $amount - $discountAmount;
            $igstAmount = ($taxableValue * $igstRate) / 100;

            $items[] = [
                'description' => $description,
                'hsn_code' => $hsn_code,
                'style' => $style,
                'colors' => $pi->colors,
                'total_cartons' => $pi->carton_counts,
                'unit' => $uom,
                'size' => $pi->size,
                'qty' => $pi->total_quantity,
                'rate' => $unit_price,
                'amount' => $amount,
                'discount' => $discountAmount,
                'taxable_value' => $taxableValue,
                'igst_rate' => $igstRate,
                'igst_amount' => $igstAmount,
            ];
        }

        $billTo = json_decode($invoice->bill_to_details, true) ?: [];
        $shipTo = json_decode($invoice->ship_to_details, true) ?: [];
        $transpDet = json_decode($invoice->transporter_details, true) ?: [];
        $irnDetails = json_decode($invoice->irn_details, true) ?: [];

        if (!empty($billTo['billed_state'])) {
            $bs = StateMaster::find($billTo['billed_state']);
            $billTo['billed_state_name'] = $bs->name ?? null;
            $billTo['billed_state_code'] = $bs->code ?? null;
        }
        if (!empty($shipTo['shipped_state'])) {
            $ss = StateMaster::find($shipTo['shipped_state']);
            $shipTo['shipped_state_name'] = $ss->name ?? null;
            $shipTo['shipped_state_code'] = $ss->code ?? null;
        }
        if (!empty($transpDet['transport_name'])) {
            $tp = TransportMaster::find($transpDet['transport_name']);
            $transpDet['transport_name_display'] = $tp->name ?? null;
        }

        $invoiceData = [
            'ref_no' => $invoice->ref_no,
            'inv_date' => $invoice->inv_date,
            'po_num' => $po_details->po_num,
            'customer_po_no' => ($vendor->id === 3 && isset($articleInfo['customer_po_no']))
                ? $articleInfo['customer_po_no']
                : '',
        ];

        // return view('invoice.update_pdf', [
        //     'invoice' => $invoiceData,
        //     'po_details' => $po_details,
        //     'vendor' => $vendor,
        //     'state' => $state,
        //     'invoice_item_details' => $items,
        //     'packCartonCounts' => $packCartonCounts,
        //     'totalCartonsInInvoice' => $totalCartonsInInvoice,
        //     'bill_to_details' => $billTo,
        //     'ship_to_details' => $shipTo,
        //     'transporter_details' => $transpDet,
        //     'irn_details' => $irnDetails,
        //     'business_settings' => $businessSettings,
        // ]);

        return Pdf::loadView('invoice.update_pdf', [
            'invoice'              => $invoiceData,
            'po_details'           => $po_details,
            'vendor'               => $vendor,
            'state'                => $state,
            'invoice_item_details' => $items,
            'packCartonCounts' => $packCartonCounts,
            'totalCartonsInInvoice' => $totalCartonsInInvoice,
            'bill_to_details'      => $billTo,
            'ship_to_details'      => $shipTo,
            'transporter_details'  => $transpDet,
            'irn_details'          => $irnDetails,
            'business_settings'    => $businessSettings,
        ])
            ->setOption('isHtml5ParserEnabled', true)
            ->setOption('isRemoteEnabled', true)
            ->setPaper('a4', 'portrait')
            ->stream('invoice.pdf');
    }

    public function master()
    {
        $page_data = [
            'page_title' => "Invoice Master",
            'page_main_title' => "Invoice Master",
            'page_child_title' => "Invoice Master",
            'isSuperAdmin' => $this->isSuperAdmin,
            'InvoiceMaster' => InvoiceMaster::where('status', 0)->orderBy('id', 'desc')->get(),
        ];
        return view('invoice.master', $page_data);
    }

    public function invoice_details_edit(Request $request)
    {
        $invoice = InvoiceMaster::find($request->id);
        $vendor = VendorMaster::find($invoice->vendor_id);
        $states = StateMaster::all();
        $transports = TransportMaster::where('status', 0)->get();

        // Decode JSON data or use empty arrays as fallback
        $billedToDetails = json_decode($invoice->bill_to_details, true) ?? [];
        $shippedToDetails = json_decode($invoice->ship_to_details, true) ?? [];
        $irnDetails = json_decode($invoice->irn_details, true) ?? [];
        $transportDetails = json_decode($invoice->transporter_details, true) ?? [];

        if (empty($billedToDetails)) {
            $billedToDetails = [
                'billed_legal_name' => $vendor->billing_legal_name ?? '',
                'billed_address_1' => $vendor->billing_address_1 ?? '',
                'billed_address_2' => $vendor->billing_address_2 ?? '',
                'billed_city' => $vendor->billing_city_town_village ?? '',
                'billed_state' => $vendor->billing_state_id ?? '',
                'billed_gst_no' => $vendor->billing_gst_no ?? '',
                'billed_pan_no' => $vendor->billing_pan_no ?? '',
                'billed_pincode' => $vendor->billing_pincode ?? '',
                'billed_gst_type' => $vendor->billing_gst_type ?? '',
            ];
        }

        if (empty($shippedToDetails)) {
            $shippedToDetails = [
                'shipped_legal_name' => $vendor->shipping_legal_name ?? '',
                'shipped_address_1' => $vendor->shipping_address_1 ?? '',
                'shipped_address_2' => $vendor->shipping_address_2 ?? '',
                'shipped_city' => $vendor->shipping_city_town_village ?? '',
                'shipped_state' => $vendor->shipping_state_id ?? '',
                'shipped_gst_no' => $vendor->shipping_gst_no ?? '',
                'shipped_pan_no' => $vendor->shipping_pan_no ?? '',
                'shipped_pincode' => $vendor->shipping_pincode ?? '',
                'shipped_place_of_supply' => $vendor->shipping_place_supply ?? '',
                //'shipped_distance' => $vendor->shipping_distance ?? '',
            ];
        }
        if (isset($transportDetails['transport_distance']) && $transportDetails['transport_distance'] != "") {
            $transportDetails['transport_distance'] = $transportDetails['transport_distance'] ?? '';
        } else {
            $transportDetails['transport_distance'] = $vendor->shipping_distance ?? '';
        }

        if ($invoice['ref_no'] != "") {

            $transportDetails['transport_doc_no'] = $this->extractSlashNumbers($invoice['ref_no']);
        } else {
            $transportDetails['transport_doc_no'] = "";
        }






        // print_r($invoice['ref_no']);
        return view('invoice.invoice_details', compact(
            'invoice',
            'vendor',
            'states',
            'transports',
            'billedToDetails',
            'shippedToDetails',
            'irnDetails',
            'transportDetails'
        ));
    }

    public function invoice_details_update(Request $request)
    {
        try {
            $invoice = InvoiceMaster::find($request->id);

            // Prepare billing details JSON
            $billedToDetails = [
                'billed_legal_name' => $request->billed_legal_name,
                'billed_address_1' => $request->billed_address_1,
                'billed_address_2' => $request->billed_address_2,
                'billed_city' => $request->billed_city,
                'billed_state' => $request->billed_state,
                'billed_gst_no' => $request->billed_gst_no,
                'billed_pan_no' => $request->billed_pan_no,
                'billed_pincode' => $request->billed_pincode,
                'billed_gst_type' => $request->billed_gst_type,
            ];

            // Prepare shipping details JSON
            $shippedToDetails = [
                'shipped_legal_name' => $request->shipped_legal_name,
                'shipped_address_1' => $request->shipped_address_1,
                'shipped_address_2' => $request->shipped_address_2,
                'shipped_city' => $request->shipped_city,
                'shipped_state' => $request->shipped_state,
                'shipped_gst_no' => $request->shipped_gst_no,
                'shipped_pan_no' => $request->shipped_pan_no,
                'shipped_pincode' => $request->shipped_pincode,
                'shipped_place_of_supply' => $request->shipped_place_of_supply,
            ];

            // Prepare IRN details JSON
            $irnDetails = [
                'irn_no' => $request->irn_no,
                'acknowledgment_no' => $request->acknowledgment_no,
                'document_no' => $request->document_no,
                'supply_type_code' => $request->supply_type_code,
                'eway_bill_no' => $request->eway_bill_no,
                'eway_bill_date' => $request->eway_bill_date,
                'acknowledgment_date' => $request->acknowledgment_date,
                'document_date' => $request->document_date,
                'reverse_charge' => $request->reverse_charge,
                'preceeding_document_no' => $request->preceeding_document_no,
                'preceeding_document_date' => $request->preceeding_document_date,
            ];

            // Prepare transport details JSON
            $transportDetails = [
                'transport_name' => $request->transport_name,
                'mode_of_transport' => $request->mode_of_transport,
                'transport_vehicle_no' => $request->transport_vehicle_no,
                'transport_distance' => $request->transport_distance,
                'transport_date_time' => $request->transport_date_time,
                'transport_doc_no' => $request->transport_doc_no,
                'transport_vehicle_type' => "Regular",

            ];

            $invoice->update([
                'ref_no' => $request->invoice_no,
                'inv_date' => $request->invoice_date,
                'bill_to_details' => json_encode($billedToDetails),
                'ship_to_details' => json_encode($shippedToDetails),
                'irn_details' => json_encode($irnDetails),
                'transporter_details' => json_encode($transportDetails),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Invoice Details updated successfully!',
                'vendor_id' => $request->vendor_id
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ]);
        }
    }

    public  function extractSlashNumbers(string $code): string
    {
        // Split into up to 3 parts and pad missing with a space
        $parts = explode('/', $code, 3);
        $parts = array_pad($parts, 3, ' ');

        // Get only digits for the middle part (e.g., 250277)
        $firstNum  = (preg_match('/\d+/', $parts[1], $m1)) ? $m1[0] : ' ';

        // Get digits or digit-range (e.g., 25-26) for the last part
        //$secondNum = (preg_match('/\d+(?:-\d+)?/', $parts[2], $m2)) ? $m2[0] : ' ';

        return $firstNum;
    }

    public function grn_details_edit(Request $request)
    {
        $invoice = InvoiceMaster::with(['po.vendor'])->find($request->id);
        $GrnDetails = json_decode($invoice->grn_details, true) ?? [];

        // Get invoice data for calculations
        $invoiceData = $this->getInvoiceCalculationData($invoice);

        return view('invoice.grn_details', compact(
            'invoice',
            'GrnDetails',
            'invoiceData'
        ));
    }

    private function getInvoiceCalculationData($invoice)
    {
        $po_details = $invoice->po;
        $vendor = $po_details->vendor;

        // Get all packing list IDs for this invoice
        $packIds = explode(',', $invoice->pack_ids);

        // Get total quantity from packing lists
        $totalInvoiceQty = PackingListItem::whereIn('packing_list_id', $packIds)
            ->sum('quantity');

        // Get GST rate from invoice
        $gstRate = floatval($invoice->gst);

        // Get vendor discount
        $discountPct = $vendor->discount ?? 0;

        // Calculate unit price based on vendor-specific logic (same as generateInvoice)
        $poUnitPrice = floatval($po_details->vcp);
        $articleInfo = json_decode($po_details->article_info, true) ?: [];

        // Group items by size to get representative items for unit price calculation
        $packedItems = PackingListItem::whereIn('packing_list_id', $packIds)
            ->select([
                'size',
                'color',
                DB::raw('SUM(quantity) AS total_quantity'),
            ])
            ->groupBy(['size', 'color'])
            ->get();

        $weightedUnitPrice = 0;
        $totalCalculatedQty = 0;

        foreach ($packedItems as $pi) {
            $unit_price = $poUnitPrice; // Default unit price

            // Get PoItems for this specific color and size
            $itm = PoItems::where('po_id', $po_details->id)
                ->where('size', $pi->size)
                ->where('color', $pi->color)
                ->first();

            // Apply vendor-specific logic for unit price
            switch ($vendor->id) {
                case 1:
                case 5:
                case 6:
                    // Use PO VCP price (already set as default)
                    break;

                case 4:
                    $poSize = PoSizes::where('po_id', $po_details->id)
                        ->where('size', $pi->size)
                        ->where('color', $pi->color)
                        ->first();
                    if ($poSize) {
                        $unit_price = $poSize->unit_price ?: $unit_price;
                    }
                    if ($itm) {
                        $unit_price = $unit_price ?: floatval($itm->unit_price);
                    }
                    break;

                case 2:
                case 3:
                    if ($itm) {
                        $unit_price = floatval($itm->unit_price ?? 0);
                    }
                    break;

                default:
                    // Use PO VCP price (already set as default)
                    break;
            }

            // Calculate weighted unit price
            $itemQty = $pi->total_quantity;
            $weightedUnitPrice += ($unit_price * $itemQty);
            $totalCalculatedQty += $itemQty;
        }

        // Calculate average unit price across all items
        $averageUnitPrice = $totalCalculatedQty > 0 ? ($weightedUnitPrice / $totalCalculatedQty) : $poUnitPrice;

        // Apply discount to get final unit price
        $discountAmount = ($averageUnitPrice * $discountPct) / 100;
        $unitPriceAfterDiscount = $averageUnitPrice - $discountAmount;

        return [
            'total_invoice_qty' => $totalInvoiceQty,
            'unit_price_after_discount' => $unitPriceAfterDiscount,
            'unit_price_before_discount' => $averageUnitPrice,
            'discount_percentage' => $discountPct,
            'discount_amount_per_unit' => $discountAmount,
            'gst_rate' => $gstRate,
            'vendor_id' => $vendor->id,
            'vendor_name' => $vendor->name
        ];
    }

    public function grn_details_update(Request $request)
    {
        try {
            $invoice = InvoiceMaster::find($request->id);

            // Prepare GRN details JSON with all fields
            $GrnDetails = [
                'asn_no' => $request->asn_no,
                'asn_date' => $request->asn_date,
                'lr_no' => $request->lr_no,
                'transport_name' => $request->transport_name,
                'transporter_per_cost' => $request->transporter_per_cost,
                'dispatched_date' => $request->dispatched_date,
                'reached_date' => $request->reached_date,
                'pod_date' => $request->pod_date,
                'grn_date' => $request->grn_date,
                'grn_qty' => $request->grn_qty,
                'short_inv_vs_grn' => $request->short_inv_vs_grn,
                'discrepancy' => $request->discrepancy,
                'remarks' => $request->remarks,
                'transport_cost_total' => $request->transport_cost_total,
                'debit_note_value' => $request->debit_note_value,
                'debit_note_tax_rate' => $request->debit_note_tax_rate,
                'debit_note_tax_amount' => $request->debit_note_tax_amount,
                'total_debit_note_value' => $request->total_debit_note_value,
                'business_head' => $request->business_head,
                'grn_status' => $request->grn_status,
                'status' => $request->status,
                'week' => $request->week,
                'fl_clear_date' => $request->fl_clear_date,
            ];

            $invoice->update([
                'grn_details' => json_encode($GrnDetails),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'GRN Details updated successfully!',
                'invoice_id' => $request->id
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ]);
        }
    }
}
