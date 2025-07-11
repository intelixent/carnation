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
                            <td><input type="checkbox" class="po_pack" name="po_pack" value="<?php echo $packed_list->id; ?> " /></td>
                            <td><?php echo $packed_list->pack_ref_no; ?></td>
                            <td><?php echo \Carbon\Carbon::parse($packed_list->created_at)->format('d-m-Y h:i A');; ?></td>
                            <td><?php echo $packed_list->color; ?></td>
                            <td><?php echo $packed_list->carton_count; ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <div id="generateBtnContainer" style="display:none; margin-top: 15px;" class="mt-4">
            <div class="row mb-2">
                <div class="col-md-6">
                    <label for="invoice_no">Invoice No</label>
                    <input type="text" id="invoice_no" name="invoice_no" class="form-control" placeholder="Invoice No." />
                </div>
                <div class="col-md-6">
                    <label for="invoice_date">Invoice Date</label>
                    <input type="text" id="invoice_date" name="invoice_date" class="form-control" placeholder="Invoice Date" />
                </div>
            </div>
            <button id="generateInvoiceBtn" class="btn btn-success">
                Generate Invoice
            </button>
        </div>
    </div>
</div>