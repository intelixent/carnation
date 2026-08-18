<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rare Rabbit Packing List</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 10px;
            padding: 10px;
            font-size: 10px;
            line-height: 1.2;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th,
        td {
            border: 1px solid #000;
            padding: 4px;
            text-align: center;
            font-size: 9px;
        }

        th {
            background-color: #f0f0f0;
            font-weight: bold;
        }

        .header-table td {
            text-align: left;
            border: none;
            padding: 2px 4px;
        }

        .header-table td.label {
            font-weight: bold;
            width: 38%;
            white-space: nowrap;
        }

        .header-wrap td {
            border: none;
            vertical-align: top;
            padding: 0 6px;
        }

        .summary-table {
            border-collapse: collapse;
        }

        .summary-table th,
        .summary-table td {
            border: 1px solid #000;
            font-size: 8px;
            padding: 2px;
        }

        .totals-row {
            font-weight: bold;
            background-color: #f5f5f5;
        }

        .section-title {
            background-color: #000;
            color: #fff;
            text-align: center;
            font-weight: bold;
            padding: 3px;
            margin-top: 10px;
        }

        .totals-box {
            width: 100%;
            border-collapse: collapse;
        }

        .totals-box td {
            border: 1px solid #000;
            padding: 3px 6px;
        }

        .totals-box td.label {
            font-weight: bold;
            text-align: left;
        }

        .totals-box td.value {
            text-align: right;
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

    @php
    $articleInfo = json_decode($packing_list->po->article_info ?? '{}', true) ?: [];
    $channel = $articleInfo['channel'] ?? '';
    $warehouseCity = $articleInfo['warehouse_city_name'] ?? '';
    $categoryStyleColor = trim($channel . ($channel && $warehouseCity ? '-' : '') . $warehouseCity);
    @endphp

    <!-- Company / PO Header -->
    <table class="header-wrap">
        <tr>
            <!-- LEFT: Brand / Category / Style / Buyer block -->
            <td style="width:58%;">
                <table class="header-table">
                    <tr>
                        <td class="label">Brand :</td>
                        <td>RR</td>
                    </tr>
                    <tr>
                        <td class="label">Category :</td>
                        <td>{{ $articleInfo['category'] ?? '' }}</td>
                    </tr>
                    <tr>
                        <td class="label">Style :</td>
                        <td>{{ $styleDescriptionsDisplay ?: '' }}</td>
                    </tr>
                    <tr>
                        <td class="label">Buyer (Warehouse) :</td>
                        <td>{{ $packing_list->po->vendor_customer_name }}</td>
                    </tr>
                    <tr>
                        <td class="label">Buyer Address :</td>
                        <td>{{ $packing_list->po->vendor_del_adr }}</td>
                    </tr>
                    <tr>
                        <td class="label">GSTIN :</td>
                        <td>{{ $packing_list->po->vendor_gst }}</td>
                    </tr>
                </table>
            </td>

            <!-- RIGHT: PO / Vendor block -->
            <td style="width:42%;">
                <table class="header-table">
                    <tr>
                        <td class="label">PO No :</td>
                        <td>{{ $poNum }}</td>
                    </tr>
                    <tr>
                        <td class="label">Vendor Name :</td>
                        <td>{{ $articleInfo['vendor_name'] ?? '' }}</td>
                    </tr>
                    <tr>
                        <td class="label">Vendor Address :</td>
                        <td>{{ $packing_list->po->vendor_com_adr }}</td>
                    </tr>
                    <tr>
                        <td class="label">Vendor GSTIN :</td>
                        <td>{{ $packing_list->po->vendor_gst }}</td>
                    </tr>
                    <tr>
                        <td class="label">Channel :</td>
                        <td>{{ $categoryStyleColor }}</td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    <div class="section-title">SUMMARY PACKING LIST</div>

    <!-- Main Carton Table -->
    @if(isset($tableData) && $tableData)
    <table>
        <thead>
            <tr>
                <th rowspan="2">CTN<br>FROM</th>
                <th rowspan="2">CTN<br>TO</th>
                <th rowspan="2">TOTAL<br>CTNS</th>
                <th rowspan="2">STYLE CODE</th>
                <th rowspan="2">CATEGORY-STYLE+COLOUR</th>
                <th rowspan="2">COLOUR</th>
                <th rowspan="2">PALLETE</th>
                <th colspan="{{ count($tableData['sizeOrder']) }}">SIZE WISE QTY</th>
                <th rowspan="2">TOTAL PCS<br>PER CTN</th>
                <th rowspan="2">TOTAL<br>PCS</th>
                <th rowspan="2">CTN DIM<br>(CM)</th>
                <th rowspan="2">NET WT<br>(per ctn)</th>
                <th rowspan="2">GRS WT<br>(per ctn)</th>
            </tr>
            <tr>
                @foreach($tableData['sizeOrder'] as $size)
                <th>{{ $size }}</th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @foreach($tableData['rows'] as $row)
            <tr>
                <td>{{ $row['ctn_first'] }}</td>
                <td>{{ $row['ctn_last'] }}</td>
                <td>{{ $row['ttl_ctn'] }}</td>
                <td>{{ $row['style_code'] ?? $row['article_number'] ?? '' }}</td>
                <td style="text-align:left;">{{ $row['category_style_color'] ?? '' }}</td>
                <td>{{ $row['color'] }}</td>
                <td>&nbsp;</td>
                @foreach($tableData['sizeOrder'] as $size)
                <td>{{ ($row['per_size'][$size] ?? 0) > 0 ? $row['per_size'][$size] : '' }}</td>
                @endforeach
                <td>{{ $row['per_ctn'] }}</td>
                <td>{{ $row['total'] }}</td>
                <td>{{ $row['ctn_dim'] }}</td>
                <td>{{ $row['net_wt_per'] }}</td>
                <td>{{ $row['grs_wt_per'] }}</td>
            </tr>
            @endforeach

            <tr class="totals-row">
                <td colspan="3">TOTAL CTNS: {{ $totalCtn }}</td>
                <td></td>
                <td></td>
                <td></td>
                <td></td>
                @foreach($tableData['sizeOrder'] as $size)
                <td>{{ ($tableData['totals']['per_size'][$size] ?? 0) > 0 ? $tableData['totals']['per_size'][$size] : '' }}</td>
                @endforeach
                <td></td>
                <td>{{ $tableData['totals']['total_pieces'] ?? '' }}</td>
                <td></td>
                <td>{{ round($totalNetWeight, 2) }}</td>
                <td>{{ round($totalGrossWeight, 2) }}</td>
            </tr>
        </tbody>
    </table>
    @endif

    <!-- Order vs Packed Summary -->
    <table class="summary-table" style="margin-top: 15px;">
        <thead>
            <tr>
                <th colspan="{{ 1 + $all_sizes->count() + 1 }}">{{ $categoryStyleColor }}</th>
            </tr>
            <tr>
                <th colspan="{{ 1 + $all_sizes->count() + 1 }}">
                    {{ $styleDescriptionsDisplay }}
                </th>
            </tr>
            <tr>
                <th>&nbsp;</th>
                @foreach($all_sizes as $size)
                <th>{{ $size }}</th>
                @endforeach
                <th>TOTAL</th>
            </tr>
        </thead>
        <tbody>
            @php $orderTotal = 0; $packedTotal = 0; $shortTotal = 0; @endphp
            <tr>
                <td>ORDER QTY</td>
                @foreach($all_sizes as $size)
                @php $q = $ordered_quantities->get($size, 0); $orderTotal += $q; @endphp
                <td>{{ $q }}</td>
                @endforeach
                <td><strong>{{ $orderTotal }}</strong></td>
            </tr>
            <tr>
                <td>PACKED QTY</td>
                @foreach($all_sizes as $size)
                @php $q = $packed_quantities->get($size, 0); $packedTotal += $q; @endphp
                <td>{{ $q }}</td>
                @endforeach
                <td><strong>{{ $packedTotal }}</strong></td>
            </tr>
            <tr>
                <td>SHORTAGE/EXCESS QTY</td>
                @foreach($all_sizes as $size)
                @php $b = $balances->get($size, 0); $shortTotal += $b; @endphp
                <td>{{ $b }}</td>
                @endforeach
                <td><strong>{{ $shortTotal }}</strong></td>
            </tr>
            <tr>
                <td>SHORTAGE/EXCESS %</td>
                @foreach($all_sizes as $size)
                @php $ordered = $ordered_quantities->get($size, 0); $pct = $percentages->get($size, 0); @endphp
                <td>{{ $ordered > 0 ? $pct . '%' : '#DIV/0!' }}</td>
                @endforeach
                <td><strong>{{ $orderTotal > 0 ? round(($packedTotal / $orderTotal) * 100, 2) . '%' : '#DIV/0!' }}</strong></td>
            </tr>
        </tbody>
    </table>

    <!-- Overall totals card (below the summary table) -->
    <table class="totals-box" style="width:45%; margin-top: 15px;">
        <tr>
            <td class="label">TOTAL CTNS</td>
            <td class="value">{{ $totalCtn }} CTNS</td>
        </tr>
        <tr>
            <td class="label">TOTAL PCS</td>
            <td class="value">{{ $tableData['totals']['total_pieces'] ?? '' }} PCS</td>
        </tr>
        <tr>
            <td class="label">TOTAL NT WT</td>
            <td class="value">{{ round($totalNetWeight, 2) }} KGS</td>
        </tr>
        <tr>
            <td class="label">TOTAL GR WT</td>
            <td class="value">{{ round($totalGrossWeight, 2) }} KGS</td>
        </tr>
        <tr>
            <td class="label">CTN SIZE</td>
            <td class="value">{{ $ctnDimDisplay }} CMS</td>
        </tr>
        <tr>
            <td class="label">TOTAL CBM</td>
            <td class="value">{{ $totalCbm ? round($totalCbm, 3) : '' }} CBM</td>
        </tr>
    </table>

</body>

</html>