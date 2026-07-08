<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>D-Mart Packing List</title>
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

        .title-row td {
            text-align: center;
            font-weight: bold;
            font-size: 13px;
            padding: 5px;
        }

        .header-table {
            border: 1px solid #000;
        }

        .header-table td {
            text-align: left;
            vertical-align: top;
            border: none;
        }

        .header-table .label {
            font-weight: bold;
            white-space: nowrap;
        }

        /* Ship From / Ship To label cells - shrink-to-fit (width:1% +
           white-space:nowrap forces the column to hug the text instead of
           taking a fixed percentage of the table, which was leaving a gap
           before the address text) */
        .header-table .ship-label {
            padding: 4px;
            width: 1%;
            white-space: nowrap;
        }

        .header-table .ship-to-col {
            border-right: 1px solid #000;
            border-left: 1px solid #000;
        }

        .header-table .dim-row td {
            border: none;
        }

        .header-table .dim-row td:last-child {
            border-top: 1px solid #000;
            border-left: 1px solid #000;
        }

        /* Summary table: size to its content instead of stretching to the
           full width of the page like the main table does */
        .summary-table {
            width: 60%;
            margin-left: auto;
            margin-right: auto;
        }

        .summary-table th,
        .summary-table td {
            font-size: 8px;
            padding: 2px;
        }

        .summary-section {
            margin-top: 15px;
        }

        .totals-row {
            font-weight: bold;
            background-color: #f5f5f5;
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

    <table style="margin-bottom:0;">
        <tr class="title-row">
            <td>PACKING LIST</td>
        </tr>
    </table>

    <!-- Header: PO details / Ship From / Ship To -->
    <table class="header-table" style="margin-bottom:0;">
        <tr>
            <td style="width:34%;"><span class="label">PO NO :</span> {{ $po->po_num }}</td>
            <td class="ship-label" rowspan="6"><strong>Ship From,</strong></td>
            <td style="width:32%;" rowspan="6">
                {{ $shipFromName }}<br>
                {{ $shipFromAddress }}<br>
                {{ $shipFromPincode }}<br>
                {{ $shipFromCountry }}<br>
            </td>
            <td class="ship-label" rowspan="5"><strong>Ship To,</strong></td>
            <td class="ship-to-col" style="width:32%;" rowspan="5">
                {{ $shipToName }}<br>
                {{ $shipToAddress }}<br>
                INDIA<br>
                @if($shipToGstin)
                GSTIN: {{ $shipToGstin }}
                @endif
            </td>
        </tr>
        <tr>
            <td><span class="label">ART .NO.</span> {{ $eanNo }}</td>
        </tr>
        <tr>
            <td><span class="label">ART. Description :</span> {{ $articleDescription }}</td>
        </tr>
        <tr>
            <td><span class="label">MRP</span> {{ $mrp }}</td>
        </tr>
        <tr>
            <td><span class="label">Ctn Dimention</span> {{ $ctnDimDisplay }}</td>
        </tr>
        <tr class="dim-row">
            <td></td>
            <td></td>
            <td><span class="label">B.Dimention</span> {{ $ctnDimDisplay }}</td>
        </tr>
    </table>

    <!-- Main Table: Colour x Size grid with Ratio / Case Lot / Cartons / Total Pcs -->
    <table style="margin-top:0;">
        <thead>
            <tr>
                <th>Colour</th>
                @foreach($sizes as $size)
                <th>{{ $size }}</th>
                @endforeach
                <th>Ratio</th>
                <th>Pcs per Carton</th>
                <th>No.Of CTNS</th>
                <th>Total Pcs</th>
            </tr>
        </thead>
        <tbody>
            @php $colorCount = count($colorSizeMatrix); @endphp
            @foreach($colorSizeMatrix as $color => $sizeQtys)
            <tr>
                <td>{{ $color }}</td>
                @foreach($sizes as $size)
                <td>{{ $sizeQtys[$size] ?? '' }}</td>
                @endforeach
                <td>{{ $ratio }}</td>
                @if($loop->first)
                <td rowspan="{{ $colorCount }}">{{ $caseLot }}</td>
                <td rowspan="{{ $colorCount }}">{{ $totalCartons }}</td>
                @endif
                <td>{{ $totalPcsPerColor }}</td>
            </tr>
            @endforeach

            <tr class="totals-row">
                <td></td>
                @foreach($sizes as $size)
                <td>{{ $sizeFooterTotals[$size] ?? 0 }}</td>
                @endforeach
                <td>{{ $ratioFooterTotal }}</td>
                <td></td>
                <td>{{ $totalCartons }}</td>
                <td>{{ $totalPcsFooterTotal }}</td>
            </tr>
        </tbody>
    </table>

    <!-- Summary Section -->
    <div class="summary-section">
        <table class="summary-table" style="margin-top:0;">
            <tbody>
                <tr class="title-row">
                    <td colspan="{{ count($sizes) + 3 }}">SUMMARY</td>
                </tr>
                <tr>
                    <td><strong>{{ $po->po_num }}</strong></td>
                    <th>SIZE</th>
                    @foreach($sizes as $size)
                    <th>{{ $size }}</th>
                    @endforeach
                    <th>TOTAL</th>
                </tr>
                <tr>
                    <td>{{ $eanNo }}</td>
                    <td>ORDER QTY</td>
                    @foreach($sizes as $size)
                    <td>{{ $orderQtyBySize[$size] ?? 0 }}</td>
                    @endforeach
                    <td><strong>{{ $orderQtyTotal }}</strong></td>
                </tr>
                <tr>
                    <td rowspan="3">{{ $articleDescription }}</td>
                    <td>PACK QTY</td>
                    @foreach($sizes as $size)
                    <td>{{ $packQtyBySize[$size] ?? 0 }}</td>
                    @endforeach
                    <td><strong>{{ $packQtyTotal }}</strong></td>
                </tr>
                <tr>
                    <td>BALANCE</td>
                    @foreach($sizes as $size)
                    <td>{{ $balanceBySize[$size] ?? 0 }}</td>
                    @endforeach
                    <td><strong>{{ $balanceTotal }}</strong></td>
                </tr>
                <tr>
                    <td>PACK QTY %</td>
                    @foreach($sizes as $size)
                    <td>{{ ($percentBySize[$size] ?? 0) > 0 ? round($percentBySize[$size], 2) . '%' : '-' }}</td>
                    @endforeach
                    <td><strong>{{ $percentTotal > 0 ? round($percentTotal, 2) . '%' : '-' }}</strong></td>
                </tr>
            </tbody>
        </table>
    </div>

</body>

</html>