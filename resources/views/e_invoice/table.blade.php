<div class="mt-4 d-flex justify-content-end gap-2">
    <a id="downloadExcelData"
        href="{{ route('e_invoice_excel_download', ['from_date' => $from_date ?? request('from_date'), 'to_date' => $to_date ?? request('to_date')]) }}"
        class="btn btn-success btn-lg"
        target="_blank">
        <i class="fa fa-file-excel"></i> Download Excel
    </a>

</div>

<div class="table-responsive mt-2">
    <table class="table table-bordered text-nowrap w-100" id="DataTable">
        <thead class="bg-primary text-white">
            <tr>
                <th>S.No</th>
                <th>Invoice Date</th>
                <th>Invoice No</th>
                <th>Vendor</th>
                <th>Billing Legal Name</th>
                <th>Total Qty</th>
                <th>Effective Rate</th>
                <th>Amount</th>
                <th>Discount</th>
                <th>Taxable Value</th>
                <th>GST Rate</th>
                <th>GST Amount</th>
                <th>Total Amount</th>
            </tr>
        </thead>
        <tbody>
            @php
            $grandTotalQty = 0;
            $grandTotalAmount = 0;
            $grandTotalDiscount = 0;
            $grandTaxableValue = 0;
            $grandTotalGst = 0;
            $grandTotalFinal = 0;
            @endphp

            @forelse($invoices as $key => $data)
            @php
            $invoice = $data['invoice'];
            $grandTotalQty += $data['total_qty'];
            $grandTotalAmount += $data['amount'];
            $grandTotalDiscount += $data['discount_amount'];
            $grandTaxableValue += $data['taxable_value'];
            $grandTotalGst += $data['gst_amount'];
            $grandTotalFinal += $data['total_amount'];
            @endphp
            <tr>
                <td>{{ $key + 1 }}</td>
                <td>{{ $invoiceData->inv_date ? \Carbon\Carbon::parse($invoiceData->inv_date)->format('d-m-Y') : '' }}</td>
                <td>{{ $invoice->ref_no }}</td>
                <td>{{ $data['vendor_name'] }}</td>
                <td>{{ $data['billing_legal_name'] }}</td>
                <td class="text-end">{{ number_format($data['total_qty']) }}</td>
                <td class="text-end">₹{{ number_format($data['rate'], 2) }}</td>
                <td class="text-end">₹{{ number_format($data['amount'], 2) }}</td>
                <td class="text-end">₹{{ number_format($data['discount_amount'], 2) }}</td>
                <td class="text-end">₹{{ number_format($data['taxable_value'], 2) }}</td>
                <td class="text-center">{{ $data['gst_rate'] }}%</td>
                <td class="text-end">₹{{ number_format($data['gst_amount'], 2) }}</td>
                <td class="text-end">₹{{ number_format($data['total_amount'], 2) }}</td>
            </tr>
            @empty
            <tr>
                <td colspan="13" class="text-center">No invoices found for the selected date range</td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>