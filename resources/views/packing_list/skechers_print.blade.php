<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Skechers Packing List</title>
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
    <table style="width:100%; border-collapse: collapse; border: 1px solid black; font-weight: bold;">
        <!-- Header -->
        <tr>
            <td colspan="6" style="text-align: center; font-size: 18px; border-bottom: 1px solid black;">
                PACKING LIST
            </td>
        </tr>
        <!-- Row 1 -->
        <tr>
            <td style="width: 10%; text-align: left; border: none;">UNIT</td>
            <td style="width: 20%; text-align: left; border: none;"></td>
            <td style="width: 10%; border: none;"></td>
            <td style="width: 25%; border: none;"></td>
            <td style="width: 10%; border: none;"></td>
            <!-- Last column: only left border -->
            <td style="width: 25%; border: none; border-left: 1px solid black;"></td>
        </tr>

        @php
        $allAddressLines = [];

        if(isset($packing_list->po->vendor_del_adr)) {
        $vendorAddress = $packing_list->po->vendor_del_adr;
        if(is_string($vendorAddress)) {
        $vendorAddress = json_decode($vendorAddress, true);
        }
        if(is_array($vendorAddress)) {
        foreach($vendorAddress as $line) {
        if(is_string($line)) {
        $parts = explode('",', $line);
        foreach($parts as $part) {
        $trimmed = trim($part);
        if(!empty($trimmed)) {
        $allAddressLines[] = $trimmed;
        }
        }
        }
        }
        }
        }
        @endphp

        <!-- Row 2 -->
        <tr>
            <td style="border: none; text-align: left;">PO NO</td>
            <td style="border: none; text-align: left;">{{ $poNum }}</td>
            <td style="border: none; text-align: right;">Ship From</td>
            <td style="border: none; text-align: left;">Ms.CARNATION CREATIONS PVT LTD.</td>
            <td style="border: none; text-align: right;">Ship To,</td>
            <!-- Last column: only left border -->
            <td style="width: 25%; border: none; border-left: 1px solid black;">
                {{ $allAddressLines[0] ?? 'No Address Line 1' }}
            </td>
        </tr>

        <!-- Row 3 -->
        <tr>
            <td style="border: none; text-align: left;">Style .NO.</td>
            <td style="border: none; text-align: left;">{{ $articleNumbersDisplay }}</td>
            <td style="border: none;"></td>
            <td style="border: none; text-align: left;">376/1, NARASIMHA NAICKEN PALAYAM,</td>
            <td style="border: none;"></td>
            <!-- Last column: only left border -->
            <td style="border: none; border-left: 1px solid black;">
                {{ $allAddressLines[1] ?? '' }}
            </td>
        </tr>

        <!-- Row 4 -->
        <tr>
            <td style="border: none; text-align: left;">Style. Description</td>
            <td style="border: none; text-align: left;">{{ $genderDisplay }} {{ $styleDescriptionsDisplay }}</td>
            <td style="border: none;"></td>
            <td style="border: none; text-align: left;">COIMBATORE-641031</td>
            <td style="border: none;"></td>
            <!-- Last column: only left border -->
            <td style="border: none; border-left: 1px solid black;">
                {{ $allAddressLines[2] ?? '' }}
            </td>
        </tr>

        <!-- Row 5 -->
        <tr>
            <td style="border: none; text-align: left;">Customs Code</td>
            <td style="border: none; text-align: left;">MEE</td>
            <td style="border: none;"></td>
            <td style="border: none; text-align: left;">INDIA</td>
            <td style="border: none;"></td>
            <!-- Last column: only left border -->
            <td style="border: none; border-left: 1px solid black;">
                {{ $allAddressLines[3] ?? '' }}
            </td>
        </tr>

        <!-- Row 6 -->
        <tr>
            <td style="border: none;"></td>
            <td style="border: none;"></td>
            <td style="border: none;"></td>
            <td style="border: none; text-align: left;"></td>
            <td style="border: none;"></td>
            <!-- Last column: only left border -->
            <td style="border: none; border-left: 1px solid black;">
                {{ $allAddressLines[4] ?? '' }}
            </td>
        </tr>

        <!-- Additional rows for remaining address lines -->
        @if(count($allAddressLines) > 5)
        @for($i = 5; $i < count($allAddressLines); $i++)
            <tr>
            <td style="border: none;"></td>
            <td style="border: none;"></td>
            <td style="border: none;"></td>
            <td style="border: none;"></td>
            <td style="border: none;"></td>
            <!-- Last column: only left border -->
            <td style="border: none; border-left: 1px solid black;">{{ $allAddressLines[$i] }}</td>
            </tr>
            @endfor
            @endif
    </table>

    @if($tableData)
    <table>
        <thead>
            {{-- Header row 1: blank over first columns, then MRP label over 2 cols, then G.WT & N.WT with rowspan --}}
            <tr class="header-row">
                {{-- Span first columns: CTN NO(2), CTN(1), Color(1), sizes(N), PCS/CTN(1), TOTAL(1) => total N+6 columns; but we span only N+4 here because PCS/CTN and TOTAL are counted later? 
                Actually in original code: colspan="{{ count($tableData['sizeOrder']) + 4 }}" spans CTN NO(2) + CTN(1) + Color(1) + sizes(N) = N+4. Then next two <th> are MRP subheaders, then G.WT & N.WT --}}
                <th colspan="{{ count($tableData['sizeOrder']) + 4 }}"></th>

                {{-- MRP grouping header --}}
                <th>MRP</th>
                <th></th>

                {{-- Weight columns with rowspan=4 --}}
                <th rowspan="4">G.WT</th>
                <th rowspan="4">N.WT</th>
            </tr>

            {{-- Header row 2: blank over first columns, then CTN Dimension label & its value --}}
            <tr class="header-row">
                <th colspan="{{ count($tableData['sizeOrder']) + 4 }}"></th>
                <th>CTN Dimension</th>
                <th>{{ $ctnDimDisplay }}</th>
            </tr>

            {{-- Header row 3: blank over first columns, then CTN W.T label & its value --}}
            <tr class="header-row">
                <th colspan="{{ count($tableData['sizeOrder']) + 4 }}"></th>
                <th>CTN W.T</th>
                <th>{{ $ctnWeight }}</th>
            </tr>

            {{-- Header row 4: actual column headers for data rows --}}
            <tr class="header-row">
                <th colspan="2">CTN NO</th>
                <th>CTN</th>
                <th>Color</th>
                @foreach($tableData['sizeOrder'] as $size)
                <th class="rotate-text">{{ $size }}</th>
                @endforeach
                <th>PCS/CTN</th>
                <th>TOTAL</th>
                {{-- Note: G.WT and N.WT headers are in the first row with rowspan=4 --}}
            </tr>
        </thead>

        <tbody>
            {{-- Data rows --}}
            @foreach($tableData['rows'] as $row)
            <tr>
                {{-- CTN NO split --}}
                <td>{{ $row['ctn_first'] }}</td>
                <td>{{ $row['ctn_last'] }}</td>

                {{-- CTN (total cartons in this group) --}}
                <td>{{ $row['ttl_ctn'] }}</td>

                {{-- Color --}}
                <td>{{ $row['color'] }}</td>

                {{-- Size columns --}}
                @foreach($tableData['sizeOrder'] as $size)
                <td>{{ ($row['per_size'][$size] ?? 0) > 0 ? $row['per_size'][$size] : '' }}</td>
                @endforeach

                {{-- PCS/CTN --}}
                <td>{{ $row['per_ctn'] }}</td>

                {{-- TOTAL pieces for this row --}}
                <td>{{ $row['total'] }}</td>

                {{-- G.WT (gross weight per carton or per group as per your logic) --}}
                <td>{{ $row['grs_wt_per'] }}</td>

                {{-- N.WT (net weight) --}}
                <td>{{ $row['net_wt_per'] }}</td>
            </tr>
            @endforeach

            {{-- Totals Row: align under same columns --}}
            <tr class="totals-row">
                {{-- Combine CTN NO columns --}}
                <td colspan="2">TOTAL</td>

                {{-- CTN total cartons --}}
                <td>{{ $tableData['totals']['carton_count'] }}</td>

                {{-- Color column blank --}}
                <td></td>

                {{-- Size-wise totals --}}
                @foreach($tableData['sizeOrder'] as $size)
                <td>{{ $tableData['totals']['per_size'][$size] > 0 ? $tableData['totals']['per_size'][$size] : '' }}</td>
                @endforeach

                {{-- PCS/CTN blank --}}
                <td></td>

                {{-- TOTAL pieces across all rows --}}
                <td>{{ $tableData['totals']['total_pieces'] }}</td>

                {{-- G.WT total - now showing the calculated total --}}
                <td>{{ number_format($tableData['totals']['total_gross_weight'] ?? 0, 2) }}</td>

                {{-- N.WT total - now showing the calculated total --}}
                <td>{{ number_format($tableData['totals']['total_net_weight'] ?? 0, 2) }}</td>

            </tr>
        </tbody>
    </table>
    @endif

    <!-- Summary Section -->
    <div class="summary-section">
        <table class="summary-table">
            <thead>
                <tr>
                    <th>{{ $poNum }}</th>
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
                    <td>{{ $articleNumbersDisplay }}</td>
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
                $leftLabel = $genderDisplay . ' ' . $styleDescriptionsDisplay;
                } else {
                $ordinalNumber = $dispatchNumber == 1 ? '1st' : ($dispatchNumber == 2 ? '2nd' : ($dispatchNumber == 3 ? '3rd' : $dispatchNumber.'th'));
                $rowLabel = $ordinalNumber . ' DISPATCH QTY';
                $leftLabel = '';
                }
                @endphp
                <tr>
                    <td>{{ $leftLabel }}</td>
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
                    <td></td>
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
                    <td>{{ $uniqueColorDisplay }}</td>
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