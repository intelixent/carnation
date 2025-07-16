<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Puma Packing List</title>
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

        .summary-table th,
        .summary-table td {
            font-size: 8px;
            padding: 2px;
        }

        .summary-section {
            margin-top: 20px;
        }

        .totals-row {
            font-weight: bold;
            background-color: #f5f5f5;
        }
    </style>
</head>

<body>
    <!-- Main Packing Table -->
    <table style="width:100%;">
        <!-- Company Header -->
        <tr>
            <td colspan="5" style="text-align: center; font-weight: bold; padding-bottom: 5px;">
                CARNATION CREATIONS PVT LTD
            </td>
        </tr>
        <tr>
            <td colspan="5" style="text-align: center; padding-bottom: 10px;">
                376/1 NSN PALAYAM, METTUPALAYAM ROAD, COIMBATORE - 641031
            </td>
        </tr>

        <!-- Packing List Header -->
        <tr>
            <td colspan="5" style="background-color: black; color: white; text-align: center; font-weight: bold; padding: 3px;">
                PACKING LIST
            </td>
        </tr>

        <!-- Address Section -->
        <tr>
            <td style="width:50%; text-align: left; vertical-align: top;">
                <strong>Consignee</strong>
            </td>
            <td colspan="4" style="width:50%; text-align: left; vertical-align: top;">
                <strong>Delivery Address</strong>
            </td>
        </tr>

        <tr>
            <td style="width:50%; text-align: left; vertical-align: top;">
                <strong>{{ $packing_list->po->vendor_com_adr }}</strong>
            </td>
            <td colspan="4" style="width:40%; text-align: left; vertical-align: top;">
                <strong>{{ $packing_list->po->vendor_del_adr }}</strong>
            </td>
        </tr>

        <!-- Product Information -->
        @php
        $articleInfo = json_decode($packing_list->po->article_info, true);
        $description = $articleInfo['style_description'] ?? '';
        $article = $articleInfo['article_number'] ?? '';
        $customer_po_no = $articleInfo['customer_po_no'] ?? '';
        $color = $articleInfo['color'] ?? '';
        @endphp

        <!-- Product Details Rows -->
        <tr>
            <td style="width:50%; text-align: right; font-weight: bold;">Description :</td>
            <td style="width:20%; text-align: left;">{{ $description }}</td>
            <td style="width:10%; border: none;"></td>
            <td style="width:10%; border: none;"></td>
            <td style="width:10%; border-left: none; border-top: none; border-bottom: none;"></td>
        </tr>
        <tr>
            <td style="width:50%; text-align: right; font-weight: bold;">PO No :</td>
            <td style="width:20%; text-align: left;">{{ $packing_list->po->po_num }}</td>
            <td style="width:10%; border: none;"></td>
            <td style="width:10%; border: none;"></td>
            <td style="width:10%; border-left: none; border-top: none; border-bottom: none;"></td>
        </tr>
        <tr>
            <td style="width:50%; text-align: right; font-weight: bold;">Style No / Code :</td>
            <td style="width:20%; text-align: left;">{{ $article }}</td>
            <td style="width:10%; border: none;"></td>
            <td style="width:10%; border: none;"></td>
            <td style="width:10%; border-left: none; border-top: none; border-bottom: none;"></td>
        </tr>
        <tr>
            <td style="width:50%; text-align: right; font-weight: bold;">Colour :</td>
            <td style="width:20%; text-align: left;">{{ $packing_list->color }}</</td>
            <td style="width:10%; border: none;"></td>
            <td style="width:10%; border: none;"></td>
            <td style="width:10%; border-left: none; border-top: none; border-bottom: none;"></td>
        </tr>
        <tr>
            <td style="width:50%; text-align: right; font-weight: bold;">Customer PO No :</td>
            <td style="width:20%; text-align: left;">{{ $customer_po_no }}</td>
            <td style="width:10%; border: none;"></td>
            <td style="width:10%; text-align: right;">Date:</td>
            <td style="width:10%; text-align: left;"></td>
        </tr>
    </table>

    @if(isset($tableData) && $tableData)
    @php
    // Calculate totals in the view
    $totals = [
    'carton_count' => 0,
    'per_size' => array_fill_keys($tableData['sizeOrder'], 0),
    'total_pieces' => 0,
    'net_weight' => 0,
    'gross_weight' => 0
    ];

    // Calculate totals from data rows
    foreach($tableData['rows'] as $row) {
    $totals['carton_count'] += $row['ttl_ctn'];
    $totals['total_pieces'] += $row['total'];
    $totals['net_weight'] += $row['net_wt_total'];
    $totals['gross_weight'] += $row['grs_wt_total'];

    foreach($tableData['sizeOrder'] as $size) {
    $totals['per_size'][$size] += $row['per_size'][$size] ?? 0;
    }
    }

    // Count non-total rows to determine rowspan
    $nonTotalRows = count($tableData['rows']);
    @endphp

    <table>
        <thead>
            <tr>
                <th>CTN.NO</th>
                <th>TTL CTN</th>
                <th>Color</th>
                @foreach($tableData['sizeOrder'] as $size)
                <th>{{ $size }}</th>
                @endforeach
                <th>PER CTN</th>
                <th>TOTAL</th>
                <th>NET WT</th>
                <th>GRS WT</th>
                <th>NET WT</th>
                <th>GRS WT</th>
                <th>CTN DIM</th>
            </tr>
        </thead>
        <tbody>
            @php $colorRowSpanUsed = false; @endphp
            @foreach($tableData['rows'] as $index => $row)
            <tr>
                <td>{{ $row['ctn_range'] }}</td>
                <td>{{ $row['ttl_ctn'] }}</td>
                @if(!$colorRowSpanUsed)
                <!-- First row gets the rowspan -->
                <td rowspan="{{ $nonTotalRows }}">{{ $color }}</td>
                @php $colorRowSpanUsed = true; @endphp
                @endif
                <!-- Skip color cell for subsequent rows as it's handled by rowspan -->
                @foreach($tableData['sizeOrder'] as $size)
                <td>{{ ($row['per_size'][$size] ?? 0) > 0 ? $row['per_size'][$size] : '' }}</td>
                @endforeach
                <td>{{ $row['per_ctn'] }}</td>
                <td>{{ $row['total'] }}</td>
                <td>{{ $row['net_wt_per'] }}</td>
                <td>{{ $row['grs_wt_per'] }}</td>
                <td>{{ $row['net_wt_total'] }}</td>
                <td>{{ $row['grs_wt_total'] }}</td>
                <td>{{ $row['ctn_dim'] }}</td>
            </tr>
            @endforeach

            <!-- Add totals row -->
            <tr class="totals-row">
                <td>TOTAL</td>
                <td>{{ $totals['carton_count'] }}</td>
                <td></td> <!-- Empty color cell for totals -->
                @foreach($tableData['sizeOrder'] as $size)
                <td>{{ $totals['per_size'][$size] > 0 ? $totals['per_size'][$size] : '' }}</td>
                @endforeach
                <td>Total</td>
                <td>{{ $totals['total_pieces'] }}</td>
                <td></td>
                <td></td>
                <td>{{ $totals['net_weight'] }}</td>
                <td>{{ $totals['gross_weight'] }}</td>
                <td></td>
            </tr>
        </tbody>
    </table>
    @endif

    {{-- Summary Section --}}
    <div class="summary-section">
        <table class="summary-table">
            <thead>
                <tr>
                    <th colspan="{{ 2 + $all_sizes->count() }}">Summary</th>
                </tr>
                <tr>
                    <th>SIZE</th>
                    @foreach($all_sizes as $size)
                    <th>{{ $size }}</th>
                    @endforeach
                    <th>TOTAL</th>
                </tr>
            </thead>
            <tbody>
                @php
                $orderTotal = 0;
                $cumulativePackTotal = 0;
                $balTotal = 0;
                @endphp

                {{-- ORDER QTY Row (Total from all packing lists for this PO) --}}
                <tr>
                    <td>ORDER. QTY</td>
                    @foreach($all_sizes as $size)
                    @php $q = $orderQuantitiesFromAllPacks->get($size, 0); $orderTotal += $q; @endphp
                    <td>{{ $q }}</td>
                    @endforeach
                    <td><strong>{{ $orderTotal }}</strong></td>
                </tr>

                {{-- DISPATCH QTY Rows (1st, 2nd, etc.) --}}
                @foreach($dispatchQuantities as $dispatchNumber => $dispatchQty)
                @php
                $dispatchTotal = 0;
                $isCurrentPackingList = $dispatchNumber == $currentDispatchNumber;
                if ($isCurrentPackingList) {
                $rowLabel = 'PACKING LIST QTY';
                } else {
                $ordinalNumber = $dispatchNumber == 1 ? '1st' : ($dispatchNumber == 2 ? '2nd' : ($dispatchNumber == 3 ? '3rd' : $dispatchNumber.'th'));
                $rowLabel = $ordinalNumber . ' DISPATCH QTY';
                }
                @endphp
                <tr>
                    <td>{{ $rowLabel }}</td>
                    @foreach($all_sizes as $size)
                    @php
                    $q = $dispatchQty->get($size, 0);
                    $dispatchTotal += $q;
                    @endphp
                    <td>{{ $q }}</td>
                    @endforeach
                    <td><strong>{{ $dispatchTotal }}</strong></td>
                </tr>
                @endforeach

                {{-- BALANCE Row --}}
                <tr>
                    <td>BALANCE</td>
                    @foreach($all_sizes as $size)
                    @php
                    $totalDispatched = 0;
                    foreach($dispatchQuantities as $dispatchQty) {
                    $totalDispatched += $dispatchQty->get($size, 0);
                    }
                    $orderQty = $orderQuantitiesFromAllPacks->get($size, 0);
                    $b = $orderQty - $totalDispatched;
                    $balTotal += $b;
                    @endphp
                    <td>{{ $b }}</td>
                    @endforeach
                    <td><strong>{{ $balTotal }}</strong></td>
                </tr>

                {{-- PACK QTY % Row --}}
                <tr>
                    <td>PACK QTY %</td>
                    @foreach($all_sizes as $size)
                    @php
                    $totalDispatched = 0;
                    foreach($dispatchQuantities as $dispatchQty) {
                    $totalDispatched += $dispatchQty->get($size, 0);
                    }
                    $orderQty = $orderQuantitiesFromAllPacks->get($size, 0);
                    $pct = $orderQty > 0 ? ($totalDispatched / $orderQty) * 100 : 0;
                    @endphp
                    <td>{{ $pct > 0 ? round($pct) . '%' : '-' }}</td>
                    @endforeach
                    <td>
                        <strong>
                            @php
                            $totalDispatched = 0;
                            foreach($dispatchQuantities as $dispatchQty) {
                            foreach($all_sizes as $size) {
                            $totalDispatched += $dispatchQty->get($size, 0);
                            }
                            }
                            @endphp
                            {{ $orderTotal > 0 ? round(($totalDispatched / $orderTotal) * 100) . '%' : '-' }}
                        </strong>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</body>

</html>