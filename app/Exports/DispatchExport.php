<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use App\Models\InvoiceMaster;
use App\Models\PackingListItem;
use App\Models\PoItems;
use App\Models\PoSizes;
use Illuminate\Support\Facades\DB;

class DispatchExport implements WithMultipleSheets
{
    protected $fromDate;
    protected $toDate;
    protected array $selectedIds;

    public function __construct($fromDate, $toDate, array $selectedIds = [])
    {
        $this->fromDate = $fromDate;
        $this->toDate = $toDate;
        $this->selectedIds = array_filter($selectedIds);
    }

    public function sheets(): array
    {
        $sheets = [];
        $invoiceData = $this->getInvoiceData();

        // Group data by month
        $monthlyData = [];
        foreach ($invoiceData as $invoice) {
            $invoiceDate = $invoice['invoice']->inv_date;
            $monthKey = Carbon::parse($invoiceDate)->format('Y-m');
            $monthName = Carbon::parse($invoiceDate)->format('F Y');

            if (!isset($monthlyData[$monthKey])) {
                $monthlyData[$monthKey] = [
                    'name' => $monthName,
                    'data' => []
                ];
            }
            $monthlyData[$monthKey]['data'][] = $invoice;
        }

        // Create a sheet for each month
        foreach ($monthlyData as $monthKey => $monthInfo) {
            $sheets[] = new DispatchMonthlySheet($monthInfo['name'], $monthInfo['data']);
        }

        return $sheets;
    }

    private function getInvoiceData()
    {
        // Load invoice with PO and vendor relationships
        $query = InvoiceMaster::with(['po', 'vendor']);
        if (!empty($this->selectedIds)) {
            $query->whereIn('id', $this->selectedIds);
        } else {
            // Filter by date range
            $query->where(function ($q) {
                $q->where(function ($subQ) {
                    $subQ->whereBetween('inv_date', [$this->fromDate, $this->toDate]);
                })
                    ->orWhere(function ($subQ) {
                        $subQ->whereRaw("STR_TO_DATE(inv_date, '%d-%m-%Y') BETWEEN ? AND ?", [
                            $this->fromDate,
                            $this->toDate
                        ]);
                    })
                    ->orWhere(function ($subQ) {
                        $subQ->whereRaw("STR_TO_DATE(inv_date, '%d.%m.%Y') BETWEEN ? AND ?", [
                            $this->fromDate,
                            $this->toDate
                        ]);
                    });
            });
        }

        $invoices = $query->orderBy('inv_date', 'desc')->get();
        $invoiceData = [];

        foreach ($invoices as $invoice) {
            $billToDetails = json_decode($invoice->bill_to_details, true) ?: [];
            $packIds = explode(',', $invoice->pack_ids);
            $po_details = $invoice->po;
            $vendor = $invoice->vendor;

            // Parse GRN details
            $grnDetails = json_decode($invoice->grn_details, true) ?: [];

            $invoiceGstRate = floatval($invoice->gst);

            $packedItems = PackingListItem::whereIn('packing_list_id', $packIds)
                ->select([
                    'size',
                    'color',
                    DB::raw('SUM(quantity) AS total_quantity'),
                ])
                ->groupBy(['size', 'color'])
                ->get();

            // Get unique carton count for this invoice
            $uniqueCartonCount = PackingListItem::whereIn('packing_list_id', $packIds)
                ->distinct('carton_name')
                ->count('carton_name');

            $totalQty = 0;
            $totalAmount = 0;
            $totalDiscountAmount = 0;
            $totalTaxableValue = 0;
            $totalGstAmount = 0;
            $totalFinalAmount = 0;
            $weightedRateSum = 0;

            $poUnitPrice = $po_details ? floatval($po_details->vcp) : 0;

            foreach ($packedItems as $pi) {
                $unit_price = $poUnitPrice;

                $itm = null;
                if ($po_details) {
                    $itm = PoItems::where('po_id', $po_details->id)
                        ->where('size', $pi->size)
                        ->where('color', $pi->color)
                        ->first();
                }

                if ($vendor) {
                    switch ($vendor->id) {
                        case 1:
                        case 5:
                        case 6:
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
                            }
                            break;

                        case 2:
                            if ($itm) {
                                $unit_price = floatval($itm->unit_price ?? 0);
                            }
                            break;

                        case 3:
                            if ($itm) {
                                $unit_price = floatval($itm->unit_price ?? 0);
                            }
                            break;
                    }
                }

                if (empty($unit_price) && $itm) {
                    $unit_price = floatval($itm->unit_price ?? 0);
                }

                $itemAmount = $pi->total_quantity * $unit_price;
                $discountPct = $vendor ? (floatval($vendor->discount) ?? 0) : 0;
                $discountAmount = ($itemAmount * $discountPct) / 100;
                $taxableValue = $itemAmount - $discountAmount;
                $gstAmount = ($taxableValue * $invoiceGstRate) / 100;
                $itemTotalAmount = $taxableValue + $gstAmount;

                $totalQty += $pi->total_quantity;
                $totalAmount += $itemAmount;
                $totalDiscountAmount += $discountAmount;
                $totalTaxableValue += $taxableValue;
                $totalGstAmount += $gstAmount;
                $totalFinalAmount += $itemTotalAmount;
                $weightedRateSum += ($unit_price * $pi->total_quantity);
            }

            $effectiveRate = $totalQty > 0 ? $weightedRateSum / $totalQty : $poUnitPrice;

            if ($packedItems->isEmpty()) {
                $totalQty = PackingListItem::whereIn('packing_list_id', $packIds)->sum('quantity');

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
                'total_qty' => $totalQty,
                'rate' => $effectiveRate,
                'amount' => $totalAmount,
                'discount_amount' => $totalDiscountAmount,
                'taxable_value' => $totalTaxableValue,
                'gst_rate' => $invoiceGstRate,
                'gst_amount' => $totalGstAmount,
                'total_amount' => $totalFinalAmount,
                'vendor_id' => $vendor ? $vendor->id : null,
                'vendor_name' => $vendor ? $vendor->name : 'N/A',
                'unique_carton_count' => $uniqueCartonCount, // Add carton count
                'grn_details' => $grnDetails // Add GRN details to the data
            ];
        }

