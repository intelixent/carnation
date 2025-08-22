<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use App\Models\InvoiceMaster;
use App\Models\PackingListItem;
use App\Models\StateMaster;
use App\Models\PoItems;
use App\Models\PoSizes;
use Illuminate\Support\Facades\DB;

class EInvoiceExport implements FromCollection, WithHeadings, WithMapping, WithStyles, WithColumnWidths, WithEvents
{
    protected $fromDate;
    protected $toDate;
    protected $data;

    public function __construct($fromDate, $toDate)
    {
        $this->fromDate = $fromDate;
        $this->toDate = $toDate;
        $this->data = $this->getInvoiceData();
    }

    public function collection()
    {
        return collect($this->data);
    }

    public function headings(): array
    {
        return [
            // Basic Details Section (A-D)
            'Document Date',
            'Document Number',
            'Document Type Code',
            'Supply Type Code',

            // Recipient/Customer Information Section (E-L)
            'Recipient Legal Name',
            'Recipient Trade Name',
            'Recipient GSTIN',
            'Place of Supply',
            'Recipient Address 1',
            'Recipient Place',
            'Recipient State Code',
            'Recipient PIN Code',

            // Item Details Section (M-X)
            'SI No',
            'Item Description',
            'Is the item a GOOD (G) or SERVICE (S)',
            'HSN or SAC Code',
            'Quantity',
            'Unit of Measurement',
            'Item Price',
            'Gross Amount',
            'Item Discount Amount',
            'Item Taxable Value',
            'GST Rate',
            'IGST Amount',

            // Tax Details Section (Y-AH)
            'CGST Amount',
            'SGST/UTGST Amount',
            'Comp Cess Rate Ad Valorem',
            'Comp Cess Amount Ad Valorem',
            'Comp Cess Amount Non Ad Valorem',
            'State Cess Rate Ad Valorem',
            'State Cess Amount Ad Valorem',
            'State Cess Amount Non Ad Valorem',
            'Other Charges (Item Level)',
            'Item Total Amount',

            // Document Total Details (AI-AR)
            'Total Taxable Value',
            'IGST  Amount Total',
            'CGST  Amount Total',
            'SGST/UTGST Amount Total',
            'Comp Cess Amount Total',
            'State Cess Amount Total',
            'TCS Amount',
            'Other Charge (Invoice Level)',
            'Round Off Amount',
            'Total Invoice Value in INR',

            // Preceding Document /Contract Reference (AS-AV)
            'Is reverse charge applicable',
            'Is Sec 7 IGST Act applicable',
            'Preceding Document Number',
            'Preceding Document Date',

            // Supplier Information (AW-BB)
            'Supplier Legal Name',
            'GSTIN of Supplier',
            'Supplier Address 1',
            'Supplier Place',
            'Supplier State Code',
            'Supplier PIN Code',

            // Mandatory in case of Exports of Goods (BC-BE)
            'Shipping Port Code',
            'Shipping Bill Number',
            'Shipping Bill Date',

            // Payee Information (BF-BN)
            'Payee Name',
            'Payee Bank Account Number',
            'Mode of Payment',
            'Bank Branch Code',
            'Payment Terms',
            'Payment Instruction',
            'Credit Transfer Terms',
            'Direct Debit Terms',
            'Credit Days',

            // Ship To Details (BO-BT)
            'Ship To Legal Name',
            'Ship To GSTIN',
            'Ship To Address1',
            'Ship To Place',
            'Ship To Pincode',
            'Ship To State Code',

            // Dispatch From Details (BU-BY)
            'Dispatch From Name',
            'Dispatch From Address1',
            'Dispatch From Place',
            'Dispatch From State Code',
            'Dispatch From Pincode',

            // Extra Information (BZ-BZ)
            'Tax Scheme',

            // E-way Bill Details (CA-CH)
            'Transporter ID',
            'Trans Mode',
            'Trans Distance',
            'Transporter Name',
            'Trans Doc No',
            'Trans Doc Date',
            'Vehicle No',
            'Vehicle Type',

            // Receipt / Contract References (CI-CP)
            'Receipt Advice Reference',
            'Receipt Advice Date',
            'Tender or Lot Reference',
            'Contract Reference',
            'External Reference',
            'Project Reference',
            'PO Reference Number',
            'PO Reference Date',

            // Additional Supporting Documents (CQ-CS)
            'Additional Supporting Documents URL',
            'Additional Supporting Documents base64',
            'Additional Information',

            // Document Period (CT-CU)
            'Document Period Start Date',
            'Document Period End Date',

            // Other Basic Details (CV-CV)
            'Other Basic Details',

            // Other Item Details (CW-DB)
            'Barcode',
            'Free Quantity',
            'Pre-Tax Value',
            'Purchase Order Line Reference',
            'Origin Country Code',
            'Unique Serial Number',

            // Batch Details (DC-DE)
            'Batch Number',
            'Batch Expiry Date',
            'Warranty Date',

            // Attribute Details of Item (DF-DG)
            'Attribute Name',
            'Attribute Value',

            // Other Recipient Information (DH-DK)
            'Country Code of Export',
            'Recipient Phone',
            'Recipient e-mail ID',
            'Recipient Address 2',

            // Other Document Total (DL-DO)
            'Total Invoice Value in FCNR',
            'Paid Amount',
            'Amount Due',
            'Discount Amount Invoice Level',

            // Other Supplier Information (DP-DS)
            'Trade Name of Supplier',
            'Supplier Address 2',
            'Supplier Phone',
            'Supplier e-mail',

            // Other Ship To Details (DT-DU)
            'Ship To Trade Name',
            'Ship To Address2',

            // Other Dispatch From Details (DV-DV)
            'Dispatch From Address2',

            // Other Extra Information (DW-EZ)
            'Remarks',
            'Export Duty Amount',
            'Supplier Can Opt Refund',
            'ECOM GSTIN',

            // Other Preceding Document / Contract Reference (EA-EA)
            'Other Reference',
        ];
    }

