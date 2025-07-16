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

        /* allow thead once + keep it on every page */
        .items-table thead {
            display: table-header-group;
        }

        /* keep each carton’s rows (with rowspan) together */
        .carton-group {
            page-break-inside: avoid;
            break-inside: avoid-page;
            -webkit-page-break-inside: avoid;
            -webkit-column-break-inside: avoid;
            display: table-row-group;
        }

        /* keep individual lines from splitting too */
        .carton-group tr {
            page-break-inside: avoid;
            break-inside: avoid-page;
            -webkit-page-break-inside: avoid;
            -webkit-column-break-inside: avoid;
        }

        /* grand totals at the bottom */
        .items-table tfoot {
            display: table-footer-group;
        }
    </style>
</head>

<body>
    <div class="header">PACKING LIST</div>

    {{-- Header Information --}}
    <div class="header-section" style="page-break-inside: avoid; margin-bottom: 10px;">
        <table style="font-size:12px; border-collapse:collapse;">
            <tr>
                <th style="background-color:#bbb; padding:8px; width:20%;">Invoice No.</th>
                <td style="padding:8px;" colspan="2"></td>
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
                <td style="padding:8px;">{{ $info['Article description'] ?? '' }}</td>
            </tr>
        </table>
    </div>

    {{-- Main Items Table --}}
    @php
    $byCarton = $packing_list->items->groupBy(fn($i) => $i->carton_name);
    $grandQty = 0;
    $grandNet = 0;
    $grandGross = 0;
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

        @foreach($byCarton as $cartonName => $items)
        <tbody class="carton-group">
            @foreach($items as $i => $item)
            @php
            $cbm = $item->quantity
            * ($item->carton->length
            * $item->carton->breadth
            * $item->carton->height)
            / 1_000_000;
            $grandQty += $item->quantity;
            $grandNet += $item->net_weight;
            $grandGross += ($item->net_weight + 1.2);
            @endphp
            <tr>
                @if($i === 0)
                <td rowspan="{{ $items->count() }}">{{ $cartonName }}</td>
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
                <td>{{ $item->net_weight }}</td>
                <td>{{ round($item->net_weight + 1.2, 2) }}</td>
                <td>{{ round($cbm, 2) }}</td>
            </tr>
            @endforeach
        </tbody>
        @endforeach

        <tfoot>
            <tr>
                <td colspan="6"></td>
                <td><strong>{{ $grandQty }}</strong></td>
                <td colspan="3"></td>
                <td><strong>{{ round($grandNet, 2) }}</strong></td>
                <td><strong>{{ round($grandGross, 2) }}</strong></td>
                <td></td>
            </tr>
        </tfoot>
    </table>

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