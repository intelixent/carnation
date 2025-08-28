<!-- <div class="mt-4 d-flex justify-content-end gap-2">
    <a id="downloadExcelData"
        href="{{ route('e_invoice_excel_download', ['from_date' => $from_date ?? request('from_date'), 'to_date' => $to_date ?? request('to_date')]) }}"
        class="btn btn-success btn-lg"
        target="_blank">
        <i class="fa fa-file-excel"></i> Download Excel
    </a>

</div> -->


<div class="table-responsive mt-2">
    <table class="table table-bordered text-nowrap myTable w-100" >
        <thead class="bg-primary text-white">
            <tr>
                <th>S.No <input type="checkbox" class="check_all" id="0" value="0" /></th>
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
                <td>{{ $key + 1 }}

                <input type="checkbox" class="batch_sno" name="batch_sno[]" id="<?php echo $invoice->id;?>" value="<?php echo $invoice->id;?>"/>

                </td>
                <td>{{ $invoice->inv_date ? \Carbon\Carbon::parse($invoice->inv_date)->format('d-m-Y') : '' }}</td>
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

<!-- <a id="downloadExcelData"
        href="{{ route('e_invoice_excel_download', ['from_date' => $from_date ?? request('from_date'), 'to_date' => $to_date ?? request('to_date')]) }}"
        class="btn btn-success btn-lg"
        target="_blank">
        <i class="fa fa-file-excel"></i> Download Excel
    </a> -->

<div class="d-grid gap-2 d-md-block" >
  <a  class="btn btn-primary update_graph_points download_excel d-none downloadExcelData" id="fixedbutton"  type="button"><i class="fa fa-download"></i> Excel</a>

</div>