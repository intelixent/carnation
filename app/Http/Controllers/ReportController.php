<?php

namespace App\Http\Controllers;

use App\Http\Controllers\BaseController;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use App\Models\PoItems;
use App\Models\PoSizes;
use Maatwebsite\Excel\Facades\Excel;
use App\Models\PackingListItem;
use App\Models\InvoiceMaster;
use App\Exports\DispatchExport;
use App\Models\VendorMaster;
use App\Models\PackingListConfigItem;
use App\Models\PackingListMaster;
use App\Models\PoMaster;
use App\Exports\SummaryExport;
use App\Exports\SizeWiseExport;

class ReportController extends BaseController
{
    protected $isSuperAdmin;

    public function __construct()
    {
        parent::__construct();
        $this->middleware('auth');
        $this->isSuperAdmin = request()->attributes->get('isSuperAdmin', false);

        if (!$this->isSuperAdmin) {
        }
    }

    public function dispatch_status_report_master()
    {
        $page_data = [
            'page_title' => "Report",
            'page_main_title' => "Dispatch Status",
            'isSuperAdmin' => $this->isSuperAdmin,
        ];

        return view('report.dispatch_status_master', $page_data);
    }

    public function dispatch_status_report_table(Request $request)
    {
        $request->validate([
            'from_date' => 'required|date',
            'to_date' => 'required|date|after_or_equal:from_date'
        ]);

        $from_date = $request->from_date;
        $to_date = $request->to_date;

        // Query invoices with date range filtering
        $invoices = InvoiceMaster::with(['po', 'vendor'])
            ->where(function ($query) use ($from_date, $to_date) {
                $query->where(function ($q) use ($from_date, $to_date) {
                    // For Y-m-d format (2025-07-31)
                    $q->whereBetween('inv_date', [$from_date, $to_date]);
                })
                    ->orWhere(function ($q) use ($from_date, $to_date) {
                        // For d-m-Y format (31-07-2025)
                        $q->whereRaw("STR_TO_DATE(inv_date, '%d-%m-%Y') BETWEEN ? AND ?", [
                            $from_date,
                            $to_date
                        ]);
                    })
                    ->orWhere(function ($q) use ($from_date, $to_date) {
                        // For d.m.Y format (31.07.2025)
                        $q->whereRaw("STR_TO_DATE(inv_date, '%d.%m.%Y') BETWEEN ? AND ?", [
                            $from_date,
                            $to_date
                        ]);
                    });
            })
            ->orderBy('inv_date', 'desc')
            ->get();

        // Calculate totals for each invoice
        $invoiceData = [];
        foreach ($invoices as $invoice) {
            $billToDetails = json_decode($invoice->bill_to_details, true) ?: [];
            $packIds = explode(',', $invoice->pack_ids);
            $po_details = $invoice->po;
            $vendor = $invoice->vendor;

            // Get invoice GST rate
            $invoiceGstRate = floatval($invoice->gst);

            // Group items by size and color for calculation (similar to generateInvoice method)
            $packedItems = PackingListItem::whereIn('packing_list_id', $packIds)
                ->select([
                    'size',
                    'color',
                    DB::raw('SUM(quantity) AS total_quantity'),
                ])
                ->groupBy(['size', 'color'])
                ->get();

            $totalQty = 0;
            $totalAmount = 0;
            $totalDiscountAmount = 0;
            $totalTaxableValue = 0;
            $totalGstAmount = 0;
            $totalFinalAmount = 0;
            $weightedRateSum = 0;

            // Get base unit price from PO
            $poUnitPrice = $po_details ? floatval($po_details->vcp) : 0;
            $articleInfo = $po_details ? (json_decode($po_details->article_info, true) ?: []) : [];

            foreach ($packedItems as $pi) {
                $unit_price = $poUnitPrice;
                $description = '';
                $style = '';

                // Get PoItems for this specific color and size
                $itm = null;
                if ($po_details) {
                    $itm = PoItems::where('po_id', $po_details->id)
                        ->where('size', $pi->size)
                        ->where('color', $pi->color)
                        ->first();
                }

                // Apply vendor-specific pricing logic (matching generateInvoice method)
                if ($vendor) {
                    switch ($vendor->id) {
                        case 1:
                        case 5:
                        case 6:
                            $description = $articleInfo['Article description'] ?? '';
                            $style = $articleInfo['ARTICLE'] ?? '';
                            // Use PO unit price (already set)
                            break;

                        case 4:
                            $poSize = PoSizes::where('po_id', $po_details->id)
                                ->where('size', $pi->size)
                                ->where('color', $pi->color)
                                ->first();
                            if ($poSize) {
                                $unit_price = floatval($poSize->unit_price) ?: $unit_price;
                            }
                            if ($itm) {
                                $unit_price = $unit_price ?: floatval($itm->unit_price ?? 0);
                                $description = $itm->part_description ?? '';
                                $style = $itm->article_number ?? '';
                            }
                            break;

                        case 2:
                            if ($itm) {
                                $unit_price = floatval($itm->unit_price ?? 0);
                                $description = $itm->type ?? '';
                                $style = $itm->article_number ?? '';
                            }
                            break;

                        case 3:
                            if ($itm) {
                                // For vendor ID 3, use unit_price from po_items table
                                $unit_price = floatval($itm->unit_price ?? 0);
                                $description = $itm->style_description ?? '';
                                $style = $itm->article_number ?? '';
                            }
                            break;

                        default:
                            $description = $articleInfo['Article description'] ?? '';
                            $style = $articleInfo['ARTICLE'] ?? '';
                            break;
                    }
                }

                // Fallback: If unit_price is still 0 or empty, try to get from po_items
                if (empty($unit_price) && $itm) {
                    $unit_price = floatval($itm->unit_price ?? 0);
                }

                // Calculate amounts for this item
                $itemAmount = $pi->total_quantity * $unit_price;
                $discountPct = $vendor ? (floatval($vendor->discount) ?? 0) : 0;
                $discountAmount = ($itemAmount * $discountPct) / 100;
                $taxableValue = $itemAmount - $discountAmount;
                $gstAmount = ($taxableValue * $invoiceGstRate) / 100;
                $itemTotalAmount = $taxableValue + $gstAmount;

                // Add to totals
                $totalQty += $pi->total_quantity;
                $totalAmount += $itemAmount;
                $totalDiscountAmount += $discountAmount;
                $totalTaxableValue += $taxableValue;
                $totalGstAmount += $gstAmount;
                $totalFinalAmount += $itemTotalAmount;

                // Calculate weighted rate sum (unit_price * quantity)
                $weightedRateSum += ($unit_price * $pi->total_quantity);
            }

            // Calculate effective rate (weighted average)
            $effectiveRate = $totalQty > 0 ? $weightedRateSum / $totalQty : $poUnitPrice;

            // If no packed items found, calculate with basic method
            if ($packedItems->isEmpty()) {
                $totalQty = PackingListItem::whereIn('packing_list_id', $packIds)
                    ->sum('quantity');

                if ($vendor && $vendor->id == 3 && $po_details) {
                    $avgUnitPrice = PoItems::where('po_id', $po_details->id)
                        ->whereNotNull('unit_price')
                        ->where('unit_price', '>', 0)
                        ->avg(DB::raw('CAST(unit_price AS DECIMAL(10,2))'));
                    if ($avgUnitPrice) {
                        $effectiveRate = floatval($avgUnitPrice);
                    } else {
                        $effectiveRate = $poUnitPrice;
                    }
                } else {
                    $effectiveRate = $poUnitPrice;
                }

                $totalAmount = $totalQty * $effectiveRate;
                $discountPct = $vendor ? (floatval($vendor->discount) ?? 0) : 0;
                $totalDiscountAmount = ($totalAmount * $discountPct) / 100;
                $totalTaxableValue = $totalAmount - $totalDiscountAmount;
                $totalGstAmount = ($totalTaxableValue * $invoiceGstRate) / 100;
                $totalFinalAmount = $totalTaxableValue + $totalGstAmount;
            }

            $invoiceData[] = [
                'invoice' => $invoice,
                'billing_legal_name' => $billToDetails['billed_legal_name'] ?? 'N/A',
                'total_qty' => $totalQty,
                'rate' => $effectiveRate,
                'amount' => $totalAmount,
                'discount_amount' => $totalDiscountAmount,
                'taxable_value' => $totalTaxableValue,
                'gst_rate' => $invoiceGstRate,
                'gst_amount' => $totalGstAmount,
                'total_amount' => $totalFinalAmount,
                'vendor_id' => $vendor ? $vendor->id : null,
                'vendor_name' => $vendor ? $vendor->name : 'N/A'
            ];
        }

        $data = [
            'invoices' => $invoiceData,
            'from_date' => $from_date,
            'to_date' => $to_date
        ];

        return view('report.dispatch_status_table', $data);
    }

