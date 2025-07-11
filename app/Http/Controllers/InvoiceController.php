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
        ]);

        try {
            $invoice = InvoiceMaster::create([
                'ref_no'           => $data['invoice_no'],
                'inv_date'         => $data['invoice_date'],
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

            return response()->json([
                'success' => true,
                'message' => "Invoice {$invoice->ref_no} saved successfully."
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to save invoice: ' . $e->getMessage()
            ], 500);
        }
    }

    public function generateInvoice(Request $request)
    {
        $invoice    = InvoiceMaster::with(['po.vendor.state'])
            ->findOrFail($request->id);
        $po_details = $invoice->po;
        $vendor     = $po_details->vendor;
        $state      = $vendor->state;

        $packIds     = explode(',', $invoice->pack_ids);
        $packedItems = PackingListItem::whereIn('packing_list_id', $packIds)
            ->select(
                'po_item_id',
                'size',
                'color',
                DB::raw('SUM(quantity) as total_quantity'),
                DB::raw('COUNT(DISTINCT id) as carton_counts')
            )
            ->groupBy('po_item_id', 'size', 'color')
            ->get();
        $totalCartonsInInvoice = PackingListItem::whereIn('packing_list_id', $packIds)->distinct()->count('carton_name');
        $poUnitPrice   = floatval($po_details->vcp);
        $articleInfo   = json_decode($po_details->article_info, true) ?: [];
        $items         = [];

        foreach ($packedItems as $pi) {
            $description = '';
            $style       = '';
            $hsn_code    = '';
            $uom         = 'PCS';
            $color       = $pi->color;

            if (in_array($vendor->id, [1, 5, 6])) {
                // Vendors 1,5,6 → PO master price + article_info + PoItems for HSN/UOM
                $unit_price  = $poUnitPrice;
                $description = $articleInfo['Article description'] ?? '';
                $style       = $articleInfo['ARTICLE']             ?? '';

                // try getting HSN/UOM from the PoItems record
                if ($pi->po_item_id) {
                    $itm = PoItems::find($pi->po_item_id);
                    if ($itm) {
                        $hsn_code = $itm->hsn_code ?? '';
                        $uom      = $itm->uom      ?? 'PCS';
                    }
                }
            } elseif ($vendor->id === 4) {
                // Vendor 4 (Benetton): PoSizes → PoItems
                $poSize = PoSizes::where('po_id', $po_details->id)
                    ->where('color', $pi->color)
                    ->where('size', $pi->size)
                    ->first();

                if ($poSize) {
                    $unit_price = $poSize->unit_price ?? 0;
                    $hsn_code   = $poSize->hsn_code   ?? '';
                    $uom        = $poSize->uom        ?? 'PCS';
                }

                $itm = PoItems::where('po_id', $po_details->id)
                    ->where('color', $pi->color)
                    ->first();
                if ($itm) {
                    $unit_price  = $unit_price ?: ($itm->unit_price ?? 0);
                    $hsn_code    = $hsn_code   ?: ($itm->hsn_code   ?? '');
                    $uom         = $uom        ?: ($itm->uom        ?? 'PCS');
                    $description = $itm->part_description ?? '';
                    $style       = $itm->article_number   ?? '';
                }
            } else {
                // Vendors 2,3 (or others): PoItems price + specific description/style
                $itm = PoItems::find($pi->po_item_id);
                if ($itm) {
                    $unit_price = floatval($itm->unit_price ?? 0);
                    $hsn_code   = $itm->hsn_code     ?? '';
                    $uom        = $itm->uom          ?? 'PCS';

                    switch ($vendor->id) {
                        case 2:
                            $description = $itm->type           ?? '';
                            $style       = $itm->article_number ?? '';
                            break;
                        case 3:
                            $description = $itm->style_description ?? '';
                            $style       = $itm->article_number     ?? '';
                            break;
                        default:
                            $description = $articleInfo['Article description'] ?? '';
                            $style       = $articleInfo['ARTICLE']             ?? '';
                    }
                } else {
                    // fallback to PO master price & article_info
                    $unit_price  = $poUnitPrice;
                    $description = $articleInfo['Article description'] ?? '';
                    $style       = $articleInfo['ARTICLE']             ?? '';
                }
            }

            $amount         = $pi->total_quantity * $unit_price;
            $discountPct    = $vendor->discount_percentage ?? 0;
            $discountAmount = ($amount * $discountPct) / 100;
            $taxableValue   = $amount - $discountAmount;
            $igstRate       = 5.00;
            $igstAmount     = ($taxableValue * $igstRate) / 100;

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

        // Decode any JSON details
        $billTo      = json_decode($invoice->bill_to_details, true)      ?: [];
        $shipTo      = json_decode($invoice->ship_to_details, true)      ?: [];
        $transpDet   = json_decode($invoice->transporter_details, true)  ?: [];
        $irnDetails  = json_decode($invoice->irn_details, true)          ?: [];

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

        return Pdf::loadView('invoice.update_pdf', [
            'invoice'              => $invoice,
            'po_details'           => $po_details,
            'vendor'               => $vendor,
            'state'                => $state,
            'invoice_item_details' => $items,
            'totalCartonsInInvoice' => $totalCartonsInInvoice,
            'bill_to_details'      => $billTo,
            'ship_to_details'      => $shipTo,
            'transporter_details'  => $transpDet,
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