        return $invoiceData;
    }
}

class DispatchMonthlySheet implements FromCollection, WithHeadings, WithMapping, WithStyles, WithColumnWidths, WithEvents, WithTitle
{
    protected $monthName;
    protected $data;
    protected $totals;

    public function __construct($monthName, $data)
    {
        $this->monthName = $monthName;
        $this->data = $data;
        $this->calculateTotals();
    }

    public function title(): string
    {
        return $this->monthName;
    }

    public function collection()
    {
        return collect($this->data);
    }

    public function headings(): array
    {
        return [
            'S.No',           // A
            'Job No',         // B
            'PO',             // C
            'HSN Code',       // D
            'Style',          // E
            'Colour',         // F
            'Buyer',          // G
            'Destination',    // H
            'Factory',        // I
            'Unit No',        // J
            'Shipped Qty',    // K
            'Qty in Pcs',     // L
            'Ctns',           // M
            'Inv No',         // N
            'Inv Date',       // O
            'Unit Price',     // P
            'Value',          // Q
            'Tax',            // R
            'Total Value',    // S
            'ASN No',         // T
            'ASN Date',       // U
            'LR No',          // V
            'Transporter',    // W
            'Transport Cost per Piece', // X
            'Dispatched',     // Y
            'Reached',        // Z
            'POD Date',       // AA
            'GRN Date',       // AB
            'GRN QTY',        // AC
            'Short INV vs GRN', // AD
            'Discrepancy',    // AE
            'REMARKS',        // AF
            'Transport Cost Total', // AG
            'Debit Note Value', // AH
            'Debit Note Tax Rate', // AI
            'Debit Note Tax Amount', // AJ
            'Total Debit Note Value', // AK
            'Business Head',  // AL
            'GRN Status',     // AM
            'Status',         // AN
            'Week',           // AO
            'FL Clear Date'   // AP
        ];
    }

