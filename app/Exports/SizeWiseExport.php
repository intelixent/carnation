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

class SizeWiseExport implements FromCollection, WithHeadings, WithStyles, WithTitle, ShouldAutoSize, WithMapping
{
    protected $sizeWiseData;
    protected $allSizes;
    protected $date;
    protected $vendor;
    protected $rowIndex = 0;

    public function __construct($sizeWiseData, $allSizes, $date, $vendor)
    {
        $this->sizeWiseData = $sizeWiseData;
        $this->allSizes = $allSizes;
        $this->date = $date;
        $this->vendor = $vendor;
    }

    public function collection()
    {
        $data = collect($this->sizeWiseData);

        // Add total row
        if (count($this->sizeWiseData) > 0) {
            $totalRow = [
                'is_total' => true,
                'vendor' => '',
                'packing_table_no' => '',
                'job_no' => '',
                'color' => 'Total:',
                'po_qty' => array_sum(array_column($this->sizeWiseData, 'po_qty')),
                'ors_qty' => array_sum(array_column($this->sizeWiseData, 'ors_qty')),
                'size_wise_packed' => []
            ];

            // Add size totals
            foreach ($this->allSizes as $size) {
                $totalRow['size_wise_packed'][$size] = array_sum(array_map(function($row) use ($size) {
                    return $row['size_wise_packed'][$size] ?? 0;
                }, $this->sizeWiseData));
            }

            $totalRow['packed'] = array_sum(array_column($this->sizeWiseData, 'packed'));
            $totalRow['yet_to_pack'] = array_sum(array_column($this->sizeWiseData, 'yet_to_pack'));

            $data->push($totalRow);
        }

        return $data;
    }

    // Helper to ensure zeros are visible in Excel: return "0" string when value is zero.
    protected function formatNumberForExport($value)
    {
        $v = $value ?? 0;
        $int = (int)$v;
        return $int === 0 ? '0' : $int;
    }

    public function map($row): array
    {
        if (isset($row['is_total']) && $row['is_total']) {
            $mapped = [
                '',
                '',
                '',
                '',
                'Total:',
                $this->formatNumberForExport($row['po_qty'] ?? 0),
                $this->formatNumberForExport($row['ors_qty'] ?? 0),
            ];

            // Add size totals
            foreach ($this->allSizes as $size) {
                $mapped[] = $this->formatNumberForExport($row['size_wise_packed'][$size] ?? 0);
            }

            $mapped[] = $this->formatNumberForExport($row['packed'] ?? 0);
            $mapped[] = $this->formatNumberForExport($row['yet_to_pack'] ?? 0);

            return $mapped;
        }

        $this->rowIndex++;

        $mapped = [
            $this->rowIndex,
            $row['vendor'] ?? '',
            $row['packing_table_no'] ?? '',
            $row['job_no'] ?? '',
            $row['color'] ?? '',
            $this->formatNumberForExport($row['po_qty'] ?? 0),
            $this->formatNumberForExport($row['ors_qty'] ?? 0),
        ];

        // Add size columns
        foreach ($this->allSizes as $size) {
            $mapped[] = $this->formatNumberForExport($row['size_wise_packed'][$size] ?? 0);
        }

        $mapped[] = $this->formatNumberForExport($row['packed'] ?? 0);
        $mapped[] = $this->formatNumberForExport($row['yet_to_pack'] ?? 0);

        return $mapped;
    }

    public function headings(): array
    {
        $headers = ['S.No', 'Vendor', 'Packing Table No', 'Job No', 'Color', 'PO Qty', 'ORS Qty'];

        // Add size headers
        foreach ($this->allSizes as $size) {
            $headers[] = $size;
        }

        $headers[] = 'Packed';
        $headers[] = 'Yet to Pack';

        return [
            ['Daily Packing Report - Size Wise'],
            ['Date: ' . $this->date . ' | Vendor: ' . $this->vendor],
            [],
            $headers
        ];
    }

    public function styles(Worksheet $sheet)
    {
        // Correct column count: 7 base columns + sizes + 2 numeric columns = 9 + count(sizes)
        $columnCount = 9 + count($this->allSizes);
        $lastColumn = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($columnCount);

        // Merge title cells
        $sheet->mergeCells("A1:{$lastColumn}1");
        $sheet->mergeCells("A2:{$lastColumn}2");

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
        $sheet->getStyle("A4:{$lastColumn}4")->applyFromArray([
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
        $dataCount = count($this->sizeWiseData) + (count($this->sizeWiseData) > 0 ? 1 : 0);
        $lastRow = 4 + $dataCount; // headers occupy 4 rows, data starts on row 5

        // Data rows border
        $sheet->getStyle("A4:{$lastColumn}{$lastRow}")->applyFromArray([
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['rgb' => '000000']
                ]
            ]
        ]);

        // Use a custom number format that explicitly specifies the zero-format.
        // Custom format sections: positive;negative;zero
        $sheet->getStyle("F5:{$lastColumn}{$lastRow}")->getNumberFormat()
            ->setFormatCode('0; -0; 0');

        // Right align number columns
        $sheet->getStyle("F5:{$lastColumn}{$lastRow}")->getAlignment()
            ->setHorizontal(Alignment::HORIZONTAL_RIGHT);

        // Total row styling
        if ($dataCount > 0) {
            $sheet->getStyle("A{$lastRow}:{$lastColumn}{$lastRow}")->applyFromArray([
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
        return 'Size Wise';
    }
}
