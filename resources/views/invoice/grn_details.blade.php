<div class="modal-dialog modal-xl">
    <div class="modal-content">
        <div class="modal-header">
            <h5 class="modal-title">Update GRN Details - #{{ $invoice->ref_no }}</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
            <form id="GrnUpdateForm">
                <input type="hidden" name="id" value="{{ $invoice->id }}">
                <input type="hidden" name="total_invoice_qty" value="{{ $invoiceData['total_invoice_qty'] }}">
                <input type="hidden" name="unit_price_after_discount" value="{{ $invoiceData['unit_price_after_discount'] }}">
                <input type="hidden" name="invoice_gst_rate" value="{{ $invoiceData['gst_rate'] }}">

                <div class="row">
                    <!-- ASN Details -->
                    <div class="col-md-6 mb-3">
                        <div class="form-floating">
                            <input type="text" class="form-control" id="asn_no" name="asn_no" placeholder="ASN No" value="{{ $GrnDetails['asn_no'] ?? '' }}">
                            <label for="asn_no">ASN No</label>
                        </div>
                    </div>
                    <div class="col-md-6 mb-3">
                        <div class="form-floating">
                            <input type="date" class="form-control datepicker" id="asn_date" name="asn_date" placeholder="ASN Date" value="{{ $GrnDetails['asn_date'] ?? '' }}">
                            <label for="asn_date">ASN Date</label>
                        </div>
                    </div>

                    <!-- Transport Details -->
                    <div class="col-md-6 mb-3">
                        <div class="form-floating">
                            <input type="text" class="form-control" id="lr_no" name="lr_no" placeholder="LR No" value="{{ $GrnDetails['lr_no'] ?? '' }}">
                            <label for="lr_no">LR No</label>
                        </div>
                    </div>
                    <div class="col-md-6 mb-3">
                        <div class="form-floating">
                            <input type="text" class="form-control" id="transport_name" name="transport_name" placeholder="Transport Name" value="{{ $GrnDetails['transport_name'] ?? '' }}">
                            <label for="transport_name">Transport Name</label>
                        </div>
                    </div>

                    <div class="col-md-6 mb-3">
                        <div class="form-floating">
                            <input type="number" step="0.01" class="form-control" id="transporter_per_cost" name="transporter_per_cost" placeholder="Transporter Per Cost" value="{{ $GrnDetails['transporter_per_cost'] ?? '' }}">
                            <label for="transporter_per_cost">Transporter Per Cost</label>
                        </div>
                    </div>
                    <div class="col-md-6 mb-3">
                        <div class="form-floating">
                            <input type="number" step="0.01" class="form-control" id="transport_cost_total" name="transport_cost_total" placeholder="Transport Cost Total" value="{{ $GrnDetails['transport_cost_total'] ?? '' }}">
                            <label for="transport_cost_total">Transport Cost Total</label>
                        </div>
                    </div>

                    <!-- Dates -->
                    <div class="col-md-6 mb-3">
                        <div class="form-floating">
                            <input type="date" class="form-control" id="dispatched_date" name="dispatched_date" placeholder="Dispatched Date" value="{{ $GrnDetails['dispatched_date'] ?? '' }}">
                            <label for="dispatched_date">Dispatched Date</label>
                        </div>
                    </div>
                    <div class="col-md-6 mb-3">
                        <div class="form-floating">
                            <input type="date" class="form-control" id="reached_date" name="reached_date" placeholder="Reached Date" value="{{ $GrnDetails['reached_date'] ?? '' }}">
                            <label for="reached_date">Reached Date</label>
                        </div>
                    </div>

                    <div class="col-md-6 mb-3">
                        <div class="form-floating">
                            <input type="date" class="form-control" id="pod_date" name="pod_date" placeholder="POD Date" value="{{ $GrnDetails['pod_date'] ?? '' }}">
                            <label for="pod_date">POD Date</label>
                        </div>
                    </div>
                    <div class="col-md-6 mb-3">
                        <div class="form-floating">
                            <input type="date" class="form-control" id="grn_date" name="grn_date" placeholder="GRN Date" value="{{ $GrnDetails['grn_date'] ?? '' }}">
                            <label for="grn_date">GRN Date</label>
                        </div>
                    </div>

                    <!-- Quantities -->
                    <div class="col-md-4 mb-3">
                        <div class="form-floating">
                            <input type="number" step="0.01" class="form-control" id="invoice_qty" name="invoice_qty" placeholder="Invoice Qty" value="{{ $invoiceData['total_invoice_qty'] }}" readonly>
                            <label for="invoice_qty">Invoice Qty</label>
                        </div>
                    </div>
                    <div class="col-md-4 mb-3">
                        <div class="form-floating">
                            <input type="number" step="0.01" class="form-control" id="grn_qty" name="grn_qty" placeholder="GRN Qty" value="{{ $GrnDetails['grn_qty'] ?? '' }}">
                            <label for="grn_qty">GRN Qty</label>
                        </div>
                    </div>
                    <div class="col-md-4 mb-3">
                        <div class="form-floating">
                            <input type="number" step="0.01" class="form-control" id="short_inv_vs_grn" name="short_inv_vs_grn" placeholder="Short Inv vs GRN" value="{{ $GrnDetails['short_inv_vs_grn'] ?? '' }}" readonly>
                            <label for="short_inv_vs_grn">Short Inv vs GRN</label>
                        </div>
                    </div>

                    <!-- Discrepancy -->
                    <div class="col-md-6 mb-3">
                        <label class="form-label mb-2">Discrepancy</label>
                        <div class="d-flex gap-3">
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="discrepancy" id="discrepancy_yes" value="yes"
                                    {{ ($GrnDetails['discrepancy'] ?? '') == 'yes' ? 'checked' : '' }}>
                                <label class="form-check-label" for="discrepancy_yes">Yes</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="discrepancy" id="discrepancy_nil" value="nil"
                                    {{ ($GrnDetails['discrepancy'] ?? '') == 'nil' ? 'checked' : '' }}>
                                <label class="form-check-label" for="discrepancy_nil">No</label>
                            </div>
                        </div>
                    </div>

                    <!-- Remarks -->
                    <div class="col-md-6 mb-3">
                        <div class="form-floating">
                            <textarea class="form-control" id="remarks" name="remarks" placeholder="Remarks" style="height: 100px;">{{ $GrnDetails['remarks'] ?? '' }}</textarea>
                            <label for="remarks">Remarks</label>
                        </div>
                    </div>

                    <!-- Debit Note Details -->
                    <div class="col-md-6 mb-3">
                        <div class="form-floating">
                            <input type="number" step="0.01" class="form-control" id="debit_note_value" name="debit_note_value" placeholder="Debit Note Value" value="{{ $GrnDetails['debit_note_value'] ?? '' }}">
                            <label for="debit_note_value">Debit Note Value</label>
                        </div>
                    </div>
                    <div class="col-md-6 mb-3">
                        <div class="form-floating">
                            <input type="number" step="0.01" class="form-control" id="debit_note_tax_rate" name="debit_note_tax_rate" placeholder="Tax Rate %" value="{{ $GrnDetails['debit_note_tax_rate'] ?? $invoiceData['gst_rate'] }}">
                            <label for="debit_note_tax_rate">Debit Note Tax Rate (%)</label>
                        </div>
                    </div>

                    <div class="col-md-6 mb-3">
                        <div class="form-floating">
                            <input type="number" step="0.01" class="form-control" id="debit_note_tax_amount" name="debit_note_tax_amount" placeholder="Tax Amount" value="{{ $GrnDetails['debit_note_tax_amount'] ?? '' }}" readonly>
                            <label for="debit_note_tax_amount">Debit Note Tax Amount</label>
                        </div>
                    </div>
                    <div class="col-md-6 mb-3">
                        <div class="form-floating">
                            <input type="number" step="0.01" class="form-control" id="total_debit_note_value" name="total_debit_note_value" placeholder="Total Value" value="{{ $GrnDetails['total_debit_note_value'] ?? '' }}" readonly>
                            <label for="total_debit_note_value">Total Debit Note Value</label>
                        </div>
                    </div>

                    <!-- Additional Fields -->
                    <div class="col-md-6 mb-3">
                        <label for="business_head">Business Head</label>
                        <select class="form-control select2" id="business_head" name="business_head">
                            <option value="">Select Business Head</option>
                            <option value="Rajesh" {{ ($GrnDetails['business_head'] ?? '') == 'Rajesh' ? 'selected' : '' }}>Rajesh</option>
                            <option value="Divya" {{ ($GrnDetails['business_head'] ?? '') == 'Divya' ? 'selected' : '' }}>Divya</option>
                        </select>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="grn_status">GRN Status</label>
                        <select class="form-control select2" id="grn_status" name="grn_status">
                            <option value="">Select GRN Status</option>
                            <option value="In Transit" {{ ($GrnDetails['status'] ?? '') == 'In Transit' ? 'selected' : '' }}>In Transit</option>
                            <option value="GRN Pending" {{ ($GrnDetails['status'] ?? '') == 'GRN Pending' ? 'selected' : '' }}>GRN Pending</option>
                            <option value="GRN Done" {{ ($GrnDetails['status'] ?? '') == 'GRN Done' ? 'selected' : '' }}>GRN Done</option>
                            <option value="Payment Pending" {{ ($GrnDetails['status'] ?? '') == 'Payment Pending' ? 'selected' : '' }}>Payment Pending</option>
                            <option value="Payment Received" {{ ($GrnDetails['status'] ?? '') == 'Payment Received' ? 'selected' : '' }}>Payment Received</option>
                            <option value="Invoice Not Disposed" {{ ($GrnDetails['status'] ?? '') == 'Invoice Not Disposed' ? 'selected' : '' }}>Invoice Not Disposed</option>
                            <option value="Cancelled" {{ ($GrnDetails['status'] ?? '') == 'Cancelled' ? 'selected' : '' }}>Cancelled</option>
                        </select>
                    </div>

                    <div class="col-md-6 mb-3">
                        <label for="status">Status</label>
                        <select class="form-control select2" id="status" name="status">
                            <option value="">Select Status</option>
                            <option value="In Transit" {{ ($GrnDetails['status'] ?? '') == 'In Transit' ? 'selected' : '' }}>In Transit</option>
                            <option value="GRN Pending" {{ ($GrnDetails['status'] ?? '') == 'GRN Pending' ? 'selected' : '' }}>GRN Pending</option>
                            <option value="GRN Done" {{ ($GrnDetails['status'] ?? '') == 'GRN Pending' ? 'selected' : '' }}>GRN Done</option>
                            <option value="Payment Pending" {{ ($GrnDetails['status'] ?? '') == 'Payment Pending' ? 'selected' : '' }}>Payment Pending</option>
                            <option value="Payment Received" {{ ($GrnDetails['status'] ?? '') == 'Payment Received' ? 'selected' : '' }}>Payment Received</option>
                            <option value="Invoice Not Disposed" {{ ($GrnDetails['status'] ?? '') == 'Invoice Not Disposed' ? 'selected' : '' }}>Invoice Not Disposed</option>
                            <option value="Cancelled" {{ ($GrnDetails['status'] ?? '') == 'Cancelled' ? 'selected' : '' }}>Cancelled</option>
                        </select>
                    </div>

                    <div class="col-md-6 mb-3">
                        <label for="week">Week</label>
                        <select class="form-control select2" id="week" name="week">
                            <option value="">Select Week</option>
                            <option value="Week 1" {{ ($GrnDetails['week'] ?? '') == 'Week 1' ? 'selected' : '' }}>Week 1</option>
                            <option value="Week 2" {{ ($GrnDetails['week'] ?? '') == 'Week 2' ? 'selected' : '' }}>Week 2</option>
                            <option value="Week 3" {{ ($GrnDetails['week'] ?? '') == 'Week 3' ? 'selected' : '' }}>Week 3</option>
                            <option value="Week 4" {{ ($GrnDetails['week'] ?? '') == 'Week 4' ? 'selected' : '' }}>Week 4</option>
                            <option value="Week 5" {{ ($GrnDetails['week'] ?? '') == 'Week 5' ? 'selected' : '' }}>Week 5</option>
                            <option value="Week 6" {{ ($GrnDetails['week'] ?? '') == 'Week 6' ? 'selected' : '' }}>Week 6</option>
                            <option value="Week 7" {{ ($GrnDetails['week'] ?? '') == 'Week 7' ? 'selected' : '' }}>Week 7</option>
                            <option value="Week 8" {{ ($GrnDetails['week'] ?? '') == 'Week 8' ? 'selected' : '' }}>Week 8</option>
                            <option value="Week 9" {{ ($GrnDetails['week'] ?? '') == 'Week 9' ? 'selected' : '' }}>Week 9</option>
                            <option value="Week 10" {{ ($GrnDetails['week'] ?? '') == 'Week 10' ? 'selected' : '' }}>Week 10</option>
                            <option value="Week 11" {{ ($GrnDetails['week'] ?? '') == 'Week 11' ? 'selected' : '' }}>Week 11</option>
                            <option value="Week 12" {{ ($GrnDetails['week'] ?? '') == 'Week 12' ? 'selected' : '' }}>Week 12</option>
                            <option value="Week 13" {{ ($GrnDetails['week'] ?? '') == 'Week 13' ? 'selected' : '' }}>Week 13</option>
                            <option value="Week 14" {{ ($GrnDetails['week'] ?? '') == 'Week 14' ? 'selected' : '' }}>Week 14</option>
                            <option value="Week 15" {{ ($GrnDetails['week'] ?? '') == 'Week 15' ? 'selected' : '' }}>Week 15</option>
                            <option value="Week 16" {{ ($GrnDetails['week'] ?? '') == 'Week 16' ? 'selected' : '' }}>Week 16</option>
                            <option value="Week 17" {{ ($GrnDetails['week'] ?? '') == 'Week 17' ? 'selected' : '' }}>Week 17</option>
                            <option value="Week 18" {{ ($GrnDetails['week'] ?? '') == 'Week 18' ? 'selected' : '' }}>Week 18</option>
                            <option value="Week 19" {{ ($GrnDetails['week'] ?? '') == 'Week 19' ? 'selected' : '' }}>Week 19</option>
                            <option value="Week 20" {{ ($GrnDetails['week'] ?? '') == 'Week 20' ? 'selected' : '' }}>Week 20</option>
                            <option value="Week 21" {{ ($GrnDetails['week'] ?? '') == 'Week 21' ? 'selected' : '' }}>Week 21</option>
                            <option value="Week 22" {{ ($GrnDetails['week'] ?? '') == 'Week 22' ? 'selected' : '' }}>Week 22</option>
                            <option value="Week 23" {{ ($GrnDetails['week'] ?? '') == 'Week 23' ? 'selected' : '' }}>Week 23</option>
                            <option value="Week 24" {{ ($GrnDetails['week'] ?? '') == 'Week 24' ? 'selected' : '' }}>Week 24</option>
                            <option value="Week 25" {{ ($GrnDetails['week'] ?? '') == 'Week 25' ? 'selected' : '' }}>Week 25</option>
                            <option value="Week 26" {{ ($GrnDetails['week'] ?? '') == 'Week 26' ? 'selected' : '' }}>Week 26</option>
                            <option value="Week 27" {{ ($GrnDetails['week'] ?? '') == 'Week 27' ? 'selected' : '' }}>Week 27</option>
                            <option value="Week 28" {{ ($GrnDetails['week'] ?? '') == 'Week 28' ? 'selected' : '' }}>Week 28</option>
                            <option value="Week 29" {{ ($GrnDetails['week'] ?? '') == 'Week 29' ? 'selected' : '' }}>Week 29</option>
                            <option value="Week 30" {{ ($GrnDetails['week'] ?? '') == 'Week 30' ? 'selected' : '' }}>Week 30</option>
                            <option value="Week 31" {{ ($GrnDetails['week'] ?? '') == 'Week 31' ? 'selected' : '' }}>Week 31</option>
                            <option value="Week 32" {{ ($GrnDetails['week'] ?? '') == 'Week 32' ? 'selected' : '' }}>Week 32</option>
                            <option value="Week 33" {{ ($GrnDetails['week'] ?? '') == 'Week 33' ? 'selected' : '' }}>Week 33</option>
                            <option value="Week 34" {{ ($GrnDetails['week'] ?? '') == 'Week 34' ? 'selected' : '' }}>Week 34</option>
                            <option value="Week 35" {{ ($GrnDetails['week'] ?? '') == 'Week 35' ? 'selected' : '' }}>Week 35</option>
                            <option value="Week 36" {{ ($GrnDetails['week'] ?? '') == 'Week 36' ? 'selected' : '' }}>Week 36</option>
                            <option value="Week 37" {{ ($GrnDetails['week'] ?? '') == 'Week 37' ? 'selected' : '' }}>Week 37</option>
                            <option value="Week 38" {{ ($GrnDetails['week'] ?? '') == 'Week 38' ? 'selected' : '' }}>Week 38</option>
                            <option value="Week 39" {{ ($GrnDetails['week'] ?? '') == 'Week 39' ? 'selected' : '' }}>Week 39</option>
                            <option value="Week 40" {{ ($GrnDetails['week'] ?? '') == 'Week 40' ? 'selected' : '' }}>Week 40</option>
                            <option value="Week 41" {{ ($GrnDetails['week'] ?? '') == 'Week 41' ? 'selected' : '' }}>Week 41</option>
                            <option value="Week 42" {{ ($GrnDetails['week'] ?? '') == 'Week 42' ? 'selected' : '' }}>Week 42</option>
                            <option value="Week 43" {{ ($GrnDetails['week'] ?? '') == 'Week 43' ? 'selected' : '' }}>Week 43</option>
                            <option value="Week 44" {{ ($GrnDetails['week'] ?? '') == 'Week 44' ? 'selected' : '' }}>Week 44</option>
                            <option value="Week 45" {{ ($GrnDetails['week'] ?? '') == 'Week 45' ? 'selected' : '' }}>Week 45</option>
                            <option value="Week 46" {{ ($GrnDetails['week'] ?? '') == 'Week 46' ? 'selected' : '' }}>Week 46</option>
                            <option value="Week 47" {{ ($GrnDetails['week'] ?? '') == 'Week 47' ? 'selected' : '' }}>Week 47</option>
                            <option value="Week 48" {{ ($GrnDetails['week'] ?? '') == 'Week 48' ? 'selected' : '' }}>Week 48</option>
                            <option value="Week 49" {{ ($GrnDetails['week'] ?? '') == 'Week 49' ? 'selected' : '' }}>Week 49</option>
                            <option value="Week 50" {{ ($GrnDetails['week'] ?? '') == 'Week 50' ? 'selected' : '' }}>Week 50</option>
                            <option value="Week 51" {{ ($GrnDetails['week'] ?? '') == 'Week 51' ? 'selected' : '' }}>Week 51</option>
                            <option value="Week 52" {{ ($GrnDetails['week'] ?? '') == 'Week 52' ? 'selected' : '' }}>Week 52</option>
                        </select>
                    </div>

                    <div class="col-md-6 mb-3">
                        <div class="form-floating">
                            <input type="date" class="form-control" id="fl_clear_date" name="fl_clear_date" placeholder="FL Clear Date" value="{{ $GrnDetails['fl_clear_date'] ?? '' }}">
                            <label for="fl_clear_date">FL Clear Date</label>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-sm-12">
                        <button class="btn btn-primary" type="submit" id="submit_btn" name="submit_btn" style="float:right">Submit</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>