    public function map($invoice): array
    {
        // Extract data from the invoice array
        $invoiceData = $invoice['invoice'];
        $billToDetails = json_decode($invoiceData->bill_to_details, true) ?: [];
        $shipToDetails = json_decode($invoiceData->ship_to_details, true) ?: [];

        // Get state information
        $billedStateId = $billToDetails['billed_state'] ?? null;
        $billedStateInfo = $this->getStateInfo($billedStateId);

        $shippedStateId = $shipToDetails['shipped_state'] ?? null;
        $shippedStateInfo = $this->getStateInfo($shippedStateId);

        // Get HSN code and UOM using the same logic as generateInvoice
        $hsnUomData = $this->getHsnCodeAndUom($invoiceData);

        return [
            // Basic Details
            $invoiceData->inv_date ? \Carbon\Carbon::parse($invoiceData->inv_date)->format('d-m-Y') : '',
            $invoiceData->ref_no ?? '',
            'INV',
            'B2B',

            // Recipient Information
            $billToDetails['billed_legal_name'] ?? '',
            '',
            $billToDetails['billed_gst_no'] ?? '',
            $billedStateInfo['code_name'] ?? '',
            $billToDetails['billed_address_1'] ?? '',
            $billToDetails['billed_city'] ?? '',
            $billedStateInfo['code_name'] ?? '',
            $billToDetails['billed_pincode'] ?? '',

            // Item Details
            '1',
            $this->getItemDescription($invoiceData),
            'G',
            $hsnUomData['hsn_code'],
            number_format($invoice['total_qty'], 0),
            $hsnUomData['uom'],
            number_format($invoice['rate'], 2),
            number_format($invoice['amount'], 2),
            number_format($invoice['discount_amount'], 2),
            number_format($invoice['taxable_value'], 2),
            number_format($invoice['gst_rate'], 2),
            number_format($invoice['gst_amount'], 2),

            // Tax Details
            '',
            '',
            '',
            '',
            '',
            '',
            '',
            '',
            '',
            number_format($invoice['total_amount'], 2),

            // Document Total Details
            number_format($invoice['taxable_value'], 2),
            number_format($invoice['gst_amount'], 2),
            '',
            '',
            '',
            '',
            '',
            '',
            '',
            number_format($invoice['total_amount'], 2),

            // Preceding Document /Contract Reference 
            '',
            '',
            '',
            '',

            // Supplier Information
            'Carnation Creations Private Limited',
            '33AAHCC1371N1ZL',
            '376/1 , NARASIMHANAICKEN PALAYAM VILLAGE',
            'COIMBATORE',
            '33-TAMIL NADU',
            '641031',

            // Mandatory in case of Exports of Goods
            '',
            '',
            '',

            // Payee Information 
            '',
            '',
            '',
            '',
            '',
            '',
            '',
            '',
            '',

            // Ship To Details 
            $shipToDetails['shipped_legal_name'] ?? '',
            $shipToDetails['shipped_gst_no'] ?? '',
            $shipToDetails['shipped_address_1'] ?? '',
            $shipToDetails['shipped_city'] ?? '',
            $shipToDetails['shipped_pincode'] ?? '',
            $shippedStateInfo['code_name'] ?? '',

            // Dispatch From Details 
            '',
            '',
            '',
            '',
            '',

            // Extra Information 
            '',

            // E-way Bill Details 
            '',
            '',
            '',
            '',
            '',
            '',
            '',
            '',

            // Receipt / Contract References
            '',
            '',
            '',
            '',
            '',
            '',
            $invoice['po_number'] ?? '',
            $invoice['po_date'] ?? '',

            // Additional Supporting Documents 
            '',
            '',
            '',

            // Document Period 
            '',
            '',

            // Other Basic Details
            '',

            // Other Item Details
            '',
            '',
            '',
            '',
            '',
            '',

            // Batch Details 
            '',
            '',
            '',

            // Attribute Details of Item 
            '',
            '',

            // Other Recipient Information
            '',
            '',
            '',
            '',

            // Other Document Total
            '',
            '',
            '',
            '',

            // Other Supplier Information
            '',
            '',
            '',
            '',

            // Other Ship To Details
            '',
            '',

            // Other Dispatch From Details 
            '',

            // Other Extra Information
            '',
            '',
            '',
            '',

            // Other Preceding Document / Contract Reference 
            '',
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                $totalRows = count($this->data) + 1; // +1 for header row

                // Insert a new row at the top for section labels
                $sheet->insertNewRowBefore(1);

                // Add section labels in row 1 (60% lighter gold)
                $sheet->setCellValue('A1', 'Basic Details');
                $sheet->mergeCells('A1:D1');

                $sheet->setCellValue('E1', 'Recipient /Customer Information');
                $sheet->mergeCells('E1:L1');

                $sheet->setCellValue('M1', 'Item Details');
                $sheet->mergeCells('M1:X1');

                $sheet->setCellValue('Y1', 'Tax Details');
                $sheet->mergeCells('Y1:AH1');

                $sheet->setCellValue('AI1', 'Document Total Details');
                $sheet->mergeCells('AI1:AR1');

                $sheet->setCellValue('AS1', 'Preceding Document /Contract Reference ');
                $sheet->mergeCells('AS1:AV1');

                $sheet->setCellValue('AW1', 'Supplier Information');
                $sheet->mergeCells('AW1:BB1');

                $sheet->setCellValue('BC1', 'Mandatory in case of Exports of Goods');
                $sheet->mergeCells('BC1:BE1');

                $sheet->setCellValue('BF1', 'Payee Information');
                $sheet->mergeCells('BF1:BN1');

                $sheet->setCellValue('BO1', 'Ship To Details');
                $sheet->mergeCells('BO1:BT1');

                $sheet->setCellValue('BU1', 'Dispatch From Details');
                $sheet->mergeCells('BU1:BY1');

                $sheet->setCellValue('BZ1', 'Extra Information');
                $sheet->mergeCells('BZ1:BZ1');

                $sheet->setCellValue('CA1', 'E-way Bill Details');
                $sheet->mergeCells('CA1:CH1');

                $sheet->setCellValue('CI1', 'Receipt / Contract References');
                $sheet->mergeCells('CI1:CP1');

                $sheet->setCellValue('CQ1', 'Additional Supporting Documents ');
                $sheet->mergeCells('CQ1:CS1');

                $sheet->setCellValue('CT1', 'Document Period');
                $sheet->mergeCells('CT1:CU1');

                $sheet->setCellValue('CV1', 'Other Basic Details');
                $sheet->mergeCells('CV1:CV1');

                $sheet->setCellValue('CW1', 'Other Item Details');
                $sheet->mergeCells('CW1:DB1');

                $sheet->setCellValue('DC1', 'Batch Details');
                $sheet->mergeCells('DC1:DE1');

                $sheet->setCellValue('DF1', 'Attribute Details of Item');
                $sheet->mergeCells('DF1:DG1');

                $sheet->setCellValue('DH1', ' Other Recipient Information');
                $sheet->mergeCells('DH1:DK1');

                $sheet->setCellValue('DL1', 'Other Document Total');
                $sheet->mergeCells('DL1:DO1');

                $sheet->setCellValue('DP1', 'Other Supplier Information');
                $sheet->mergeCells('DP1:DS1');

                $sheet->setCellValue('DT1', 'Other Ship To Details');
                $sheet->mergeCells('DT1:DU1');

                $sheet->setCellValue('DW1', 'Other Extra Information');
                $sheet->mergeCells('DW1:DZ1');

                $sheet->setCellValue('EA1', 'Other Preceding Document / Contract Reference ');
                $sheet->mergeCells('EA1:EA1');

                // Style row 1 (section labels) - Gold contrast 60% lighter
                $sheet->getStyle('A1:EA1')->applyFromArray([
                    'font' => [
                        'bold' => true,
                        'color' => ['rgb' => '7F6000'], // Darker gold for text
                        'size' => 11
                    ],
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => 'FFF2CC'] // Gold 60% lighter
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

                // Style row 2 (column headers) - Gold contrast 80% lighter
                $sheet->getStyle('A2:EA2')->applyFromArray([
                    'font' => [
                        'bold' => true,
                        'color' => ['rgb' => '7F6000'], // Darker gold for text
                        'size' => 10
                    ],
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => 'FFF9E6'] // Gold 80% lighter
                    ],
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_CENTER,
                        'vertical' => Alignment::VERTICAL_CENTER,
                        'wrapText' => true
                    ],
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => Border::BORDER_THIN,
                            'color' => ['rgb' => '000000']
                        ]
                    ]
                ]);

                // Style data rows
                $sheet->getStyle('A3:EA' . ($totalRows + 1))->applyFromArray([
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
                $sheet->getStyle('Q3:AR' . ($totalRows + 1))->applyFromArray([
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_RIGHT
                    ]
                ]);

                // Set row heights
                $sheet->getRowDimension(1)->setRowHeight(25); // Section labels
                $sheet->getRowDimension(2)->setRowHeight(35); // Column headers

                // Auto-size rows for data
                for ($row = 3; $row <= ($totalRows + 1); $row++) {
                    $sheet->getRowDimension($row)->setRowHeight(20);
                }

                // Freeze panes at row 3 (after headers)
                $sheet->freezePane('A3');
            }
        ];
    }

    public function styles(Worksheet $sheet)
    {
        // This method will be overridden by registerEvents
        return [];
    }

    public function columnWidths(): array
    {
        return [
            // Basic Details Section (A-D)
            'A' => 12, // Document Date
            'B' => 20, // Document Number
            'C' => 15, // Document Type Code
            'D' => 15, // Supply Type Code

            // Recipient/Customer Information Section (E-L)
            'E' => 35, // Recipient Legal Name
            'F' => 30, // Recipient Trade Name
            'G' => 18, // Recipient GSTIN
            'H' => 20, // Place of Supply
            'I' => 30, // Recipient Address 1
            'J' => 15, // Recipient Place
            'K' => 20, // Recipient State Code
            'L' => 12, // Recipient PIN Code

            // Item Details Section (M-X)
            'M' => 8,  // SI No
            'N' => 30, // Item Description
            'O' => 12, // Is the item a GOOD (G) or SERVICE (S)
            'P' => 12, // HSN or SAC Code
            'Q' => 12, // Quantity
            'R' => 12, // Unit of Measurement
            'S' => 15, // Item Price
            'T' => 15, // Gross Amount
            'U' => 15, // Item Discount Amount
            'V' => 15, // Item Taxable Value
            'W' => 10, // GST Rate
            'X' => 15, // IGST Amount

            // Tax Details Section (Y-AH)
            'Y' => 15, // CGST Amount
            'Z' => 15, // SGST/UTGST Amount
            'AA' => 18, // Comp Cess Rate Ad Valorem
            'AB' => 20, // Comp Cess Amount Ad Valorem
            'AC' => 22, // Comp Cess Amount Non Ad Valorem
            'AD' => 20, // State Cess Rate Ad Valorem
            'AE' => 20, // State Cess Amount Ad Valorem
            'AF' => 25, // State Cess Amount Non Ad Valorem
            'AG' => 18, // Other Charges (Item Level)
            'AH' => 18, // Item Total Amount

            // Document Total Details (AI-AR)
            'AI' => 18, // Total Taxable Value
            'AJ' => 18, // IGST Amount Total
            'AK' => 18, // CGST Amount Total
            'AL' => 20, // SGST/UTGST Amount Total
            'AM' => 20, // Comp Cess Amount Total
            'AN' => 20, // State Cess Amount Total
            'AO' => 15, // TCS Amount
            'AP' => 20, // Other Charge (Invoice Level)
            'AQ' => 15, // Round Off Amount
            'AR' => 20, // Total Invoice Value in INR

            // Preceding Document /Contract Reference (AS-AV)
            'AS' => 20, // Is reverse charge applicable
            'AT' => 20, // Is Sec 7 IGST Act applicable
            'AU' => 25, // Preceding Document Number
            'AV' => 18, // Preceding Document Date

            // Supplier Information (AW-BB)
            'AW' => 35, // Supplier Legal Name
            'AX' => 18, // GSTIN of Supplier
            'AY' => 30, // Supplier Address 1
            'AZ' => 15, // Supplier Place
            'BA' => 18, // Supplier State Code
            'BB' => 12, // Supplier PIN Code

            // Mandatory in case of Exports of Goods (BC-BE)
            'BC' => 18, // Shipping Port Code
            'BD' => 20, // Shipping Bill Number
            'BE' => 18, // Shipping Bill Date

            // Payee Information (BF-BN)
            'BF' => 25, // Payee Name
            'BG' => 20, // Payee Bank Account Number
            'BH' => 15, // Mode of Payment
            'BI' => 15, // Bank Branch Code
            'BJ' => 15, // Payment Terms
            'BK' => 20, // Payment Instruction
            'BL' => 20, // Credit Transfer Terms
            'BM' => 18, // Direct Debit Terms
            'BN' => 12, // Credit Days

            // Ship To Details (BO-BT)
            'BO' => 30, // Ship To Legal Name
            'BP' => 18, // Ship To GSTIN
            'BQ' => 30, // Ship To Address1
            'BR' => 15, // Ship To Place
            'BS' => 12, // Ship To Pincode
            'BT' => 18, // Ship To State Code

            // Dispatch From Details (BU-BY)
            'BU' => 25, // Dispatch From Name
            'BV' => 30, // Dispatch From Address1
            'BW' => 15, // Dispatch From Place
            'BX' => 18, // Dispatch From State Code
            'BY' => 12, // Dispatch From Pincode

            // Extra Information (BZ)
            'BZ' => 15, // Tax Scheme

            // E-way Bill Details (CA-CH)
            'CA' => 15, // Transporter ID
            'CB' => 12, // Trans Mode
            'CC' => 15, // Trans Distance
            'CD' => 25, // Transporter Name
            'CE' => 18, // Trans Doc No
            'CF' => 15, // Trans Doc Date
            'CG' => 15, // Vehicle No
            'CH' => 12, // Vehicle Type

            // Receipt / Contract References (CI-CP)
            'CI' => 25, // Receipt Advice Reference
            'CJ' => 18, // Receipt Advice Date
            'CK' => 25, // Tender or Lot Reference
            'CL' => 20, // Contract Reference
            'CM' => 20, // External Reference
            'CN' => 20, // Project Reference
            'CO' => 20, // PO Reference Number
            'CP' => 15, // PO Reference Date

            // Additional Supporting Documents (CQ-CS)
            'CQ' => 30, // Additional Supporting Documents URL
            'CR' => 25, // Additional Supporting Documents base64
            'CS' => 25, // Additional Information

            // Document Period (CT-CU)
            'CT' => 20, // Document Period Start Date
            'CU' => 20, // Document Period End Date

            // Other Basic Details (CV)
            'CV' => 20, // Other Basic Details

            // Other Item Details (CW-DB)
            'CW' => 15, // Barcode
            'CX' => 12, // Free Quantity
            'CY' => 15, // Pre-Tax Value
            'CZ' => 25, // Purchase Order Line Reference
            'DA' => 18, // Origin Country Code
            'DB' => 20, // Unique Serial Number

            // Batch Details (DC-DE)
            'DC' => 15, // Batch Number
            'DD' => 15, // Batch Expiry Date
            'DE' => 15, // Warranty Date

            // Attribute Details of Item (DF-DG)
            'DF' => 20, // Attribute Name
            'DG' => 20, // Attribute Value

            // Other Recipient Information (DH-DK)
            'DH' => 18, // Country Code of Export
            'DI' => 15, // Recipient Phone
            'DJ' => 25, // Recipient e-mail ID
            'DK' => 25, // Recipient Address 2

            // Other Document Total (DL-DO)
            'DL' => 20, // Total Invoice Value in FCNR
            'DM' => 15, // Paid Amount
            'DN' => 15, // Amount Due
            'DO' => 22, // Discount Amount Invoice Level

            // Other Supplier Information (DP-DS)
            'DP' => 25, // Trade Name of Supplier
            'DQ' => 25, // Supplier Address 2
            'DR' => 15, // Supplier Phone
            'DS' => 25, // Supplier e-mail

            // Other Ship To Details (DT-DU)
            'DT' => 25, // Ship To Trade Name
            'DU' => 25, // Ship To Address2

            // Other Dispatch From Details (DV)
            'DV' => 25, // Dispatch From Address2

            // Other Extra Information (DW-DZ)
            'DW' => 20, // Remarks
            'DX' => 18, // Export Duty Amount
            'DY' => 20, // Supplier Can Opt Refund
            'DZ' => 15, // ECOM GSTIN

            // Other Preceding Document / Contract Reference (EA)
            'EA' => 32, // Other Reference
        ];
    }

    private function getInvoiceData()
    {
        // Load invoice with PO and vendor relationships
        $invoices = InvoiceMaster::with(['po', 'vendor'])
            ->where(function ($query) {
                $query->where(function ($q) {
                    $q->whereBetween('inv_date', [$this->fromDate, $this->toDate]);
                })
                    ->orWhere(function ($q) {
                        $q->whereRaw("STR_TO_DATE(inv_date, '%d-%m-%Y') BETWEEN ? AND ?", [
                            $this->fromDate,
                            $this->toDate
                        ]);
                    });
            })
            ->get();

        $invoiceData = [];
        foreach ($invoices as $invoice) {
            $billToDetails = json_decode($invoice->bill_to_details, true) ?: [];
            $packIds = explode(',', $invoice->pack_ids);
            $po_details = $invoice->po;
            $vendor = $invoice->vendor;

            // Get total quantity
            $totalQty = PackingListItem::whereIn('packing_list_id', $packIds)
                ->sum('quantity');

            // Calculate amounts
            $poUnitPrice = $po_details ? floatval($po_details->vcp) : 0;
            $totalAmount = $totalQty * $poUnitPrice;
            $discountPct = $vendor ? (floatval($vendor->discount) ?? 0) : 0;
            $totalDiscountAmount = ($totalAmount * $discountPct) / 100;
            $totalTaxableValue = $totalAmount - $totalDiscountAmount;
            $invoiceGstRate = floatval($invoice->gst);
            $totalGstAmount = ($totalTaxableValue * $invoiceGstRate) / 100;
            $totalFinalAmount = $totalTaxableValue + $totalGstAmount;

            $invoiceData[] = [
                'invoice' => $invoice,
                'total_qty' => $totalQty,
                'rate' => $poUnitPrice,
                'amount' => $totalAmount,
                'po_number' => $po_details->po_num ?? '',
                'po_date' => $po_details->po_date ?? '',
                'discount_amount' => $totalDiscountAmount,
                'taxable_value' => $totalTaxableValue,
                'gst_rate' => $invoiceGstRate,
                'gst_amount' => $totalGstAmount,
                'total_amount' => $totalFinalAmount,
                'vendor_id' => $vendor ? $vendor->id : null,
                'vendor_name' => $vendor ? $vendor->name : 'N/A'
            ];
        }

        return $invoiceData;
    }

    private function getStateInfo($stateId)
    {
        if (empty($stateId)) {
            return [
                'code' => '',
                'name' => '',
                'code_name' => ''
            ];
        }

        $state = StateMaster::where('id', $stateId)->first();

        if ($state) {
            return [
                'code' => $state->code,
                'name' => $state->name,
                'code_name' => $state->code . ' - ' . $state->name
            ];
        }

        return [
            'code' => '',
            'name' => '',
            'code_name' => ''
        ];
    }

    private function getItemDescription($invoice)
    {
        $po_details = $invoice->po;
        if ($po_details) {
            $articleInfo = json_decode($po_details->article_info, true) ?: [];
            return $articleInfo['Article description'] ?? 'Garment Items';
        }
        return 'Garment Items';
    }

    private function getHsnCodeAndUom($invoice)
    {
        $po_details = $invoice->po;
        $vendor = $invoice->vendor;
        $packIds = explode(',', $invoice->pack_ids);

        // Default values
        $hsn_code = '';
        $uom = 'PCS';

        if (!$po_details || !$vendor) {
            return ['hsn_code' => $hsn_code, 'uom' => $uom];
        }

        // Get first packing item to determine size and color for lookup
        $firstPackingItem = PackingListItem::whereIn('packing_list_id', $packIds)
            ->first();

        if (!$firstPackingItem) {
            return ['hsn_code' => $hsn_code, 'uom' => $uom];
        }

        $size = $firstPackingItem->size;
        $color = $firstPackingItem->color;

        // Get article info
        $articleInfo = json_decode($po_details->article_info, true) ?: [];

        // Apply vendor-specific logic (same as generateInvoice)
        switch ($vendor->id) {
            case 1:
            case 5:
            case 6:
                $itm = PoItems::where('po_id', $po_details->id)
                    ->where('size', $size)
                    ->where('color', $color)
                    ->first();
                if ($itm) {
                    $hsn_code = $itm->hsn_code;
                    $uom = $itm->uom;
                }
                break;

            case 4:
                $poSize = PoSizes::where('po_id', $po_details->id)
                    ->where('size', $size)
                    ->where('color', $color)
                    ->first();
                if ($poSize) {
                    $hsn_code = $poSize->hsn_code ?? '';
                    $uom = $poSize->uom ?? 'PCS';
                }

                $itm = PoItems::where('po_id', $po_details->id)
                    ->where('size', $size)
                    ->where('color', $color)
                    ->first();
                if ($itm) {
                    $hsn_code = $hsn_code ?: $itm->hsn_code;
                    $uom = $uom ?: $itm->uom;
                }
                break;

            case 2:
            case 3:
                $itm = PoItems::where('po_id', $po_details->id)
                    ->where('size', $size)
                    ->where('color', $color)
                    ->first();
                if ($itm) {
                    $hsn_code = $itm->hsn_code ?? '';
                    $uom = $itm->uom ?? 'PCS';
                }
                break;

            default:
                // Default case - try to get from PoItems
                $itm = PoItems::where('po_id', $po_details->id)
                    ->where('size', $size)
                    ->where('color', $color)
                    ->first();
                if ($itm) {
                    $hsn_code = $itm->hsn_code;
                    $uom = $itm->uom;
                }
                break;
        }

        // Fallback - if still empty, get any HSN code from PoItems for this PO
        if (empty($hsn_code)) {
            $fallbackItem = PoItems::where('po_id', $po_details->id)
                ->whereNotNull('hsn_code')
                ->first();
            if ($fallbackItem) {
                $hsn_code = $fallbackItem->hsn_code;
                $uom = $fallbackItem->uom ?? 'PCS';
            }
        }

        return [
            'hsn_code' => $hsn_code ?: '',
            'uom' => $uom ?: 'PCS'
        ];
    }
}
