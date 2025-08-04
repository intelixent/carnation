<div class="row">
    <input type="hidden" value="<?php echo $poId; ?>" name="selected_po" class="selected_po" />
    <input type="hidden" value="<?php echo $vendor_id; ?>" name="selected_vendor_id" class="selected_vendor_id" />
    <div class="col-md-12">
        <h5>Select Packing List</h5>
        <div class="table-responsive">
            <table class="table table-bordered" style="width:100%">
                <thead>
                    <tr>
                        <th></th>
                        <th>Packing Ref No</th>
                        <th>Packed At</th>
                        <th>Colors</th>
                        <th>Carton Counts</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    foreach ($packed_lists as $packed_list):
                    ?>
                        <tr>
                            <td><input type="checkbox" class="po_pack" name="po_pack" value="<?php echo $packed_list->id; ?>" /></td>
                            <td><?php echo $packed_list->pack_ref_no; ?></td>
                            <td><?php echo \Carbon\Carbon::parse($packed_list->created_at)->format('d-m-Y h:i A'); ?></td>
                            <td><?php echo $packed_list->color; ?></td>
                            <td><?php echo $packed_list->carton_count; ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <div id="generateBtnContainer" style="display:none; margin-top: 15px;" class="mt-4">
            <div class="row mb-2">
                <div class="col-md-4">
                    <label for="invoice_no" class="form-label">Invoice No <span class="text-danger">*</span></label>
                    <input type="text" id="invoice_no" name="invoice_no" class="form-control" placeholder="Enter Invoice No." required />
                </div>
                <div class="col-md-4">
                    <label for="invoice_date" class="form-label">Invoice Date <span class="text-danger">*</span></label>
                    <input type="date" id="invoice_date" name="invoice_date" class="form-control" required />
                </div>
                <div class="col-md-4">
                    <label for="gst_rate" class="form-label">GST Rate (%) <span class="text-danger">*</span></label>
                    <input type="number" id="gst_rate" name="gst_rate" class="form-control"
                        placeholder="Enter GST Rate" step="0.01" min="0" max="100" required />
                </div>
            </div>
            <div class="row mb-2">
                <div class="col-md-12">
                    <button id="generateInvoiceBtn" class="btn btn-success">
                        <i class="fas fa-file-invoice"></i> Generate Invoice
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>