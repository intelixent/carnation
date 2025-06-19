<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <title>Tax Invoice</title>
    <style>
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 10px;
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
    </style>
</head>

<body>

    <h2 class="text-center">TAX INVOICE</h2>
    <table>
        <tr>
            <td></td>
            <td width="75%" valign="middle">
                <p align="center">
                    <strong>Carnation Creations Private Limited</strong><br>
                    376/1, Narasimhanaicken Palayam Village, Coimbatore - 641031<br>
                    <strong>Supplier GSTIN:</strong> 33AAHCC1371N1ZL |
                    <strong>PAN:</strong> AAHCC1371N |
                    <strong>State Code:</strong> 33
                </p>
            </td>
            <td></td>

    </table>

    <table>

        <tr>
            <td><strong>IRN No:</strong></td>
            <td></td>
        </tr>
    </table>
    <table>
        <tr>
            <td>
                <strong>Acknowledgment No:</strong> <br />
                <strong>Document No:</strong> <br />
                <strong>Supply Type Code:</strong> <br />
                <strong>E-WAY BILL NO.:</strong> <br />
                <strong>E-WAY BILL DATE:</strong> <br />
            </td>
            <td>
                <strong>Acknowledgment Date:</strong> <br />
                <strong>Document Date:</strong> <br />
                <strong>Reverse Charge:</strong> <br />
                <strong>Preceeding Document No.:</strong> <br />
                <strong>Preceeding Document Date:</strong> <br />
            </td>
        </tr>
    </table>

    @php
        $billAddr = $po_details->vendor_com_adr;
        $shipAddr = $po_details->vendor_del_adr;
    @endphp
    <table>
        <tr>
            <td width="50%">Details of Receiver (Billed to)</td>
            <td width="50%">Details of Consignee (shipped to)</td>
        </tr>
        <tr>
            <td>
                <table class="inner_table">
                    <tr>
                        <td width="30%"><strong>Legal Name</strong></td>
                        <td width="70%">:BEST UNITED INDIA COMFORTS PVT LTD</td>
                    </tr>
                    <tr>
                        <td><strong>Address 1</strong></td>
                        <td>:{!! nl2br(e($billAddr)) !!}</td>
                    </tr>
                    <tr>
                        <td><strong>Address 2</strong></td>
                        <td>:</td>
                    </tr>
                    <tr>
                        <td><strong>City/Town/Village</strong></td>
                        <td>:</td>
                    </tr>
                    <tr>
                        <td><strong>State Name/Code</strong></td>
                        <td>:{{ $state->name }} ({{ $state->code }})</td>
                    </tr>
                    <tr>
                        <td><strong>GST No</strong></td>
                        <td>:{{ $po_details->vendor_gst  }}</td>
                    </tr>
                    <tr>
                        <td><strong>PAN No</strong></td>
                        <td>:{{ $vendor->pan_no }}</td>
                    </tr>
                    <tr>
                        <td><strong>PIN Code</strong></td>
                        <td>:</td>
                    </tr>
                    <tr>
                        <td><strong>GST Type</strong></td>
                        <td>:Local GST</td>
                    </tr>
                    <tr>
                        <td><strong>PO Number</strong></td>
                        <td>:{{ $po_details->po_num }}</td>
                    </tr>
                    <tr>
                        <td><strong>PO Date</strong></td>
                        <td>:{{ $po_details->po_date }}</td>
                    </tr>
                </table>
            </td>
            <td>
                <table class="inner_table">
                    <tr>
                        <td width="30%"><strong>Legal Name</strong></td>
                        <td width="70%">:BEST UNITED INDIA COMFORTS PVT LTD</td>
                    </tr>
                    <tr>
                        <td><strong>Address 1</strong></td>
                        <td>:{!! nl2br(e($shipAddr)) !!}</td>
                    </tr>
                    <tr>
                        <td><strong>Address 2</strong></td>
                        <td>:</td>
                    </tr>
                    <tr>
                        <td><strong>City/Town/Village</strong></td>
                        <td>:</td>
                    </tr>
                    <tr>
                        <td><strong>State Name/Code</strong></td>
                        <td>:{{ $state->name }} ({{ $state->code }})</td>
                    </tr>
                    <tr>
                        <td><strong>GST No</strong></td>
                        <td>:{{ $po_details->vendor_gst  }}</td>
                    </tr>
                    <tr>
                        <td><strong>PAN No</strong></td>
                        <td>:{{ $vendor->pan_no }}</td>
                    </tr>
                    <tr>
                        <td><strong>PIN Code</strong></td>
                        <td>:</td>
                    </tr>
                    <tr>
                        <td><strong>Place of Supply</strong></td>
                        <td>:{{ $state->name }}</td>
                    </tr>
                    <tr>
                        <td></td>
                        <td></td>
                    </tr>
                    <tr>
                        <td></td>
                        <td></td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    @php
    $totalCartons = 0;
    $totalQty = 0;
    $totalAmount = 0;
    $totalDiscount = 0;
    $totalTaxable = 0;
    foreach ($invoice_item_details as $item) {
    $totalCartons += $item['total_cartons'];
    $totalQty += $item['qty'];
    $totalAmount += $item['amount'];
    $totalDiscount += $item['discount'];
    $totalTaxable += $item['taxable_value'];
    }
    $assessableValue = $totalTaxable;
    $igstAmount = round($assessableValue * 0.05, 2);
    $cess = 0.00;
    $gross = $assessableValue + $igstAmount + $cess;
    // Round off to nearest rupee
    $roundOff = round($gross) - $gross;
    $totalInvoice = round($gross);
    @endphp
    <table>
        <thead>
            <tr>
                <th>SI No</th>
                <th>Description</th>
                <th>HSN</th>
                <th>Style No</th>
                <th>Color</th>
                <th>Total<br>Cartons</th>
                <th>UOM</th>
                <th>Qty</th>
                <th>Rate</th>
                <th>Line Total</th>
                <th>Discount</th>
                <th>Taxable</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($invoice_item_details as $i => $invoice_item)
            <tr>
                <td>{{ $i + 1 }}</td>
                <td>{{ $invoice_item['description'] }}, {{ $invoice_item['size'] }}</td>
                <td>{{ $invoice_item['hsn_code'] }}</td>
                <td class="text-right">{{ $invoice_item['style'] }}</td>
                <td class="text-right">{{ $invoice_item['color'] }}</td>
                <td class="text-right">{{ $invoice_item['total_cartons'] }}</td>
                <td class="text-right">{{ $invoice_item['unit'] }}</td>
                <td class="text-right">{{ $invoice_item['qty'] }}</td>
                <td class="text-right">{{ number_format($invoice_item['rate'], 2) }}</td>
                <td class="text-right">{{ number_format($invoice_item['amount'], 2) }}</td>
                <td class="text-center">{{ number_format($invoice_item['discount'], 2) }}</td>
                <td class="text-center">{{ number_format($invoice_item['taxable_value'], 2) }}</td>
            </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr>
                <td colspan="5" align="right"><strong>Total</strong></td>
                <td class="text-right"><strong>{{ $totalCartons }}</strong></td>
                <td></td>
                <td class="text-right"><strong>{{ $totalQty }}</strong></td>
                <td></td>
                <td class="text-right"><strong>{{ number_format($totalAmount, 2) }}</strong></td>
                <td class="text-right"><strong>{{ number_format($totalDiscount, 2) }}</strong></td>
                <td class="text-right"><strong>{{ number_format($totalTaxable, 2) }}</strong></td>
            </tr>
        </tfoot>
    </table>

    <table>
        <tr>
            <td>
                <table class="inner_table">
                    <tr>
                        <td>Transporter Name</td>
                        <td></td>
                    </tr>
                    <tr>
                        <td>Mode of Transportation</td>
                        <td></td>
                    </tr>
                    <tr>
                        <td>GR No</td>
                        <td></td>
                    </tr>
                    <tr>
                        <td>Vehicle No</td>
                        <td></td>
                    </tr>
                    <tr>
                        <td>Distance (KM)</td>
                        <td></td>
                    </tr>
                </table>
            </td>
            <td>
                <table class="inner_table">
                    <tr>
                        <td><strong>Assessable Value</strong></td>
                        <td class="text-right">₹{{ number_format($assessableValue, 2) }}</td>
                    </tr>
                    <tr>
                        <td><strong>IGST 5%</strong></td>
                        <td class="text-right">₹{{ number_format($igstAmount, 2) }}</td>
                    </tr>
                    <tr>
                        <td><strong>CESS</strong></td>
                        <td class="text-right">₹{{ number_format($cess, 2) }}</td>
                    </tr>
                    <tr>
                        <td><strong>Round Off</strong></td>
                        <td class="text-right">₹{{ number_format($roundOff, 2) }}</td>
                    </tr>
                    <tr>
                        <td><strong>Total Invoice</strong></td>
                        <td class="text-right">₹{{ number_format($totalInvoice, 2) }}</td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    @php
    // Instantiate the formatter once
    $formatter = new \NumberFormatter('en_IN', \NumberFormatter::SPELLOUT);

    // Format and capitalize
    $totalInWords = ucfirst($formatter->format($totalInvoice));
    @endphp

    <p><strong>Total in Words:</strong> {{ $totalInWords }} Only</p>
    <br><br>
    <p class="text-right">Authorized Signatory</p>

</body>

</html>