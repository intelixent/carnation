<?php

namespace App\Http\Controllers;

use App\Http\Controllers\BaseController;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use App\Models\PoItems;
use App\Models\PoSizes;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Yajra\DataTables\Facades\DataTables;
use Barryvdh\DomPDF\Facade\Pdf;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Storage;
use App\Models\PackingListItem;
use App\Models\InvoiceMaster;
use App\Exports\EInvoiceExport;

class EInvoiceController extends BaseController
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

    public function e_invoice_master()
    {
        $page_data = [
            'page_title' => "Master",
            'page_main_title' => "E-Invoice",
            'isSuperAdmin' => $this->isSuperAdmin,
        ];

        return view('e_invoice.master', $page_data);
    }

    public function e_invoice_master_table(Request $request)
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

        return view('e_invoice.table', $data);
    }

    public function e_invoice_excel_download(Request $request)
    {
        $request->validate([
            'from_date' => 'required|date',
            'to_date' => 'required|date|after_or_equal:from_date'
        ]);

        // $from_date = $request->from_date;
        // $to_date = $request->to_date;
        $from_date = $request->query('from_date');
        $to_date   = $request->query('to_date');
        $selected = array_map('intval', (array) $request->query('selected_invoice', []));


        // Generate filename
        $filename = "E-Invoice-{$from_date}-{$to_date}.xlsx";

        //print_r(new EInvoiceExport($from_date, $to_date,$selected));

        return Excel::download(new EInvoiceExport($from_date, $to_date, $selected), $filename);
    }
}
