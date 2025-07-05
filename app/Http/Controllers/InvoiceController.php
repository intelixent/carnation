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
        $packed_lists = PackingListMaster::where(array('po_id' => $poId, 'pack_status' => 1))->withCount('items')->get();
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
        $selectedpackids = $request->selectedpackids;
        $selected_po = $request->selected_po;
        $selected_vendor_id = $request->selected_vendor_id;

        $prefixSetting = PrefixSetting::where('id', 2)->first();
        try {

            $currentNumber = $prefixSetting->number;
            $inv_no = $prefixSetting->format . str_pad($currentNumber, 6, '0', STR_PAD_LEFT) . '/' . $prefixSetting->suffix;
            $InvoiceMaster = InvoiceMaster::create([
                'ref_no' => $inv_no,
                'inv_date' => date('Y-m-d'),
                'bill_to_details' => "",
                'ship_to_details' => "",
                'po_id' => $selected_po,
                'pack_ids' => implode(',', $selectedpackids),
                'vendor_id' => $selected_vendor_id,
                'created_by' => auth()->user()->id,
                'created_at' => now(),
            ]);
            //$ids = explode(',', $selectedpackids);
            PackingListMaster::whereIn('id', $selectedpackids)->update(['pack_status' => 2]);


            return response()->json([
                'success' => true,
                'message' => 'Invoice Generate successfully.',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while adding the vendor: ' . $e->getMessage()
            ]);
        }
    }

    public function generateInvoice(Request $request)
    {
        // Fetch invoice and related PO, vendor, and packed items
        $invoice     = InvoiceMaster::with(['po.vendor.state'])->findOrFail($request->id);
        $po_details  = $invoice->po;
        $vendor      = $po_details->vendor;
        $state       = $vendor->state;

        // Get packed items
        $packIds     = explode(',', $invoice->pack_ids);
        $packedItems = PackingListItem::whereIn('packing_list_id', $packIds)
            ->select('po_item_id', 'size', 'color', DB::raw('SUM(quantity) as total_quantity'), DB::raw('COUNT(DISTINCT id) as carton_counts'))
            ->groupBy('po_item_id', 'size', 'color')
            ->get();

        // Fetch VCP (unit price) from PO master
        $unitPriceFromPO = floatval($po_details->vcp);

        // Build invoice item details
        $articleInfo = json_decode($po_details->article_info, true);
        $items       = [];

        foreach ($packedItems as $pi) {
            // Initialize default values
            $description = '';
            $style = '';
            $hsn_code = '';
            $color = $pi->color ?? '';
            $uom = 'PCS';

            // Always use VCP from PO as unit price
            $unit_price = $unitPriceFromPO;

            // Determine description and style (existing logic unchanged)
            $itemModel = PoItems::find($pi->po_item_id);
            if ($itemModel) {
                $hsn_code = $itemModel->hsn_code ?? '';
                $uom = $itemModel->uom ?? 'PCS';

                // Determine description based on vendor ID
                switch ($vendor->id) {
                    case 1:
                    case 5:
                    case 6:
                        $description = $articleInfo['Article description'] ?? '';
                        $style = $articleInfo['ARTICLE'] ?? '';
                        break;
                    case 2:
                        $description = $itemModel->type ?? '';
                        $style = $itemModel->article_number ?? '';
                        break;
                    case 3:
                        $description = $itemModel->style_description ?? '';
                        $style = $itemModel->article_number ?? '';
                        break;
                    default:
                        $description = $articleInfo['Article description'] ?? '';
                        $style = $articleInfo['ARTICLE'] ?? '';
                        break;
                }
            }

            // Calculate amount
            $amount = $pi->total_quantity * $unit_price;

            // Get discount percentage from vendor_master
            $discountPercentage = $vendor->discount_percentage ?? 0;

            // Calculate discount amount
            $discountAmount = ($amount * $discountPercentage) / 100;

            // Calculate taxable value (amount - discount)
            $taxableValue = $amount - $discountAmount;

            // Calculate IGST (5% of taxable value)
            $igstRate = 5.00;
            $igstAmount = ($taxableValue * $igstRate) / 100;

            $items[] = [
                'description'    => $description,
                'hsn_code'       => $hsn_code,
                'style'          => $style,
                'color'          => $color,
                'total_cartons'  => $pi->carton_counts,
                'unit'           => $uom,
                'size'           => $pi->size,
                'qty'            => $pi->total_quantity,
                'rate'           => $unit_price,
                'amount'         => $amount,
                'discount'       => $discountAmount,
                'taxable_value'  => $taxableValue,
                'igst_rate'      => $igstRate,
                'igst_amount'    => $igstAmount,
            ];
        }

        // Parse JSON details
        $billToDetails      = json_decode($invoice->bill_to_details, true);
        $shipToDetails      = json_decode($invoice->ship_to_details, true);
        $transporterDetails = json_decode($invoice->transporter_details, true);
        $irnDetails         = json_decode($invoice->irn_details, true);

        $billedState = null;
        $shippedState = null;
        $transporter = null;

        if (isset($billToDetails['billed_state'])) {
            $billedState = StateMaster::find($billToDetails['billed_state']);
        }

        if (isset($shipToDetails['shipped_state'])) {
            $shippedState = StateMaster::find($shipToDetails['shipped_state']);
        }

        if (isset($transporterDetails['transport_name'])) {
            $transporter = TransportMaster::find($transporterDetails['transport_name']);
        }

        if ($billedState) {
            $billToDetails['billed_state_name'] = $billedState->name;
            $billToDetails['billed_state_code'] = $billedState->code;
        }

        if ($shippedState) {
            $shipToDetails['shipped_state_name'] = $shippedState->name;
            $shipToDetails['shipped_state_code'] = $shippedState->code;
        }

        if ($transporter) {
            $transporterDetails['transport_name_display'] = $transporter->name;
        }

        // Pass data to PDF view
        return Pdf::loadView('invoice.update_pdf', [
            'invoice'              => $invoice,
            'po_details'           => $po_details,
            'vendor'               => $vendor,
            'state'                => $state,
            'invoice_item_details' => $items,
            'bill_to_details'      => $billToDetails,
            'ship_to_details'      => $shipToDetails,
            'transporter_details'  => $transporterDetails,
            'irn_details'          => $irnDetails,
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
            'InvoiceMaster' => InvoiceMaster::where('status', 0)->get(),
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
                'billed_legal_name' => $vendor->name ?? '',
                'billed_address_1' => $vendor->address ?? '',
                'billed_address_2' => '',
                'billed_city' => '',
                'billed_state' => $vendor->state_id ?? '',
                'billed_gst_no' => $vendor->gst_no ?? '',
                'billed_pan_no' => $vendor->pan_no ?? '',
                'billed_pincode' => '',
                'billed_gst_type' => '',
            ];
        }

        if (empty($shippedToDetails)) {
            $shippedToDetails = [
                'shipped_legal_name' => $vendor->name ?? '',
                'shipped_address_1' => $vendor->address ?? '',
                'shipped_address_2' => '',
                'shipped_city' => '',
                'shipped_state' => $vendor->state_id ?? '',
                'shipped_gst_no' => $vendor->gst_no ?? '',
                'shipped_pan_no' => $vendor->pan_no ?? '',
                'shipped_pincode' => '',
                'shipped_place_of_supply' => '',
            ];
        }

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
            ];

            $invoice->update([
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
}
