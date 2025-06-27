<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
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
        }

        /* Page break control */
        .header-section {
            page-break-after: avoid;
            page-break-inside: avoid;
        }

        .items-table {
            page-break-before: auto;
            page-break-inside: auto;
        }

        .items-table thead {
            display: table-header-group;
        }

        .items-table tbody tr {
            page-break-inside: avoid;
        }

        /* Ensure summary stays together */
        .summary-section {
            page-break-before: auto;
            page-break-inside: avoid;
        }

        @media print {
            .header-section {
                page-break-after: avoid;
            }

            .items-table {
                page-break-before: auto;
            }

            .items-table thead {
                display: table-header-group;
            }
        }
    </style>
</head>

<body>
    <div class="header">PACKING LIST</div>

    <!-- Header Information Section -->
    <div class="header-section">
        <table style="width:100%; border-collapse:collapse; font-family: Arial, sans-serif; font-size: 12px;">
            <tr>
                <th style="background-color:#bbb; padding:8px; border:1px solid #000; width:20%; font-weight:bold;">Invoice No.</th>
                <td style="background-color:#fff; padding:8px; border:1px solid #000; width:40%;"></td>
                <th style="background-color:#bbb; padding:8px; border:1px solid #000; width:15%; font-weight:bold; text-align:right;">Date :</th>
                <td colspan="3" style="background-color:#fff; padding:8px; border:1px solid #000; width:25%;">{{ $packing_list->po_date }}</td>
            </tr>

            <tr>
                <th style="background-color:#bbb; padding:8px; border:1px solid #000; width:20%; vertical-align:top; font-weight:bold;">
                    Shipped/<br>Exported By
                </th>
                <td colspan="5" style="background-color:#fff; padding:8px; border:1px solid #000; width:80%;">
                    CARNATION CREATIONS PVT LTD 376,Narasimha Naicken Palayam, Coimbatore 641031
                </td>
            </tr>

            <tr>
                <th style="background-color:#bbb; padding:8px; border:1px solid #000; width:20%; vertical-align:top; font-weight:bold;">
                    Bill To Address
                </th>
                <td colspan="5" style="background-color:#fff; padding:8px; border:1px solid #000; width:80%;">
                    {{ $packing_list->po->vendor_com_adr }}
                </td>
            </tr>

            <tr>
                <th style="background-color:#bbb; padding:8px; border:1px solid #000; width:20%; vertical-align:top; font-weight:bold;">
                    Ship to Address
                </th>
                <td colspan="5" style="background-color:#fff; padding:8px; border:1px solid #000; width:80%;">
                    {{ $packing_list->po->vendor_del_adr }}
                </td>
            </tr>

            <tr>
                <th style="background-color:#bbb; padding:8px; border:1px solid #000; width:20%; font-weight:bold;">Final Destination</th>
                <td style="background-color:#fff; padding:8px; border:1px solid #000; width:30%;"></td>
                <th colspan="2" style="background-color:#bbb; padding:8px; border:1px solid #000; width:15%; font-weight:bold;">Color</th>
                <td colspan="2" style="background-color:#fff; padding:8px; border:1px solid #000; width:35%;">
                    @php
                    $articleInfo = json_decode($packing_list->po->article_info, true);
                    $article = $articleInfo['ARTICLE'] ?? '';
                    $description = $articleInfo['Article description'] ?? '';
                    $gender = $articleInfo['Gender'] ?? '';
                    $colors = $articleInfo['Colors'] ?? '';
                    $vendor = $articleInfo['Vendor'] ?? '';
                    @endphp

                    {{ $colors }}
                </td>
            </tr>

            <tr>
                <th style="background-color:#bbb; padding:8px; border:1px solid #000; width:20%; font-weight:bold;">Item Description</th>
                <td style="background-color:#fff; padding:8px; border:1px solid #000; width:25%;">
                    {{ $gender }} {{ $description }}
                </td>
                <th style="background-color:#bbb; padding:8px; border:1px solid #000; width:15%; font-weight:bold;">PO No.</th>
                <td style="background-color:#fff; padding:8px; border:1px solid #000; width:15%;">{{ $packing_list->po->po_num }}</td>
                <th style="background-color:#bbb; padding:8px; border:1px solid #000; width:15%; font-weight:bold;">Style No.</th>
                <td style="background-color:#fff; padding:8px; border:1px solid #000; width:25%;">{{ $description }}</td>
            </tr>
        </table>
    </div>

    <!-- Main Items Table -->
    <table class="items-table">
        <thead>
            <tr>
                <th style="background-color:#bbb;" rowspan="2">Ctn. #</th>
                <th style="background-color:#bbb;" rowspan="2">PO No.</th>
                <th style="background-color:#bbb;" rowspan="2">SAP Article No.</th>
                <th style="background-color:#bbb;" rowspan="2">Short Desc.</th>
                <th style="background-color:#bbb;" rowspan="2">EAN / SKU</th>
                <th style="background-color:#bbb;" rowspan="2">Size</th>
                <th style="background-color:#bbb;" rowspan="2">Shipped Units</th>
                <th style="background-color:#bbb;" colspan="3">Ctn. Mea (cm)</th>
                <th style="background-color:#bbb;" rowspan="2">Net Weight (kg)</th>
                <th style="background-color:#bbb;" rowspan="2">Gross Weight (kg)</th>
                <th style="background-color:#bbb;" rowspan="2">CBM</th>
            </tr>
            <tr>
                <th style="background-color:#bbb;">L</th>
                <th style="background-color:#bbb;">B</th>
                <th style="background-color:#bbb;">H</th>
            </tr>
        </thead>
        <tbody>
            @if($packing_list->items->isNotEmpty())
            {{-- Group items by carton name --}}
            @php
            $byCarton = $packing_list->items->groupBy(fn($item) => $item->carton_name);
            @endphp

            @foreach($byCarton as $cartonName => $items)
            @foreach($items as $i => $item)
            <tr>
                {{-- only on the first row of the group: output the carton name with rowspan --}}
                @if($i === 0)
                <td rowspan="{{ $items->count() }}">{{ $cartonName }}</td>
                @endif

                <td>{{ $packing_list->po_no }}</td>
                <td>{{ $item->article_number }}</td>
                <td> {{ $description }} </td>
                <td>{{ $item->po_item->ean_code }}</td>
                <td>{{ $item->size }}</td>
                <td>{{ $item->quantity }}</td>

                {{-- carton measures split into three cells --}}
                <td>{{ $item->carton->length }}</td>
                <td>{{ $item->carton->breadth }}</td>
                <td>{{ $item->carton->height }}</td>

                <td>{{ number_format($item->quantity * 0.2, 1) }}</td>
                <td>{{ number_format($item->quantity * 0.25, 1) }}</td>
                <td></td>
            </tr>
            @endforeach
            @endforeach

            {{-- example of a totals row after all cartons --}}
            <tr>
                <td colspan="6"></td>
                <td style="background-color:#bbb;"><strong>{{ $packing_list->items->sum('quantity') }}</strong></td>
                <td colspan="3"></td>
                <td style="background-color:#bbb;"><strong>{{ number_format($packing_list->items->sum(fn($i)=>$i->quantity*0.2),1) }}</strong></td>
                <td style="background-color:#bbb;"><strong>{{ number_format($packing_list->items->sum(fn($i)=>$i->quantity*0.25),1) }}</strong></td>
                <td style="background-color:#bbb;"><strong></strong></td>
            </tr>

            @else
            {{-- your existing "static" fallback rows here --}}
            @endif
        </tbody>
    </table>

    <!-- Summary Section -->
    <div class="summary-section">
        <!-- Size Summary Table -->
        <table class="summary-table">
            <thead>
                <tr>
                    <th colspan="{{ 3 + $all_sizes->count() }}">Summary</th>
                </tr>
                <tr>
                    <th>{{ $packing_list->po_no }}</th>
                    <th>SIZE</th>
                    @foreach($all_sizes as $size)
                    <th>{{ $size }}</th>
                    @endforeach
                    <th>TOTAL</th>
                </tr>
            </thead>
            <tbody>
                <!-- ORDER QTY Row -->
                <tr>
                    <td>{{ $article }}</td>
                    <td>ORDER. QTY</td>
                    @php $orderTotal = 0; @endphp
                    @foreach($all_sizes as $size)
                    @php
                    $orderQty = $ordered_quantities->get($size, 0);
                    $orderTotal += $orderQty;
                    @endphp
                    <td>{{ $orderQty }}</td>
                    @endforeach
                    <td><strong>{{ $orderTotal }}</strong></td>
                </tr>

                <!-- PACK QTY Row -->
                <tr>
                    <td>{{ $description }}</td>
                    <td>PACK QTY</td>
                    @php $packTotal = 0; @endphp
                    @foreach($all_sizes as $size)
                    @php
                    $packQty = $packed_quantities->get($size, 0);
                    $packTotal += $packQty;
                    @endphp
                    <td>{{ $packQty }}</td>
                    @endforeach
                    <td><strong>{{ $packTotal }}</strong></td>
                </tr>

                <!-- BALANCE Row -->
                <tr>
                    <td>{{ $vendor }}</td>
                    <td>BALANCE</td>
                    @php $balanceTotal = 0; @endphp
                    @foreach($all_sizes as $size)
                    @php
                    $balance = $balances->get($size, 0);
                    $balanceTotal += $balance;
                    @endphp
                    <td>{{ $balance }}</td>
                    @endforeach
                    <td><strong>{{ $balanceTotal }}</strong></td>
                </tr>

                <!-- PACK QTY % Row -->
                <tr>
                    <td>{{ $colors }}</td>
                    <td>PACK QTY %</td>
                    @foreach($all_sizes as $size)
                    @php
                    $percentage = $percentages->get($size, 0);
                    @endphp
                    <td>
                        @if($percentage > 0)
                        {{ round($percentage) }}%
                        @else
                        -
                        @endif
                    </td>
                    @endforeach
                    <td>
                        <strong>
                            @if($orderTotal > 0)
                            {{ round(($packTotal / $orderTotal) * 100) }}%
                            @else
                            -
                            @endif
                        </strong>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</body>

</html>