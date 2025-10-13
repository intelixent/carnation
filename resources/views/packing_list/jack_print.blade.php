<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width,initial-scale=1.0">
    <title>Packing List</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 5px;
            padding: 5px;
            font-size: 10px;
            line-height: 1.2;
        }

        .header {
            text-align: center;
            font-size: 18px;
            font-weight: bold;
            margin-bottom: 10px;
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
            page-break-inside: avoid;
        }

        .items-table thead {
            display: table-header-group;
        }

        .items-table tfoot {
            display: table-footer-group;
        }

        .carton-group {
            page-break-inside: avoid;
            break-inside: avoid-page;
            display: table-row-group;
        }

        @media print {

            @page {
                size: A4 landscape;
                margin: 10mm 5mm 10mm 5mm !important;  
            }

            .carton-group {
                page-break-inside: avoid;
                break-inside: avoid-page;
            }

        }

        .debug-info {
            font-size: 8px;
            color: #999;
            margin-top: 10px;
        }
    </style>
</head>

<body onLoad="window.print()">

    <div class="header">PACKING LIST</div>

    <div class="header-section" style="page-break-inside: avoid; margin-bottom: 10px;">
        <table style="font-size:12px; border-collapse:collapse; width:100%;">
            <tr>
                <th style="background-color:#bbb; padding:8px; width:20%;">Invoice No.</th>
                <td style="padding:8px;" colspan="2">{{ $packing_list->po->invoice_no ?? '' }}</td>
                <th style="background-color:#bbb; padding:8px; text-align:right; width:15%;">Date :</th>
                <td style="padding:8px;" colspan="2">{{ $packing_list->po_date }}</td>
            </tr>
            <tr>
                <th style="background-color:#bbb; padding:8px; vertical-align:top;">Shipped/<br>Exported By</th>
                <td style="padding:8px;" colspan="5">
                    CARNATION CREATIONS PVT LTD<br>
                    376, Narasimha Naicken Palayam,<br>
                    Coimbatore 641031
                </td>
            </tr>
            <tr>
                <th style="background-color:#bbb; padding:8px; vertical-align:top;">Bill To Address</th>
                <td style="padding:8px;" colspan="5">{{ $packing_list->po->vendor_com_adr }}</td>
            </tr>
            <tr>
                <th style="background-color:#bbb; padding:8px; vertical-align:top;">Ship to Address</th>
                <td style="padding:8px;" colspan="5">{{ $packing_list->po->vendor_del_adr }}</td>
            </tr>
            @php $info = json_decode($packing_list->po->article_info, true); @endphp
            <tr>
                <th style="background-color:#bbb; padding:8px;">Final Destination</th>
                <td style="padding:8px;" colspan="1"></td>
                <th style="background-color:#bbb; padding:8px;" colspan="2">Color</th>
                <td style="padding:8px;" colspan="2">{{ $packing_list->color }}</td>
            </tr>
            <tr>
                <th style="background-color:#bbb; padding:8px;">Item Description</th>
                <td style="padding:8px;" colspan="1">
                    {{ ($info['Gender'] ?? '') . ' ' . ($info['Article description'] ?? '') }}
                </td>
                <th style="background-color:#bbb; padding:8px;">PO No.</th>
                <td style="padding:8px;">{{ $packing_list->po->po_num }}</td>
                <th style="background-color:#bbb; padding:8px;">Style No.</th>
                <td style="padding:8px;">{{ $info['Style No.'] ?? '' }}</td>
            </tr>
        </table>
    </div>

    @php
    // Group by dynamic_carton_name instead of carton_name
    $byCarton = $packing_list->items->groupBy(fn($i) => $i->dynamic_carton_name);
    $grandQty = 0; 
    $grandNet = 0; 
    $grandGross = 0;
    $firstPageMax = 19; 
    $otherPageMax = 30;
    $currentPageRowCount = 0; 
    $pageNumber = 1; 
    $debugInfo = [];
    @endphp

    <table class="items-table">
        <thead>
            <tr>
                <th rowspan="2">Ctn. #</th>
                <th rowspan="2">PO No.</th>
                <th rowspan="2">SAP Article No.</th>
                <th rowspan="2">Short Desc.</th>
                <th rowspan="2">EAN / SKU</th>
                <th rowspan="2">Size</th>
                <th rowspan="2">Qty</th>
                <th colspan="3">Ctn. Mea (cm)</th>
                <th rowspan="2">Net Wt</th>
                <th rowspan="2">Gross Wt</th>
                <th rowspan="2">CBM</th>
            </tr>
            <tr>
                <th>L</th>
                <th>B</th>
                <th>H</th>
            </tr>
        </thead>

        @foreach($byCarton as $dynamicCartonName => $items)
        @php
        $count = $items->count();
        $maxRows = $pageNumber === 1 ? $firstPageMax : $otherPageMax;
        $remaining = $maxRows - $currentPageRowCount;
        $force = false;
        
        if ($currentPageRowCount > 0 && $count > $remaining) {
            $force = true;
            $debugInfo[] = "Carton {$dynamicCartonName} ({$count}) won't fit page {$pageNumber}, rem {$remaining} → BREAK";
        } else {
            $debugInfo[] = "Carton {$dynamicCartonName} ({$count}) fits page {$pageNumber}, rem {$remaining}";
        }
        
        if ($force) { 
            $pageNumber++; 
            $currentPageRowCount = 0; 
        }
        
        $cls = 'carton-group';
        
        // Check if this is a mixed carton
        $isMixed = $items->first()->is_mixed ?? false;
        if ($isMixed) {
            $cls .= ' mixed-carton';
        }
        
        $currentPageRowCount += $count;
        if ($currentPageRowCount >= $maxRows) { 
            $currentPageRowCount -= $maxRows; 
            $pageNumber++; 
        }
        @endphp

        <tbody class="{{ $cls }}" @if($force) style="page-break-before: always;" @endif>
            @php
            $net = $items->first()->net_weight;
            $gross = $net + 1.2;
            $grandNet += $net;
            $grandGross += $gross;
            @endphp
            @foreach($items as $i => $item)
            @php
            $cbm = $item->quantity * (
                $item->carton->length *
                $item->carton->breadth *
                $item->carton->height
            ) / 1e6;
            $grandQty += $item->quantity;
            @endphp
            <tr @if($i===0 && $force) style="page-break-before: always;" @endif>
                @if($i === 0)
                <td rowspan="{{ $count }}" @if($isMixed) @endif>
                    {{ $dynamicCartonName }}
                </td>
                @endif
                <td>{{ $packing_list->po_no }}</td>
                <td>{{ $item->article_number }}</td>
                <td>{{ $info['Article description'] ?? '' }}</td>
                <td>{{ $item->po_item->ean_code }}</td>
                <td>{{ $item->size }}</td>
                <td>{{ $item->quantity }}</td>
                <td>{{ $item->carton->length }}</td>
                <td>{{ $item->carton->breadth }}</td>
                <td>{{ $item->carton->height }}</td>
                @if($i === 0)
                <td rowspan="{{ $count }}">{{ $net }}</td>
                <td rowspan="{{ $count }}">{{ round($gross, 2) }}</td>
                @endif
                <td>{{ round($cbm, 2) }}</td>
            </tr>
            @endforeach
        </tbody>
        @endforeach

        <!-- Footer row with totals -->
        <tbody>
            <tr>
                <td colspan="6"><strong>TOTAL</strong></td>
                <td><strong>{{ $grandQty }}</strong></td>
                <td colspan="3"></td>
                <td><strong>{{ round($grandNet, 2) }}</strong></td>
                <td><strong>{{ round($grandGross, 2) }}</strong></td>
                <td></td>
            </tr>
        </tbody>
    </table>

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
                @php $orderTotal = 0; $balTotal = 0; @endphp
                <tr>
                    <td>ORDER. QTY</td>
                    @foreach($all_sizes as $size)
                    @php $q = $orderQuantitiesFromAllPacks->get($size, 0); $orderTotal += $q; @endphp
                    <td>{{ $q }}</td>
                    @endforeach
                    <td><strong>{{ $orderTotal }}</strong></td>
                </tr>
                @foreach($dispatchQuantities as $num => $dispatch)
                @php
                $label = $num == $currentDispatchNumber
                    ? 'PACKING LIST QTY'
                    : ($num == 1 ? '1st DISPATCH QTY'
                        : ($num == 2 ? '2nd DISPATCH QTY'
                            : ($num == 3 ? '3rd DISPATCH QTY' : $num.'th DISPATCH QTY')));
                $dispTotal = 0;
                @endphp
                <tr>
                    <td>{{ $label }}</td>
                    @foreach($all_sizes as $size)
                    @php $d = $dispatch->get($size, 0); $dispTotal += $d; @endphp
                    <td>{{ $d }}</td>
                    @endforeach
                    <td><strong>{{ $dispTotal }}</strong></td>
                </tr>
                @endforeach
                <tr>
                    <td>BALANCE</td>
                    @foreach($all_sizes as $size)
                    @php
                    $sum = 0; 
                    foreach($dispatchQuantities as $dq) {
                        $sum += $dq->get($size, 0);
                    }
                    $b = $orderQuantitiesFromAllPacks->get($size, 0) - $sum; 
                    $balTotal += $b;
                    @endphp
                    <td>{{ $b }}</td>
                    @endforeach
                    <td><strong>{{ $balTotal }}</strong></td>
                </tr>
                <tr>
                    <td>PACK QTY %</td>
                    @foreach($all_sizes as $size)
                    @php
                    $sum = 0; 
                    foreach($dispatchQuantities as $dq) {
                        $sum += $dq->get($size, 0);
                    }
                    $pct = $orderQuantitiesFromAllPacks->get($size, 0) > 0
                        ? round($sum / $orderQuantitiesFromAllPacks->get($size, 0) * 100, 2) . '%'
                        : '-';
                    @endphp
                    <td>{{ $pct }}</td>
                    @endforeach
                    <td><strong>
                        @php
                        $totalDisp = 0;
                        foreach($dispatchQuantities as $dq) {
                            foreach($all_sizes as $s) {
                                $totalDisp += $dq->get($s, 0);
                            }
                        }
                        @endphp
                        {{ $orderTotal > 0 ? round($totalDisp / $orderTotal * 100, 2) . '%' : '-' }}
                    </strong></td>
                </tr>
            </tbody>
        </table>
    </div>

    <!-- Debug information (optional - can be removed in production) -->
    <!--
    <div class="debug-info">
        <p><strong>Debug Information:</strong></p>
        @foreach($debugInfo as $info)
        <p>{{ $info }}</p>
        @endforeach
    </div>
    -->

</body>


</html>