<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithMapping;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

class SummaryExport implements FromCollection, WithHeadings, WithStyles, WithTitle, ShouldAutoSize, WithMapping
{
    protected $summaryData;
    protected $date;
    protected $vendor;
    protected $rowIndex = 0;

    public function __construct($summaryData, $date, $vendor)
    {
        $this->summaryData = $summaryData;
        $this->date = $date;
        $this->vendor = $vendor;
    }

    public function collection()
    {
        $data = collect($this->summaryData);

        // Add total row
        if (count($this->summaryData) > 0) {
            $data->push([
                'is_total' => true,
                'vendor' => '',
                'packing_table_no' => '',
                'job_no' => 'Total:',
                'po_qty' => array_sum(array_column($this->summaryData, 'po_qty')),
                'ors_qty' => array_sum(array_column($this->summaryData, 'ors_qty')),
                'packed' => array_sum(array_column($this->summaryData, 'packed')),
                'yet_to_pack' => array_sum(array_column($this->summaryData, 'yet_to_pack')),
            ]);
        }

        return $data;
    }

    protected function formatNumberForExport($value)
    {
        $v = $value ?? 0;
        $int = (int)$v;
        return $int === 0 ? '0' : $int;
    }

    public function map($row): array
    {
        // increment rowIndex only for real rows (not the final total row)
        if (isset($row['is_total']) && $row['is_total']) {
            return [
                '',
                '',
                '',
                'Total:',
                $this->formatNumberForExport($row['po_qty'] ?? 0),
                $this->formatNumberForExport($row['ors_qty'] ?? 0),
                $this->formatNumberForExport($row['packed'] ?? 0),
                $this->formatNumberForExport($row['yet_to_pack'] ?? 0),
            ];
        }

        $this->rowIndex++;

        return [
            $this->rowIndex,
            $row['vendor'] ?? '',
            $row['packing_table_no'] ?? '',
            $row['job_no'] ?? '',
            $this->formatNumberForExport($row['po_qty'] ?? 0),
            $this->formatNumberForExport($row['ors_qty'] ?? 0),
            $this->formatNumberForExport($row['packed'] ?? 0),
            $this->formatNumberForExport($row['yet_to_pack'] ?? 0),
        ];
    }

    public function headings(): array
    {
        return [
            ['Daily Packing Report - Summary'],
            ['Date: ' . $this->date . ' | Vendor: ' . $this->vendor],
            [],
            ['S.No', 'Vendor', 'Packing Table No', 'Job No', 'PO Qty', 'ORS Qty', 'Packed', 'Yet to Pack']
        ];
    }

    public function styles(Worksheet $sheet)
    {
        // Merge title cells - we have 8 columns
        $sheet->mergeCells('A1:H1');
        $sheet->mergeCells('A2:H2');

        // Title styling
        $sheet->getStyle('A1')->applyFromArray([
            'font' => [
                'bold' => true,
                'size' => 16,
                'color' => ['rgb' => '000000']
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => 'E3F2FD']
            ]
        ]);

        // Date and vendor info styling
        $sheet->getStyle('A2')->applyFromArray([
            'font' => [
                'bold' => true,
                'size' => 12,
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
            ]
        ]);

        // Header styling
        $sheet->getStyle('A4:H4')->applyFromArray([
            'font' => [
                'bold' => true,
                'color' => ['rgb' => 'FFFFFF']
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '0d6efd']
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER
            ]
        ]);

        // compute data rows count correctly (include total row if present)
        $dataCount = count($this->summaryData) + (count($this->summaryData) > 0 ? 1 : 0);
        $lastRow = 4 + $dataCount; // headers occupy 4 rows, data starts on row 5

        // Data rows border
        $sheet->getStyle("A4:H{$lastRow}")->applyFromArray([
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['rgb' => '000000']
                ]
            ]
        ]);

        // Custom number format includes explicit zero-format so zero will show when numeric
        $sheet->getStyle("E5:H{$lastRow}")->getNumberFormat()
            ->setFormatCode('0; -0; 0');

        // Right align number columns
        $sheet->getStyle("E5:H{$lastRow}")->getAlignment()
            ->setHorizontal(Alignment::HORIZONTAL_RIGHT);

        // Total row styling
        if ($dataCount > 0) {
            $sheet->getStyle("A{$lastRow}:H{$lastRow}")->applyFromArray([
                'font' => [
                    'bold' => true,
                ],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => 'E0E0E0']
                ]
            ]);
        }

        return [];
    }

    public function title(): string
    {
        return 'Summary';
    }
}
