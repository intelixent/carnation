<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width,initial-scale=1.0">
    <title>Packing List - Aditya</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 5px;
            padding: 5px;
            font-size: 10px;
            line-height: 1.2;
        }

        .header {
            font-size: 18px;
            font-weight: bold;
            margin-bottom: 10px;
            text-align: center;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
        }

        th,
        td {
            border: 1px solid #000;
            padding: 4px;
            font-size: 9px;
            text-align: center;
        }

        th {
            background-color: #f0f0f0;
            font-weight: bold;
        }

        .header-info {
            margin-bottom: 15px;
        }

        .header-info table {
            border-collapse: collapse;
            width: 100%;
        }

        .header-info th {
            background-color: #bbb;
            padding: 8px;
            border: 1px solid #000;
            text-align: left;
            font-weight: bold;
            font-size: 10px;
        }

        .header-info td {
            padding: 8px;
            border: 1px solid #000;
            text-align: left;
            font-size: 10px;
        }

        .summary-section {
            margin-top: 15px;
        }

        .details-section {
            margin-top: 10px;
        }

        @media print {
            @page {
                size: A4 landscape;
                margin: 10mm 5mm 10mm 5mm !important;
            }
        }
    </style>
</head>

<body onLoad="window.print()">

    <div class="header">PACKING LIST</div>

    <!-- Header Information Section -->
    <div class="header-info">
        <table style="border-collapse: collapse; width: 100%;">
            <tr>
                <th style="background-color: #bbb; padding: 8px; border: 1px solid #000; text-align: left; width: 15%;">Consignee</th>
                <td style="padding: 8px; border: 1px solid #000; text-align: left;" colspan="5">{{ $packing_list->po->vendor_com_adr }}</td>
            </tr>
            <tr>
                <th style="background-color: #bbb; padding: 8px; border: 1px solid #000; text-align: left;">BUYER</th>
                <td style="padding: 8px; border: 1px solid #000; text-align: left;" colspan="5">{{ $packing_list->po->vendor_del_adr }}</td>
            </tr>
            <tr>
                <th style="background-color: #bbb; padding: 8px; border: 1px solid #000; text-align: left;">Vendor</th>
                <td style="padding: 8px; border: 1px solid #000; text-align: left;" colspan="5">CARNATION CREATIONS PRIVATE LIMITED , 376/1 , NARASIMHANAICKEN PALAYAM, COIMBATORE, TAMILNADU,INDIA.641031</td>
            </tr>
            <tr>
                <th style="background-color: #bbb; padding: 8px; border: 1px solid #000; text-align: left;">LOCATION</th>
                <td style="padding: 8px; border: 1px solid #000; text-align: left;" colspan="5">{{ $packing_list->location ?? '' }}</td>
            </tr>
            <tr>
                <th style="background-color: #bbb; padding: 8px; border: 1px solid #000; text-align: left;">PO NO</th>
                <td style="padding: 8px; border: 1px solid #000; text-align: left;" colspan="5">{{ $poNum }}</td>
            </tr>
        </table>
    </div>

    <!-- Main Items Table -->
    <div class="details-section">
        <table>
            <thead>
                <!-- First header row with colspan -->
                <tr>
                    <th rowspan="2">STYLE CODE</th>
                    <th rowspan="2">ARTICLE DESCRIPTION</th>
                    <th rowspan="2">PO No.</th>
                    <th rowspan="2">Color</th>
                    @if($tableData && isset($tableData['sizeOrder']))
                    <th colspan="{{ count($tableData['sizeOrder']) }}">Size/Qty</th>
                    @endif
                    <th rowspan="2">Pcs/Ctn</th>
                    <th rowspan="2">Total</th>
                    <th rowspan="2">Ctn Numbers</th>
                    <th rowspan="2">Total CTNS</th>
                </tr>
                <!-- Second header row with size columns -->
                <tr>
                    @if($tableData && isset($tableData['sizeOrder']))
                    @foreach($tableData['sizeOrder'] as $size)
                    <th>{{ $size }}</th>
                    @endforeach
                    @endif
                </tr>
            </thead>
            <tbody>
                @if($tableData && isset($tableData['rows']))
                @foreach($tableData['rows'] as $row)
                <tr>
                    <td>{{ $row['article_number'] }}</td>
                    <td style="text-align: left;">{{ $row['style_description'] }}</td>
                    <td>{{ $poNum }}</td>
                    <td>{{ strtoupper($row['color']) }}</td>
                    @foreach($tableData['sizeOrder'] as $size)
                    <td>{{ $row['per_size'][$size] ?? 0 ?: '' }}</td>
                    @endforeach
                    <td>{{ $row['per_ctn'] }}</td>
                    <td>{{ $row['total'] }}</td>
                    <td>{{ $row['ctn_range'] }}</td>
                    <td>{{ $row['total_ctns'] }}</td>
                </tr>
                @endforeach

                <!-- Total Row -->
                <tr style="font-weight: bold; background-color: #f0f0f0;">
                    <td colspan="4">Total</td>
                    @if($tableData && isset($tableData['totals']))
                    @foreach($tableData['sizeOrder'] as $size)
                    <td>{{ $tableData['totals']['per_size'][$size] ?? 0 }}</td>
                    @endforeach
                    @endif
                    <td></td>
                    <td>{{ $tableData['totals']['total_pieces'] ?? 0 }}</td>
                    <td></td>
                    <td>{{ $tableData['totals']['carton_count'] ?? 0 }}</td>
                </tr>

                <!-- Grand Total Row -->
                <tr style="font-weight: bold;">
                    <td colspan="4">Grand Total</td>
                    @if($tableData && isset($tableData['totals']))
                    @foreach($tableData['sizeOrder'] as $size)
                    <td>{{ $tableData['totals']['per_size'][$size] ?? 0 }}</td>
                    @endforeach
                    @endif
                    <td></td>
                    <td>{{ $tableData['totals']['total_pieces'] ?? 0 }}</td>
                    <td></td>
                    <td></td>
                </tr>

                <!-- Order Qty Row -->
                <tr style="background-color: #e6e6e6;">
                    <td colspan="4">Order Qty</td>
                    @if($tableData && isset($tableData['sizeOrder']))
                    @foreach($tableData['sizeOrder'] as $size)
                    <td>{{ $ordered_quantities->get($size, 0) }}</td>
                    @endforeach
                    @endif
                    <td></td>
                    <td>{{ $ordered_quantities->sum() }}</td>
                    <td></td>
                    <td></td>
                </tr>

                <!-- Percentage Row -->
                <tr style="background-color: #f9f9f9;">
                    <td colspan="4">% of Disp</td>
                    @if($tableData && isset($tableData['sizeOrder']))
                    @foreach($tableData['sizeOrder'] as $size)
                    <td>{{ isset($percentages[$size]) ? $percentages[$size] . '%' : '0%' }}</td>
                    @endforeach
                    @endif
                    <td></td>
                    <td>
                        @php
                        $totalOrdered = $ordered_quantities->sum();
                        $totalPacked = $tableData['totals']['total_pieces'] ?? 0;
                        $totalPercentage = $totalOrdered > 0 ? round(($totalPacked / $totalOrdered) * 100) : 0;
                        @endphp
                        {{ $totalPercentage }}%
                    </td>
                    <td></td>
                    <td></td>
                </tr>
                @endif
            </tbody>
        </table>
    </div>

    <!-- Summary Section -->
    <div class="summary-section">
        <table>
            <thead>
                <tr>
                    <th></th>
                    <th>PO No.</th>
                    <th>Color</th>
                    @if($tableData && isset($tableData['sizeOrder']))
                    @foreach($tableData['sizeOrder'] as $size)
                    <th>{{ $size }}</th>
                    @endforeach
                    @endif
                    <th>Total</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>Pack Qty</td>
                    <td>{{ $poNum }}</td>
                    <td>{{ strtoupper($uniqueColorDisplay) }}</td>
                    @if($tableData && isset($tableData['totals']))
                    @foreach($tableData['sizeOrder'] as $size)
                    <td>{{ $tableData['totals']['per_size'][$size] ?? 0 }}</td>
                    @endforeach
                    <td>{{ $tableData['totals']['total_pieces'] ?? 0 }}</td>
                    @endif
                </tr>
                <tr>
                    <td>Order Qty</td>
                    <td>{{ $poNum }}</td>
                    <td>{{ strtoupper($uniqueColorDisplay) }}</td>
                    @if($tableData && isset($tableData['sizeOrder']))
                    @foreach($tableData['sizeOrder'] as $size)
                    <td>{{ $ordered_quantities->get($size, 0) }}</td>
                    @endforeach
                    @endif
                    <td>{{ $ordered_quantities->sum() }}</td>
                </tr>
            </tbody>
        </table>
    </div>

    <!-- Footer Information -->
    <div style="margin-top: 20px;">
        <table style="border: none;">
            <tr>
                <td style="border: none;"><strong>NET WEIGHT : {{ number_format($totalNetWeight, 2) }} KGS</strong></td>
                <td style="border: none;"><strong>TOTAL PCS {{ $tableData['totals']['total_pieces'] ?? 0 }}</strong></td>
            </tr>
            <tr>
                <td style="border: none;"><strong>GROSS WEIGHT : {{ number_format($totalGrossWeight, 2) }} KGS</strong></td>
                <td style="border: none;"><strong>TOTAL CARTONS {{ $totalCtn }}</strong></td>
            </tr>
            <tr>
                <td style="border: none;"><strong>CARTON DIMENTION IN CM {{ $ctnDimDisplay }}</strong></td>
                <td style="border: none;"><strong>TOTAL CBM : {{ number_format($totalCbm, 2) }}</strong></td>
            </tr>
        </table>
    </div>
</body>

</html>