    public function dispatch_status_report_excel_download(Request $request)
    {
        $request->validate([
            'from_date' => 'required|date',
            'to_date' => 'required|date|after_or_equal:from_date'
        ]);

        $from_date = $request->query('from_date');
        $to_date = $request->query('to_date');
        $selected = array_map('intval', (array) $request->query('selected_invoice', []));

        // Generate filename
        $filename = "Dispatch-Report-{$from_date}-{$to_date}.xlsx";

        return Excel::download(new DispatchExport($from_date, $to_date, $selected), $filename);
    }

    public function dispatch_status_report_edit(Request $request)
    {
        $invoice = InvoiceMaster::with(['po.vendor'])->find($request->id);
        $GrnDetails = json_decode($invoice->grn_details, true) ?? [];

        // Get transport details from transporter_details
        $transporterDetails = json_decode($invoice->transporter_details, true) ?? [];

        // Get transport name from transport_master if transport_name exists
        $transportName = '';
        if (!empty($transporterDetails['transport_name'])) {
            $transport = \App\Models\TransportMaster::find($transporterDetails['transport_name']);
            $transportName = $transport ? $transport->name : '';
        }

        $dispatchedDate = '';
        if (!empty($transporterDetails['transport_date_time'])) {
            try {
                $date = \DateTime::createFromFormat('d-M-y', $transporterDetails['transport_date_time']);
                if ($date) {
                    $dispatchedDate = $date->format('Y-m-d');
                }
            } catch (\Exception $e) {
            }
        }

        // Get invoice data for calculations
        $invoiceData = $this->getInvoiceCalculationData($invoice);

        return view('report.dispatch_status_update', compact(
            'invoice',
            'GrnDetails',
            'invoiceData',
            'transportName',
            'dispatchedDate'
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

    public function dispatch_status_report_update(Request $request)
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
                'unit_no' => $request->unit_no,
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

    public function daily_packing_report_master()
    {
        $page_data = [
            'page_title' => "Report",
            'page_main_title' => "Dispatch Status",
            'isSuperAdmin' => $this->isSuperAdmin,
        ];

        return view('report.daily_packing_master', $page_data);
    }

    public function daily_packing_report_table(Request $request)
    {
        $date = $request->date;

        // Get all PO IDs that have packing items created on the selected date
        $poIds = PackingListItem::whereDate('created_at', $date)
            ->distinct()
            ->pluck('packing_list_id')
            ->toArray();

        // Get packing list masters for these IDs
        $packingListMasters = PackingListMaster::whereIn('id', $poIds)->get();

        $packingListIds = $packingListMasters->pluck('po_id')->unique()->toArray();

        // Get summary data
        $summaryData = [];
        foreach ($packingListIds as $po_id) {
            $po = PoMaster::find($po_id);
            if (!$po) continue;

            // Get packing table number from PackingListMaster
            $packingListMaster = $packingListMasters->where('po_id', $po_id)->first();
            $packingTableNo = $packingListMaster ? $packingListMaster->packing_table_no : 'N/A';

            // Get total PO qty from config items
            $poQty = PackingListConfigItem::where('po_id', $po_id)
                ->sum('po_qty');

            // Get total ORS qty (pack_qty) from config items
            $orsQty = PackingListConfigItem::where('po_id', $po_id)
                ->sum('pack_qty');

            // Get packed qty from packing list items created on the date
            $packedQty = PackingListItem::whereDate('created_at', $date)
                ->whereHas('packingList', function ($q) use ($po_id) {
                    $q->where('po_id', $po_id);
                })
                ->sum('quantity');

            // Calculate yet to pack
            $yetToPack = $orsQty - $packedQty;

            $summaryData[] = [
                'vendor' => $po->vendor->name,
                'packing_table_no' => $packingTableNo,
                'job_no' => $po->po_job_num,
                'po_qty' => $poQty,
                'ors_qty' => $orsQty,
                'packed' => $packedQty,
                'yet_to_pack' => $yetToPack,
                'po_id' => $po_id
            ];
        }

        // Get size-wise data for all vendors
        $sizeWiseData = [];

        // Get all vendors that have packing data on the selected date
        $vendorsWithData = PackingListItem::whereDate('created_at', $date)
            ->distinct()
            ->pluck('vendor_id')
            ->toArray();

        foreach ($vendorsWithData as $vendor_id) {
            $vendor = VendorMaster::find($vendor_id);
            if (!$vendor) continue;

            // Get PO IDs for this vendor
            $vendorPoIds = PackingListItem::whereDate('created_at', $date)
                ->where('vendor_id', $vendor_id)
                ->distinct()
                ->pluck('packing_list_id')
                ->toArray();

            $vendorPackingListMasters = PackingListMaster::whereIn('id', $vendorPoIds)
                ->where('vendor_id', $vendor_id)
                ->get();

            $vendorPackingListIds = $vendorPackingListMasters->pluck('po_id')->unique()->toArray();

            foreach ($vendorPackingListIds as $po_id) {
                $po = PoMaster::find($po_id);
                if (!$po) continue;

                // Get packing table number from PackingListMaster
                $packingListMaster = $vendorPackingListMasters->where('po_id', $po_id)->first();
                $packingTableNo = $packingListMaster ? $packingListMaster->packing_table_no : 'N/A';

                // Get all unique colors that were ACTUALLY PACKED on this date for this PO and vendor
                $colors = PackingListItem::whereDate('created_at', $date)
                    ->where('vendor_id', $vendor_id)
                    ->whereHas('packingList', function ($q) use ($po_id) {
                        $q->where('po_id', $po_id);
                    })
                    ->distinct()
                    ->pluck('color')
                    ->toArray();

                foreach ($colors as $color) {
                    // Get total PO qty for this color
                    $poQty = PackingListConfigItem::where('po_id', $po_id)
                        ->where('vendor_id', $vendor_id)
                        ->where('color', $color)
                        ->sum('po_qty');

                    // Get total ORS qty for this color
                    $orsQty = PackingListConfigItem::where('po_id', $po_id)
                        ->where('vendor_id', $vendor_id)
                        ->where('color', $color)
                        ->sum('pack_qty');

                    // Get size-wise packed quantities for this color
                    $sizeWisePacked = PackingListItem::whereDate('created_at', $date)
                        ->where('vendor_id', $vendor_id)
                        ->where('color', $color)
                        ->whereHas('packingList', function ($q) use ($po_id) {
                            $q->where('po_id', $po_id);
                        })
                        ->selectRaw('size, SUM(quantity) as qty')
                        ->groupBy('size')
                        ->pluck('qty', 'size')
                        ->toArray();

                    // Calculate total packed for this color
                    $totalPacked = array_sum($sizeWisePacked);

                    // Calculate yet to pack
                    $yetToPack = $orsQty - $totalPacked;

                    $sizeWiseData[] = [
                        'vendor' => $vendor->name,
                        'packing_table_no' => $packingTableNo,
                        'job_no' => $po->po_job_num,
                        'color' => $color,
                        'po_qty' => $poQty,
                        'ors_qty' => $orsQty,
                        'size_wise_packed' => $sizeWisePacked,
                        'packed' => $totalPacked,
                        'yet_to_pack' => $yetToPack,
                        'po_id' => $po_id
                    ];
                }
            }
        }

        // Get all unique sizes that were ACTUALLY PACKED on the selected date
        $allSizes = PackingListItem::whereDate('created_at', $date)
            ->distinct()
            ->pluck('size')
            ->sort()
            ->values()
            ->toArray();

        return view('report.daily_packing_table', compact('summaryData', 'sizeWiseData', 'allSizes', 'date'));
    }

    public function daily_packing_summary_export(Request $request)
    {
        $date = $request->date;

        // Get the same data as in the table method
        $poIds = PackingListItem::whereDate('created_at', $date)
            ->distinct()
            ->pluck('packing_list_id')
            ->toArray();

        $packingListMasters = PackingListMaster::whereIn('id', $poIds)->get();
        $packingListIds = $packingListMasters->pluck('po_id')->unique()->toArray();

        $summaryData = [];
        foreach ($packingListIds as $po_id) {
            $po = PoMaster::find($po_id);
            if (!$po) continue;

            // Get packing table number from PackingListMaster
            $packingListMaster = $packingListMasters->where('po_id', $po_id)->first();
            $packingTableNo = $packingListMaster ? $packingListMaster->packing_table_no : 'N/A';

            $poQty = PackingListConfigItem::where('po_id', $po_id)
                ->sum('po_qty');

            $orsQty = PackingListConfigItem::where('po_id', $po_id)
                ->sum('pack_qty');

            $packedQty = PackingListItem::whereDate('created_at', $date)
                ->whereHas('packingList', function ($q) use ($po_id) {
                    $q->where('po_id', $po_id);
                })
                ->sum('quantity');

            $yetToPack = $orsQty - $packedQty;

            $summaryData[] = [
                'vendor' => $po->vendor->name,
                'packing_table_no' => $packingTableNo,
                'job_no' => $po->po_job_num,
                'po_qty' => $poQty,
                'ors_qty' => $orsQty,
                'packed' => $packedQty,
                'yet_to_pack' => $yetToPack,
                'po_id' => $po_id
            ];
        }

        $filename = "Daily-Packing-Summary-All-Vendors-{$date}.xlsx";

        return Excel::download(new SummaryExport($summaryData, $date, 'All Vendors'), $filename);
    }

    public function daily_packing_sizewise_export(Request $request)
    {
        $date = $request->date;

        // Get the same data as in the table method
        $poIds = PackingListItem::whereDate('created_at', $date)
            ->distinct()
            ->pluck('packing_list_id')
            ->toArray();

        $packingListMasters = PackingListMaster::whereIn('id', $poIds)->get();
        $packingListIds = $packingListMasters->pluck('po_id')->unique()->toArray();

        $sizeWiseData = [];
        $vendorsWithData = PackingListItem::whereDate('created_at', $date)
            ->distinct()
            ->pluck('vendor_id')
            ->toArray();

        foreach ($vendorsWithData as $vendor_id) {
            $vendor = VendorMaster::find($vendor_id);
            if (!$vendor) continue;

            $vendorPoIds = PackingListItem::whereDate('created_at', $date)
                ->where('vendor_id', $vendor_id)
                ->distinct()
                ->pluck('packing_list_id')
                ->toArray();

            $vendorPackingListMasters = PackingListMaster::whereIn('id', $vendorPoIds)
                ->where('vendor_id', $vendor_id)
                ->get();

            $vendorPackingListIds = $vendorPackingListMasters->pluck('po_id')->unique()->toArray();

            foreach ($vendorPackingListIds as $po_id) {
                $po = PoMaster::find($po_id);
                if (!$po) continue;

                // Get packing table number from PackingListMaster
                $packingListMaster = $vendorPackingListMasters->where('po_id', $po_id)->first();
                $packingTableNo = $packingListMaster ? $packingListMaster->packing_table_no : 'N/A';

                // Get all unique colors that were ACTUALLY PACKED on this date for this PO and vendor
                $colors = PackingListItem::whereDate('created_at', $date)
                    ->where('vendor_id', $vendor_id)
                    ->whereHas('packingList', function ($q) use ($po_id) {
                        $q->where('po_id', $po_id);
                    })
                    ->distinct()
                    ->pluck('color')
                    ->toArray();

                foreach ($colors as $color) {
                    $poQty = PackingListConfigItem::where('po_id', $po_id)
                        ->where('vendor_id', $vendor_id)
                        ->where('color', $color)
                        ->sum('po_qty');

                    $orsQty = PackingListConfigItem::where('po_id', $po_id)
                        ->where('vendor_id', $vendor_id)
                        ->where('color', $color)
                        ->sum('pack_qty');

                    $sizeWisePacked = PackingListItem::whereDate('created_at', $date)
                        ->where('vendor_id', $vendor_id)
                        ->where('color', $color)
                        ->whereHas('packingList', function ($q) use ($po_id) {
                            $q->where('po_id', $po_id);
                        })
                        ->selectRaw('size, SUM(quantity) as qty')
                        ->groupBy('size')
                        ->pluck('qty', 'size')
                        ->toArray();

                    $totalPacked = array_sum($sizeWisePacked);
                    $yetToPack = $orsQty - $totalPacked;

                    $sizeWiseData[] = [
                        'vendor' => $vendor->name,
                        'packing_table_no' => $packingTableNo,
                        'job_no' => $po->po_job_num,
                        'color' => $color,
                        'po_qty' => $poQty,
                        'ors_qty' => $orsQty,
                        'size_wise_packed' => $sizeWisePacked,
                        'packed' => $totalPacked,
                        'yet_to_pack' => $yetToPack,
                        'po_id' => $po_id
                    ];
                }
            }
        }

        // Get all unique sizes that were ACTUALLY PACKED on the selected date
        $allSizes = PackingListItem::whereDate('created_at', $date)
            ->distinct()
            ->pluck('size')
            ->sort()
            ->values()
            ->toArray();

        $filename = "Daily-Packing-SizeWise-All-Vendors-{$date}.xlsx";

        return Excel::download(new SizeWiseExport($sizeWiseData, $allSizes, $date, 'All Vendors'), $filename);
    }
}
