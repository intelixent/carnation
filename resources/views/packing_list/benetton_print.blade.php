<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Benetton Packing List</title>
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
    <table style="width:100%; border-collapse: collapse; border: 1px solid black; font-weight: bold;">
        <!-- Header -->
        <tr>
            <td colspan="6" style="text-align: center; font-size: 8px; border-bottom: 1px solid black; width: 90%;">
                PACKING LIST
            </td>
            <td style="text-align: left; font-size: 8px; border-bottom: 1px solid black; width: 10%;">
                DATE: {{ $poDate }}
            </td>
        </tr>
        <tr>
            <td colspan="7" style="text-align: left; font-size: 8px; border-bottom: 1px solid black; width: 100%;">
                SUPPLIER NAME & ADDRESS
            </td>
        </tr>
        <tr>
            <td colspan="7" style="text-align: left; font-size: 8px; border-bottom: 1px solid black; width: 100%;">
                CARNATION CREATION PRIVATE LIMITED
            </td>
        </tr>
        <tr>
            <td colspan="7" style="text-align: left; font-size: 8px; border-bottom: 1px solid black; width: 100%;">
                376/1 , NARASIMHANAICKEN PALAYAMN VILLAGE,
            </td>
        </tr>
        <tr>
            <td colspan="7" style="text-align: left; font-size: 8px; border-bottom: 1px solid black; width: 100%;">
                METTUPALAYAM MAIN ROAD
            </td>
        </tr>
        <tr>
            <td colspan="7" style="text-align: left; font-size: 8px; border-bottom: 1px solid black; width:100%;">
                COIMBATORE.
            </td>
        </tr>
        <tr>
            <td colspan="6" style="text-align: left; font-size: 8px; border-bottom: 1px solid black; width: 90%;">
                PINCODE-641031
            </td>
            <td style="text-align: left; font-size: 8px; border-bottom: 1px solid black; width: 10%;">
                JOB # {{ $poJobNum }}
            </td>
        </tr>
        <tr>
            <td style="text-align: center; font-size: 8px; border-bottom: 1px solid black; width: 15%;">
                INVOICE NO
            </td>
            <td rowspan="2" style="text-align: center; font-size: 8px; border-bottom: 1px solid black; width: 10%;">
                {{ $articleNumbersDisplay }}
            </td>
            <td rowspan="2" colspan="5" style="text-align: left; font-size: 8px; border-bottom: 1px solid black; width: 75%;">
            </td>
        </tr>
        <tr>
            <td style="text-align: center; font-size: 8px; border-bottom: 1px solid black; width: 15%;">
                STYLE
            </td>
        </tr>
        <tr>
            <td style="text-align: center; font-size: 8px; border-bottom: 1px solid black; width: 15%;">
                DESCRIPTION
            </td>
            <td style="text-align: center; font-size: 8px; border-bottom: 1px solid black; width: 10%;">
                {{ $styleDescriptionsDisplay }}
            </td>
            <td colspan="3" style="text-align: right; font-size: 8px; border-bottom: 1px solid black; width: 60%;">
                TOTAL NO OF CARTONS
            </td>
            <td colspan="2" style="text-align: left; font-size: 8px; border-bottom: 1px solid black; width: 15%;">
                {{ $totalCtn }}
            </td>
        </tr>
        <tr>
            <td style="text-align: center; font-size: 8px; border-bottom: 1px solid black; width: 15%;">
                PO NUMBER
            </td>
            <td style="text-align: center; font-size: 8px; border-bottom: 1px solid black; width: 10%;">
                {{ $poNum }}
            </td>
            <td colspan="3" style="text-align: right; font-size: 8px; border-bottom: 1px solid black; width: 60%;">
                TOTAL NET WEIGHT
            </td>
            <td colspan="2" style="text-align: left; font-size: 8px; border-bottom: 1px solid black; width: 15%;">
                {{ $totalNetWeight }}
            </td>
        </tr>
        <tr>
            <td style="text-align: center; font-size: 8px; border-bottom: 1px solid black; width: 15%;">
                COLOUR CODE
            </td>
            <td style="text-align: center; font-size: 8px; border-bottom: 1px solid black; width: 10%;">
                {{ $uniqueColorDisplay }}
            </td>
            <td colspan="3" style="text-align: right; font-size: 8px; border-bottom: 1px solid black; width: 60%;">
                TOTAL GROSS WEIGHT
            </td>
            <td colspan="2" style="text-align: left; font-size: 8px; border-bottom: 1px solid black; width: 15%;">
                {{ $totalGrossWeight }}
            </td>
        </tr>
        <tr>
            <td style="text-align: center; font-size: 8px; border-bottom: 1px solid black; width: 15%;">
                COLOUR
            </td>
            <td style="text-align: center; font-size: 8px; border-bottom: 1px solid black; width: 10%;">
                {{ $uniqueColorDisplay }}
            </td>
            <td colspan="3" style="text-align: right; font-size: 8px; border-bottom: 1px solid black; width: 60%;">
            </td>
            <td colspan="2" style="text-align: left; font-size: 8px; border-bottom: 1px solid black; width: 15%;">
            </td>
        </tr>
    </table>

    <!-- Main Packing Table -->
    <table style="width:100%; border-collapse: collapse; border: 1px solid black; font-weight: bold;">
        <!-- Header Row -->
        <tr>
            <td colspan="2" style="text-align: center; font-size: 8px; border: 1px solid black; background-color: #f0f0f0;">CTN NO</td>
            <td style="text-align: center; font-size: 8px; border: 1px solid black; background-color: #f0f0f0;">TTL CTNS</td>
            <td style="text-align: center; font-size: 8px; border: 1px solid black; background-color: #f0f0f0;">COLOUR CODE</td>

            <!-- Dynamic Size Headers -->
            @foreach($tableData['sizeOrder'] as $size)
            <td style="text-align: center; font-size: 8px; border: 1px solid black; background-color: #f0f0f0;">{{ $size }}</td>
            @endforeach

            <td style="text-align: center; font-size: 8px; border: 1px solid black; background-color: #f0f0f0;">PER.CTN</td>
            <td style="text-align: center; font-size: 8px; border: 1px solid black; background-color: #f0f0f0;">GRAND TTL</td>
            <td style="text-align: center; font-size: 8px; border: 1px solid black; background-color: #f0f0f0;">NET-W.T</td>
            <td style="text-align: center; font-size: 8px; border: 1px solid black; background-color: #f0f0f0;">EMPTY BOX-W.T</td>
            <td style="text-align: center; font-size: 8px; border: 1px solid black; background-color: #f0f0f0;">GROSS-W.T</td>
        </tr>

        <!-- Data Rows -->
        @foreach($tableData['rows'] as $row)
        <tr>
            <!-- CTN NO -->
            <td style="text-align: center; font-size: 8px; border: 1px solid black;">{{ $row['ctn_first'] }}</td>

            <!-- Empty column for CTN NO colspan -->
            <td style="text-align: center; font-size: 8px; border: 1px solid black;">{{ $row['ctn_last'] }}</td>

            <!-- TTL CTNS -->
            <td style="text-align: center; font-size: 8px; border: 1px solid black;">{{ $row['ttl_ctn'] }}</td>

            <!-- COLOUR CODE -->
            <td style="text-align: center; font-size: 8px; border: 1px solid black;">{{ $row['color_code'] }}</td>

            <!-- Dynamic Size Columns -->
            @foreach($tableData['sizeOrder'] as $size)
            <td style="text-align: center; font-size: 8px; border: 1px solid black;">
                {{ $row['per_size'][$size] > 0 ? $row['per_size'][$size] : '' }}
            </td>
            @endforeach

            <!-- PER.CTN -->
            <td style="text-align: center; font-size: 8px; border: 1px solid black;">{{ $row['per_ctn'] }}</td>

            <!-- GRAND TTL -->
            <td style="text-align: center; font-size: 8px; border: 1px solid black;">{{ $row['grand_total'] }}</td>

            <!-- NET-W.T -->
            <td style="text-align: center; font-size: 8px; border: 1px solid black;">{{ $row['net_weight'] }}</td>

            <!-- EMPTY BOX-W.T -->
            <td style="text-align: center; font-size: 8px; border: 1px solid black;">{{ $row['empty_box_weight'] }}</td>

            <!-- GROSS-W.T -->
            <td style="text-align: center; font-size: 8px; border: 1px solid black;">{{ $row['gross_weight'] }}</td>
        </tr>
        @endforeach

        <!-- Total Row -->
        <tr>
            <!-- Empty CTN NO columns -->
            <td style="text-align: center; font-size: 8px; border: 1px solid black; background-color: #e0e0e0;"></td>
            <td style="text-align: center; font-size: 8px; border: 1px solid black; background-color: #e0e0e0;"></td>

            <!-- TTL CTNS Total -->
            <td style="text-align: center; font-size: 8px; border: 1px solid black; background-color: #e0e0e0;">{{ $tableData['totals']['carton_count'] }}</td>

            <!-- Empty COLOUR CODE -->
            <td style="text-align: center; font-size: 8px; border: 1px solid black; background-color: #e0e0e0;"></td>

            <!-- Dynamic Size Totals -->
            @foreach($tableData['sizeOrder'] as $size)
            <td style="text-align: center; font-size: 8px; border: 1px solid black; background-color: #e0e0e0;">
                {{ $tableData['totals']['per_size'][$size] > 0 ? $tableData['totals']['per_size'][$size] : '' }}
            </td>
            @endforeach

            <!-- Empty PER.CTN -->
            <td style="text-align: center; font-size: 8px; border: 1px solid black; background-color: #e0e0e0;"></td>

            <!-- GRAND TTL Total -->
            <td style="text-align: center; font-size: 8px; border: 1px solid black; background-color: #e0e0e0;">{{ $tableData['totals']['total_pieces'] }}</td>

            <!-- NET-W.T Total -->
            <td style="text-align: center; font-size: 8px; border: 1px solid black; background-color: #e0e0e0;">{{ $tableData['totals']['net_weight'] }}</td>

            <!-- EMPTY BOX-W.T Total -->
            <td style="text-align: center; font-size: 8px; border: 1px solid black; background-color: #e0e0e0;">{{ $tableData['totals']['empty_box_weight'] }}</td>

            <!-- GROSS-W.T Total -->
            <td style="text-align: center; font-size: 8px; border: 1px solid black; background-color: #e0e0e0;">{{ $tableData['totals']['gross_weight'] }}</td>
        </tr>
    </table>

    <!-- Summary Section -->
    <div class="summary-section">
        <!-- Size Summary Table -->
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
                <!-- ORDER QTY Row -->
                <tr>
                    <td>{{ $articleNumbersDisplay }}</td>
                    <td>Order Qty</td>
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

                <!-- Packing List Qty Row -->
                <tr>
                    <td>{{ $genderDisplay }} {{ $styleDescriptionsDisplay }}</td>
                    <td>Packing Qty</td>
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

                <!-- Balance Row: Order Qty minus Packing Qty -->
                <tr>
                    <td></td>
                    <td>Balance</td>
                    @foreach($all_sizes as $size)
                    @php
                    $orderQty = $ordered_quantities->get($size, 0);
                    $packQty = $packed_quantities->get($size, 0);
                    $balanceQty = $orderQty - $packQty;
                    @endphp
                    <td>{{ $balanceQty }}</td>
                    @endforeach
                    <td><strong>{{ $orderTotal - $packTotal }}</strong></td>
                </tr>

                <!-- Packing List % Row -->
                <tr>
                    <td>{{ $uniqueColorDisplay }}</td>
                    <td>Packing Qty %</td>
                    @foreach($all_sizes as $size)
                    @php
                    $percentage = $percentages->get($size, 0);
                    @endphp
                    <td>
                        @if($percentage > 0)
                        {{ number_format($percentage, 2) }}%
                        @else
                        -
                        @endif
                    </td>
                    @endforeach
                    <td><strong>
                            @if($orderQty > 0)
                            {{ number_format(($packQty / $orderQty) * 100, 2) }}%
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