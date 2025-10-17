<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <title>Tax Invoice</title>
    <style>
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 8px;
            margin: 0px;
            padding: 0px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 0px;
        }

        th,
        td {
            border: 1px solid #000;
            padding: 5px;
            vertical-align: top;
        }

        /* Style for the main invoice table - only column borders */
        .invoice-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 0px;
        }

        .invoice-table th,
        .invoice-table td {
            border-left: 1px solid #000;
            border-right: 1px solid #000;
            border-top: none;
            border-bottom: none;
            padding: 5px;
            vertical-align: top;
        }

        /* Keep top and bottom borders for header and footer */
        .invoice-table thead th {
            border-top: 1px solid #000;
            border-bottom: 1px solid #000;
        }

        .invoice-table tfoot td {
            border-top: 1px solid #000;
            border-bottom: 1px solid #000;
        }

        /* First and last column borders */
        .invoice-table th:first-child,
        .invoice-table td:first-child {
            border-left: 1px solid #000;
        }

        .invoice-table th:last-child,
        .invoice-table td:last-child {
            border-right: 1px solid #000;
        }

        .invoice-table tbody tr:last-child td {
            border-bottom: 1px solid #000;
        }

        .no-border {
            border: none;
        }

        .text-right {
            text-align: right;
        }

        .text-center {
            text-align: center;
        }

        .section-title {
            font-weight: bold;
            background: #eee;
            padding: 5px;
        }

        .inner_table table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 0px;
        }

        .inner_table th,
        .inner_table td {
            border: 0px solid #000;
            padding: 1px;
            vertical-align: top;
        }

        @media print {
            body {
                margin: 0;
            }
        }
    </style>
</head>