    public function map($invoice): array
    {
        static $serialNo = 1;

        $invoiceData = $invoice['invoice'];
        $billToDetails = json_decode($invoiceData->bill_to_details, true) ?: [];
        $po_details = $invoiceData->po;
        $grnDetails = $invoice['grn_details'] ?? [];

        return [
            $serialNo++,      // A - S.No
            $po_details->po_job_num ?? '', // B - Job No
            $po_details->po_num ?? '', // C - PO
            $this->getHsnCode($invoiceData), // D - HSN Code
            $this->getStyleInfo($invoiceData), // E - Style
            $this->getColourInfo($invoiceData), // F - Colour
            $billToDetails['billed_legal_name'] ?? '', // G - Buyer
            $billToDetails['billed_city'] ?? '', // H - Destination
            'CCPL',           // I - Factory
            $grnDetails['unit_no'] ?? '', // J - Unit No
            number_format($invoice['total_qty'], 0), // K - Shipped Qty
            number_format($invoice['total_qty'], 0), // L - Qty in Pcs
            $invoice['unique_carton_count'], // M - Ctns (unique carton count)
            $invoiceData->ref_no ?? '', // N - Inv No
            $invoiceData->inv_date ? Carbon::parse($invoiceData->inv_date)->format('d-M-y') : '', // O - Inv Date
            number_format($invoice['rate'], 2), // P - Unit Price
            number_format($invoice['taxable_value'], 2), // Q - Value
            number_format($invoice['gst_amount'], 2), // R - Tax
            number_format($invoice['total_amount'], 2), // S - Total Value
            // GRN Details from database
            $grnDetails['asn_no'] ?? 'NA',               // T - ASN No
            $this->formatDate($grnDetails['asn_date'] ?? 'NA'), // U - ASN Date
            $grnDetails['lr_no'] ?? '',                // V - LR No
            $grnDetails['transport_name'] ?? '',       // W - Transporter
            $grnDetails['transporter_per_cost'] ?? '', // X - Transport Cost per Piece
            $this->formatDate($grnDetails['dispatched_date'] ?? ''), // Y - Dispatched
            $this->formatDate($grnDetails['reached_date'] ?? ''),    // Z - Reached
            $this->formatDate($grnDetails['pod_date'] ?? ''),        // AA - POD Date
            $this->formatDate($grnDetails['grn_date'] ?? ''),        // AB - GRN Date
            $grnDetails['grn_qty'] ?? '',              // AC - GRN QTY
            $grnDetails['short_inv_vs_grn'] ?? '',     // AD - Short INV vs GRN
            $grnDetails['discrepancy'] ?? '',          // AE - Discrepancy
            $grnDetails['remarks'] ?? '',              // AF - REMARKS
            $grnDetails['transport_cost_total'] ?? '', // AG - Transport Cost Total
            $grnDetails['debit_note_value'] ?? '',     // AH - Debit Note Value
            $grnDetails['debit_note_tax_rate'] ?? '',  // AI - Debit Note Tax Rate
            $grnDetails['debit_note_tax_amount'] ?? '', // AJ - Debit Note Tax Amount
            $grnDetails['total_debit_note_value'] ?? '', // AK - Total Debit Note Value
            $grnDetails['business_head'] ?? '',        // AL - Business Head
            $grnDetails['grn_status'] ?? '',           // AM - GRN Status
            $grnDetails['status'] ?? '',               // AN - Status
            $grnDetails['week'] ?? '',                 // AO - Week
            $this->formatDate($grnDetails['fl_clear_date'] ?? '') // AP - FL Clear Date
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                $totalRows = count($this->data) + 1; // +1 for header row

                // Add title row
                $sheet->insertNewRowBefore(1);
                $sheet->setCellValue('A1', 'DISPATCH STATUS REPORT - ' . $this->monthName);
                $sheet->mergeCells('A1:AP1'); // Updated to AP to include all columns

                // Style title row
                $sheet->getStyle('A1:AP1')->applyFromArray([
                    'font' => [
                        'bold' => true,
                        'size' => 16
                    ],
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_CENTER,
                        'vertical' => Alignment::VERTICAL_CENTER
                    ]
                ]);

                // Style header row (row 2)
                $sheet->getStyle('A2:AP2')->applyFromArray([
                    'font' => [
                        'bold' => true,
                        'color' => ['rgb' => 'FFFFFF']
                    ],
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => '4472C4']
                    ],
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_CENTER,
                        'vertical' => Alignment::VERTICAL_CENTER
                    ],
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => Border::BORDER_THIN,
                            'color' => ['rgb' => '000000']
                        ]
                    ]
                ]);

                // Add totals row
                $totalRowIndex = $totalRows + 2;
                $sheet->setCellValue('A' . $totalRowIndex, 'TOTAL');
                $sheet->mergeCells('A' . $totalRowIndex . ':J' . $totalRowIndex);
                $sheet->setCellValue('K' . $totalRowIndex, number_format($this->totals['shipped_qty'], 0));
                $sheet->setCellValue('L' . $totalRowIndex, number_format($this->totals['qty_pcs'], 0));
                $sheet->setCellValue('M' . $totalRowIndex, number_format($this->totals['total_cartons'], 0)); // Add carton total
                // Leave columns N-P empty for totals row
                $sheet->setCellValue('Q' . $totalRowIndex, number_format($this->totals['value'], 2));
                $sheet->setCellValue('R' . $totalRowIndex, number_format($this->totals['tax'], 2));
                $sheet->setCellValue('S' . $totalRowIndex, number_format($this->totals['total_value'], 2));
                // Calculate GRN totals
                $sheet->setCellValue('AG' . $totalRowIndex, number_format($this->totals['transport_cost_total'], 2));
                $sheet->setCellValue('AH' . $totalRowIndex, number_format($this->totals['debit_note_value'], 2));
                $sheet->setCellValue('AJ' . $totalRowIndex, number_format($this->totals['debit_note_tax_amount'], 2));
                $sheet->setCellValue('AK' . $totalRowIndex, number_format($this->totals['total_debit_note_value'], 2));

                // Style totals row
                $sheet->getStyle('A' . $totalRowIndex . ':AP' . $totalRowIndex)->applyFromArray([
                    'font' => [
                        'bold' => true,
                        'color' => ['rgb' => 'FFFFFF']
                    ],
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => '808080'] // Gray background
                    ],
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_CENTER,
                        'vertical' => Alignment::VERTICAL_CENTER
                    ],
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => Border::BORDER_THIN,
                            'color' => ['rgb' => '000000']
                        ]
                    ]
                ]);

                // Right align totals in numeric columns
                $sheet->getStyle('K' . $totalRowIndex . ':S' . $totalRowIndex)->applyFromArray([
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_RIGHT
                    ]
                ]);
                $sheet->getStyle('AG' . $totalRowIndex . ':AK' . $totalRowIndex)->applyFromArray([
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_RIGHT
                    ]
                ]);

                // Style data rows
                $sheet->getStyle('A3:AP' . ($totalRows + 1))->applyFromArray([
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => Border::BORDER_THIN,
                            'color' => ['rgb' => '000000']
                        ]
                    ],
                    'alignment' => [
                        'vertical' => Alignment::VERTICAL_CENTER
                    ]
                ]);

                // Right align numeric columns
                $sheet->getStyle('K3:S' . ($totalRows + 1))->applyFromArray([
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_RIGHT
                    ]
                ]);
                $sheet->getStyle('X3:X' . ($totalRows + 1))->applyFromArray([
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_RIGHT
                    ]
                ]);
                $sheet->getStyle('AC3:AC' . ($totalRows + 1))->applyFromArray([
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_RIGHT
                    ]
                ]);
                $sheet->getStyle('AD3:AD' . ($totalRows + 1))->applyFromArray([
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_RIGHT
                    ]
                ]);
                $sheet->getStyle('AG3:AK' . ($totalRows + 1))->applyFromArray([
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_RIGHT
                    ]
                ]);

                // Set row heights
                $sheet->getRowDimension(1)->setRowHeight(30); // Title
                $sheet->getRowDimension(2)->setRowHeight(25); // Headers
                $sheet->getRowDimension($totalRowIndex)->setRowHeight(25); // Totals

                // Auto-size data rows
                for ($row = 3; $row <= ($totalRows + 1); $row++) {
                    $sheet->getRowDimension($row)->setRowHeight(20);
                }

                // Freeze panes
                $sheet->freezePane('A3');
            }
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [];
    }

    public function columnWidths(): array
    {
        return [
            'A' => 8,
            'B' => 12,
            'C' => 15,
            'D' => 12,
            'E' => 30,
            'F' => 15,
            'G' => 20,
            'H' => 15,
            'I' => 12,
            'J' => 12,
            'K' => 12,
            'L' => 12,
            'M' => 8,
            'N' => 15,
            'O' => 12,
            'P' => 12,
            'Q' => 15,
            'R' => 12,
            'S' => 15,
            'T' => 12,
            'U' => 12,
            'V' => 12,
            'W' => 15,
            'X' => 18,
            'Y' => 12,
            'Z' => 12,
            'AA' => 12,
            'AB' => 12,
            'AC' => 12,
            'AD' => 15,
            'AE' => 12,
            'AF' => 20,
            'AG' => 15,
            'AH' => 15,
            'AI' => 12,
            'AJ' => 15,
            'AK' => 18,
            'AL' => 15,
            'AM' => 12,
            'AN' => 12,
            'AO' => 10,
            'AP' => 15
        ];
    }

    private function calculateTotals()
    {
        $this->totals = [
            'shipped_qty' => 0,
            'qty_pcs' => 0,
            'total_cartons' => 0, // Add carton total
            'value' => 0,
            'tax' => 0,
            'total_value' => 0,
            'transport_cost_total' => 0,
            'debit_note_value' => 0,
            'debit_note_tax_amount' => 0,
            'total_debit_note_value' => 0
        ];

        foreach ($this->data as $invoice) {
            $grnDetails = $invoice['grn_details'] ?? [];

            $this->totals['shipped_qty'] += $invoice['total_qty'];
            $this->totals['qty_pcs'] += $invoice['total_qty'];
            $this->totals['total_cartons'] += $invoice['unique_carton_count']; // Add carton count to total
            $this->totals['value'] += $invoice['taxable_value'];
            $this->totals['tax'] += $invoice['gst_amount'];
            $this->totals['total_value'] += $invoice['total_amount'];

            // Add GRN totals
            $this->totals['transport_cost_total'] += floatval($grnDetails['transport_cost_total'] ?? 0);
            $this->totals['debit_note_value'] += floatval($grnDetails['debit_note_value'] ?? 0);
            $this->totals['debit_note_tax_amount'] += floatval($grnDetails['debit_note_tax_amount'] ?? 0);
            $this->totals['total_debit_note_value'] += floatval($grnDetails['total_debit_note_value'] ?? 0);
        }
    }

    private function formatDate($date)
    {
        if (empty($date)) {
            return '';
        }

        try {
            return Carbon::parse($date)->format('d-M-y');
        } catch (\Exception $e) {
            return $date; // Return original if parsing fails
        }
    }

    private function getStyleInfo($invoice)
    {
        $po_details = $invoice->po;
        $vendor = $invoice->vendor;

        if (!$po_details) return '';

        $articleInfo = json_decode($po_details->article_info, true) ?: [];
        $packIds = explode(',', $invoice->pack_ids);

        // Get first packing item for size and color
        $firstPackingItem = PackingListItem::whereIn('packing_list_id', $packIds)->first();

        if (!$firstPackingItem) {
            return $articleInfo['Article description'] ?? $articleInfo['ARTICLE'] ?? '';
        }

        $size = $firstPackingItem->size;
        $color = $firstPackingItem->color;

        // Get PoItems for this specific color and size
        $itm = PoItems::where('po_id', $po_details->id)
            ->where('size', $size)
            ->where('color', $color)
            ->first();

        // Apply vendor-specific logic for style/description
        if ($vendor) {
            switch ($vendor->id) {
                case 1:
                case 5:
                case 6:
                    // For vendors 1, 5, 6 - use article description from article_info
                    return $articleInfo['Article description'] ?? $articleInfo['ARTICLE'] ?? '';

                case 4:
                    // For vendor 4 - use part_description from po_items
                    return $itm ? ($itm->part_description ?? '') : '';

                case 2:
                    // For vendor 2 - use type from po_items
                    return $itm ? ($itm->type ?? '') : '';

                case 3:
                    // For vendor 3 - use style_description from po_items
                    return $itm ? ($itm->style_description ?? '') : '';

                default:
                    return $articleInfo['Article description'] ?? $articleInfo['ARTICLE'] ?? '';
            }
        }

        return $articleInfo['Article description'] ?? $articleInfo['ARTICLE'] ?? '';
    }

    private function getColourInfo($invoice)
    {
        $packIds = explode(',', $invoice->pack_ids);
        $firstPackingItem = PackingListItem::whereIn('packing_list_id', $packIds)->first();
        return $firstPackingItem ? $firstPackingItem->color : '';
    }

    private function getHsnCode($invoice)
    {
        $po_details = $invoice->po;
        $packIds = explode(',', $invoice->pack_ids);

        if (!$po_details) return '';

        $firstPackingItem = PackingListItem::whereIn('packing_list_id', $packIds)->first();
        if (!$firstPackingItem) return '';

        $itm = PoItems::where('po_id', $po_details->id)
            ->where('size', $firstPackingItem->size)
            ->where('color', $firstPackingItem->color)
            ->first();

        return $itm ? ($itm->hsn_code ?? '') : '';
    }
}