<body onload="window.print()">

    <table>
        <tr>
            <td width="15%" align="center" valign="middle">
                <img src="{{url('assets/web')}}/Picture1.png" alt="Company Logo" style="width: 120px; height: 60px;">
            </td>
            <td width="70%" valign="middle">
                <p align="center" style="font-size: 10px;">
                    <strong>Carnation Creations Private Limited</strong><br>
                    <strong>376/1 , NARASIMHANAICKEN PALAYAM VILLAGE</strong><br>
                    <strong>COIMBATORE , TAMILNADU , INDIA. 641031</strong><br>
                    <strong style="color: blue; text-decoration: underline;">TAX INVOICE</strong>
                </p>
            </td>
            <td width="15%" align="center" valign="middle">
                <img src="{{url('assets/web')}}/Picture2.png" alt="Company Logo" style="width: 120px; height: 60px;">
            </td>
        </tr>
    </table>

    <table style="width: 100%; border-collapse: collapse; border: 1px solid #000;">
        <tr>
            <td colspan="3" style="border-right: 1px solid #000; border-bottom: none; border-top: none; border-left: none;">
                <strong>Registered under the MSME UDYAM - Registration Number - {{ $business_settings['nsme_register_no'] }}</strong>
            </td>
            <td style="border-right: 1px solid #000; border-bottom: none; border-top: none; border-left: none;">
                <strong>Mode of Transport</strong>
            </td>
            <td style="border: none;">
                <strong> {{ $transporter_details['mode_of_transport'] ?? '' }}</strong>
            </td>
        </tr>
        <tr>
            <td style="border: none;">
                <strong>Type</strong>
            </td>
            <td style="border: none;">
                {{ $business_settings['nsme_type'] }}
            </td>
            <td style="border-right: 1px solid #000; border-bottom: none; border-top: none; border-left: none;">
                <!-- Empty cell -->
            </td>
            <td style="border-right: 1px solid #000; border-bottom: none; border-top: none; border-left: none;">
                <!-- Empty cell -->
            </td>
            <td style="border: none;">
                <!-- Empty cell -->
            </td>
        </tr>
        <tr>
            <td style="border: none;">
                <strong>Sector</strong>
            </td>
            <td style="border: none;">
                {{ $business_settings['nsme_sector'] }}
            </td>
            <td style="border-right: 1px solid #000; border-bottom: none; border-top: none; border-left: none;">
                <!-- Empty cell -->
            </td>
            <td style="border-right: 1px solid #000; border-bottom: none; border-top: none; border-left: none;">
                <strong>Transporter</strong>
            </td>
            <td style="border: none;">
                {{ $transporter_details['transport_name_display'] ?? '' }}
            </td>
        </tr>
        <tr>
            <td style="border: none;">
                <strong>Date of Registration</strong>
            </td>
            <td style="border: none;">
                {{ $business_settings['nsme_register_date'] }}
            </td>
            <td style="border-right: 1px solid #000; border-bottom: none; border-top: none; border-left: none;">
                <!-- Empty cell -->
            </td>
            <td style="border-right: 1px solid #000; border-bottom: none; border-top: none; border-left: none;">
                <strong>Date & Time of Supply</strong>
            </td>
            <td style="border: none;">
                {{ !empty($transporter_details['transport_date_time']) ? \Carbon\Carbon::createFromFormat('Y-m-d', $transporter_details['transport_date_time'])->format('d-m-Y') : '' }}
            </td>
        </tr>
        <tr>
            <td style="border: none;">
                <strong>PAN NO : {{ $business_settings['business_pan_no'] }}</strong>
            </td>
            <td style="border: none;">
                <!-- Empty cell -->
            </td>
            <td style="border-right: 1px solid #000; border-bottom: none; border-top: none; border-left: none;">
                <!-- Empty cell -->
            </td>
            <td style="border-right: 1px solid #000; border-bottom: none; border-top: none; border-left: none;">
                <strong>Place Of Supply</strong>
            </td>
            <td style="border: none;">
                {{ $ship_to_details['shipped_city'] ?? '' }}
            </td>
        </tr>
        <tr>
            <td style="border: none;">
                <strong>GST IN: {{ $business_settings['business_gst_no'] }}</strong>
            </td>
            <td style="border: none;">
                <!-- Empty cell -->
            </td>
            <td style="border-right: 1px solid #000; border-bottom: none; border-top: none; border-left: none;">
                <!-- Empty cell -->
            </td>
            <td style="border-right: 1px solid #000; border-bottom: none; border-top: none; border-left: none;">
                <strong>State Code</strong>
            </td>
            <td style="border: none;">
                {{ $ship_to_details['shipped_state_code'] ?? '' }}
            </td>
        </tr>
        <tr>
            <td style="border: none;">
                <strong>Invoice No:</strong>
            </td>
            <td style="border: none;">
                {{ $invoice['ref_no'] ?? '' }}
            </td>
            <td style="border-right: 1px solid #000; border-bottom: none; border-top: none; border-left: none;">
                <strong>Invoice Date:</strong>
                @if(!empty($invoice['inv_date']))
                @php
                try {
                // Try parsing as Y-m-d format
                $date = \Carbon\Carbon::parse($invoice['inv_date']);
                echo $date->format('d-m-Y');
                } catch (\Exception $e) {
                // If parsing fails, just display the original value
                echo $invoice['inv_date'];
                }
                @endphp
                @endif
            </td>
            <td style="border-right: 1px solid #000; border-bottom: none; border-top: none; border-left: none;">
                <!-- Empty cell -->
            </td>
            <td style="border: none;">
                <!-- Empty cell -->
            </td>
        </tr>
        <tr>
            <td style="border: none;">
                <strong>PO No:</strong>
            </td>
            <td style="border: none;">
                {{ $invoice['po_num'] ?? '' }}
            </td>
            <td style="border-right: 1px solid #000; border-bottom: none; border-top: none; border-left: none;">
                @if($vendor->id == 3 && !empty($invoice['customer_po_no']))
                <strong>Customer PO No:</strong> {{ $invoice['customer_po_no'] }}
                @elseif($vendor->id == 7 && !empty($invoice['da_no']))
                <strong>DA No:</strong> {{ $invoice['da_no'] }}
                @else
                <strong></strong>
                @endif
            </td>
            <td style="border-right: 1px solid #000; border-bottom: none; border-top: none; border-left: none;">
                <!-- Empty cell -->
            </td>
            <td style="border: none;">
                <!-- Empty cell -->
            </td>
        </tr>
        <tr>
            <td colspan="3" style="border: 1px solid #000;">
                <strong>Details of Receiver (Billed to)</strong><br />
                <strong>Name:</strong> {{ $bill_to_details['billed_legal_name'] ?? '' }}<br />
                <strong>Address:</strong> {{ $bill_to_details['billed_address_1'] ?? '' }}<br />
                {{ $bill_to_details['billed_address_2'] ?? '' }}<br />
                {{ $bill_to_details['billed_city'] ?? '' }}-{{ $bill_to_details['billed_pincode'] ?? '' }}, STATE : {{ $bill_to_details['billed_state_name'] ?? '' }}<br />
                <strong>State code:</strong> {{ $bill_to_details['billed_state_code'] ?? '' }}<br />
                <strong>GSTIN/UNIQUE ID:</strong> {{ $bill_to_details['billed_gst_no'] ?? '' }}
            </td>
            <td colspan="2" style="border: 1px solid #000;">
                <strong>Details of Consignee (Shipped to)</strong><br />
                <strong>Name:</strong> {{ $ship_to_details['shipped_legal_name'] ?? '' }}<br />
                <strong>Address:</strong> {{ $ship_to_details['shipped_address_1'] ?? '' }}<br />
                @if(!empty($ship_to_details['shipped_address_2']))
                {{ $ship_to_details['shipped_address_2'] }}<br />
                @endif
                @php
                    $isVendor2 = isset($invoice['vendor_id']) && $invoice['vendor_id'] == 2;
                    
                    // Build the city/pincode/state line
                    $cityLine = $ship_to_details['shipped_city'] ?? '';
                    if ($isVendor2) {
                        if (!empty($ship_to_details['shipped_pincode'])) {
                            $cityLine .= '-' . $ship_to_details['shipped_pincode'];
                        }
                        if (!empty($ship_to_details['shipped_state_name'])) {
                            $cityLine .= ', STATE : ' . $ship_to_details['shipped_state_name'];
                        }
                    } else {
                        $cityLine .= '-' . ($ship_to_details['shipped_pincode'] ?? '');
                        $cityLine .= ', STATE : ' . ($ship_to_details['shipped_state_name'] ?? '');
                    }
                @endphp
                {{ $cityLine }}<br />
                @php
                    $showStateCode = !$isVendor2 || !empty($ship_to_details['shipped_state_code']);
                    $showGSTIN = !$isVendor2 || !empty($ship_to_details['shipped_gst_no']);
                @endphp
                @if($showStateCode)
                <strong>State code:</strong> {{ $ship_to_details['shipped_state_code'] ?? '' }}<br />
                @endif
                @if($showGSTIN)
                <strong>GSTIN/UNIQUE ID:</strong> {{ $ship_to_details['shipped_gst_no'] ?? '' }}
                @endif
            </td>
        </tr>
    </table>

    @php
    $totalQty = 0;
    $totalAmount = 0;
    $totalDiscount = 0;
    $totalTaxable = 0;
    $totalIgstAmount = 0;
    foreach ($invoice_item_details as $item) {
    $totalQty += $item['qty'];
    $totalAmount += $item['amount'];
    $totalDiscount += $item['discount'];
    $totalTaxable += $item['taxable_value'];
    $totalIgstAmount += $item['igst_amount'];
    }
    $assessableValue = $totalTaxable;
    $cess = 0.00;
    $totalTaxAmount = $totalIgstAmount + $cess;
    $finalAmount = $assessableValue + $totalTaxAmount;
    @endphp

    <table class="invoice-table">
        <thead>
            <tr>
                @if($vendor->id == 7)
                {{-- Columns for Vendor ID 7 - 14 columns total (15 with IGST split) --}}
                <th class="text-center" style="vertical-align: middle;" rowspan="2">S.No</th>
                <th class="text-center" style="vertical-align: middle;" rowspan="2">Description of Goods</th>
                <th class="text-center" style="vertical-align: middle;" rowspan="2">HSN Code</th>
                <th class="text-center" style="vertical-align: middle;" rowspan="2">STYLE NO</th>
                <th class="text-center" style="vertical-align: middle;" rowspan="2">LOCATION CODE</th>
                <th class="text-center" style="vertical-align: middle;" rowspan="2">COLOR</th>
                <th class="text-center" style="vertical-align: middle;" rowspan="2">Total<br>Cartons</th>
                <th class="text-center" style="vertical-align: middle;" rowspan="2">Unit</th>
                <th class="text-center" style="vertical-align: middle;" rowspan="2">Qty</th>
                <th class="text-center" style="vertical-align: middle;" rowspan="2">Rate</th>
                <th class="text-center" style="vertical-align: middle;" rowspan="2">Total</th>
                <th class="text-center" style="vertical-align: middle;" rowspan="2">Discount</th>
                <th class="text-center" style="vertical-align: middle;" rowspan="2">Taxable Value</th>
                <th class="text-center" style="vertical-align: middle;" colspan="2">IGST</th>
                @else
                {{-- Original columns for all other vendors - 12 columns total (14 with IGST split) --}}
                <th class="text-center" style="vertical-align: middle;" rowspan="2">SI No</th>
                <th class="text-center" style="vertical-align: middle;" rowspan="2">Description</th>
                <th class="text-center" style="vertical-align: middle;" rowspan="2">HSN</th>
                <th class="text-center" style="vertical-align: middle;" rowspan="2">Style No</th>
                <th class="text-center" style="vertical-align: middle;" rowspan="2">Color</th>
                <th class="text-center" style="vertical-align: middle;" rowspan="2">Total<br>Cartons</th>
                <th class="text-center" style="vertical-align: middle;" rowspan="2">UOM</th>
                <th class="text-center" style="vertical-align: middle;" rowspan="2">Qty</th>
                <th class="text-center" style="vertical-align: middle;" rowspan="2">Rate</th>
                <th class="text-center" style="vertical-align: middle;" rowspan="2">Amount</th>
                <th class="text-center" style="vertical-align: middle;" rowspan="2">Discount</th>
                <th class="text-center" style="vertical-align: middle;" rowspan="2">Taxable Value</th>
                <th class="text-center" style="vertical-align: middle;" colspan="2">IGST</th>
                @endif
            </tr>
            <tr>
                <th class="text-center">Rate</th>
                <th class="text-center">Amount</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($invoice_item_details as $i => $item)
            <tr>
                @if($vendor->id == 7)
                {{-- Row structure for Vendor ID 7 --}}
                <td class="text-center">{{ $i+1 }}</td>
                <td>{{ $item['description'] }}, {{ $item['size'] }}</td>
                <td>{{ $item['hsn_code'] }}</td>
                <td class="text-center">{{ $item['style'] }}</td>
                <td class="text-center">{{ $item['location'] }}</td>
                <td class="text-center">{{ $item['colors'] }}</td>
                @if ($i === 0)
                <td class="text-center"
                    style="vertical-align: middle;"
                    rowspan="{{ count($invoice_item_details) }}">
                    <strong>{{ $totalCartonsInInvoice }}</strong>
                </td>
                @endif
                <td class="text-right">{{ $item['unit'] }}</td>
                <td class="text-right">{{ $item['qty'] }}</td>
                <td class="text-right">{{ IND_money_format($item['rate'], 2) }}</td>
                <td class="text-right">{{ IND_money_format($item['amount'], 2) }}</td>
                <td class="text-right">{{ IND_money_format($item['discount'], 2) }}</td>
                <td class="text-right">{{ IND_money_format($item['taxable_value'], 2) }}</td>
                <td class="text-center">{{ IND_money_format($item['igst_rate'], 2) }}%</td>
                <td class="text-right">{{ IND_money_format($item['igst_amount'], 2) }}</td>
                @else
                {{-- Original row structure for all other vendors --}}
                <td class="text-center">{{ $i+1 }}</td>
                <td>{{ $item['description'] }}, {{ $item['size'] }}</td>
                <td>{{ $item['hsn_code'] }}</td>
                <td class="text-center">{{ $item['style'] }}</td>
                <td class="text-center">{{ $item['colors'] }}</td>
                @if ($i === 0)
                <td class="text-center"
                    style="vertical-align: middle;"
                    rowspan="{{ count($invoice_item_details) }}">
                    <strong>{{ $totalCartonsInInvoice }}</strong>
                </td>
                @endif
                <td class="text-right">{{ $item['unit'] }}</td>
                <td class="text-right">{{ $item['qty'] }}</td>
                <td class="text-right">{{ IND_money_format($item['rate'], 2) }}</td>
                <td class="text-right">{{ IND_money_format($item['amount'], 2) }}</td>
                <td class="text-right">{{ IND_money_format($item['discount'], 2) }}</td>
                <td class="text-right">{{ IND_money_format($item['taxable_value'], 2) }}</td>
                <td class="text-center">{{ IND_money_format($item['igst_rate'], 2) }}%</td>
                <td class="text-right">{{ IND_money_format($item['igst_amount'], 2) }}</td>
                @endif
            </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr>
                @if($vendor->id == 7)
                {{-- Total row for Vendor ID 7: 6 columns before cartons --}}
                <td colspan="6" align="right"><strong>Total</strong></td>
                <td class="text-center"><strong>{{ $totalCartonsInInvoice }}</strong></td>
                <td></td>
                <td class="text-right"><strong>{{ $totalQty }}</strong></td>
                <td></td>
                <td class="text-right"><strong>{{ IND_money_format($totalAmount, 2) }}</strong></td>
                <td class="text-right"><strong>{{ IND_money_format($totalDiscount, 2) }}</strong></td>
                <td class="text-right"><strong>{{ IND_money_format($totalTaxable, 2) }}</strong></td>
                <td></td>
                <td class="text-right"><strong>{{ IND_money_format($totalTaxAmount, 2) }}</strong></td>
                @else
                {{-- Total row for other vendors: 5 columns before cartons --}}
                <td colspan="5" align="right"><strong>Total</strong></td>
                <td class="text-center"><strong>{{ $totalCartonsInInvoice }}</strong></td>
                <td></td>
                <td class="text-right"><strong>{{ $totalQty }}</strong></td>
                <td></td>
                <td class="text-right"><strong>{{ IND_money_format($totalAmount, 2) }}</strong></td>
                <td class="text-right"><strong>{{ IND_money_format($totalDiscount, 2) }}</strong></td>
                <td class="text-right"><strong>{{ IND_money_format($totalTaxable, 2) }}</strong></td>
                <td></td>
                <td class="text-right"><strong>{{ IND_money_format($totalTaxAmount, 2) }}</strong></td>
                @endif
            </tr>
            <tr>
                @if($vendor->id == 7)
                <td colspan="14" align="right"><strong>Final Amount</strong></td>
                @else
                <td colspan="13" align="right"><strong>Final Amount</strong></td>
                @endif
                <td class="text-right"><strong>{{ IND_money_format($finalAmount, 2) }}</strong></td>
            </tr>
            @php
            // Instantiate the formatter once
            $formatter = new \NumberFormatter('en_IN', \NumberFormatter::SPELLOUT);

            // Split the amount into rupees and paise
            $rupees = floor($finalAmount);
            $paise = round(($finalAmount - $rupees) * 100);

            // Format rupees in words
            $rupeesInWords = strtoupper($formatter->format($rupees));

            // Format the complete amount in words
            if ($paise > 0) {
            $paiseInWords = strtoupper($formatter->format($paise));
            $totalInWords = $rupeesInWords . ' RUPEES AND ' . $paiseInWords . ' PAISE';
            } else {
            $totalInWords = $rupeesInWords . ' RUPEES';
            }
            @endphp
            <tr>
                @if($vendor->id == 7)
                <td colspan="15" align="left"><strong>Invoice Total ( In Words) : INR {{ $totalInWords }} ONLY</strong></td>
                @else
                <td colspan="14" align="left"><strong>Invoice Total ( In Words) : INR {{ $totalInWords }} ONLY</strong></td>
                @endif
            </tr>
            <tr>
                @if($vendor->id == 7)
                <td colspan="15" align="left">
                    <strong>Certified that the Particulars given above are true and correct and the amount indicated</strong></br>
                    a) represent the price actually charged and that there is no flow additional consideration directly or indirectly from the buyer or </br>
                    b) is provisional as additional consideration will be received from the buyer on account of
                </td>
                @else
                <td colspan="14" align="left">
                    <strong>Certified that the Particulars given above are true and correct and the amount indicated</strong></br>
                    a) represent the price actually charged and that there is no flow additional consideration directly or indirectly from the buyer or </br>
                    b) is provisional as additional consideration will be received from the buyer on account of
                </td>
                @endif
            </tr>
            <tr>
                @if($vendor->id == 7)
                <td colspan="15" align="left">
                    <strong>TERMS OF SALE</strong></br>
                    1) Goods once sold will not be taken back or exchanged </br>
                    2) Jurisdiction : Coimbatore </br>
                    3) Payment Terms : {{ $vendor->payment_terms }}
                </td>
                @else
                <td colspan="14" align="left">
                    <strong>TERMS OF SALE</strong></br>
                    1) Goods once sold will not be taken back or exchanged </br>
                    2) Jurisdiction : Coimbatore </br>
                    3) Payment Terms : {{ $vendor->payment_terms }}
                </td>
                @endif
            </tr>
        </tfoot>
    </table>

</body>

</html>