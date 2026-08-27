@extends('layouts.app')

@section('pagetitle', 'Bulk Import PO')

@section('content')
<style>
    .bulk-card-header {
        background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%) !important;
        color: #ffffff;
        border-bottom: 2px solid #3b82f6;
    }

    .file-badge {
        display: inline-block;
        background: #e2e8f0;
        color: #1e293b;
        padding: 4px 12px;
        border-radius: 20px;
        font-size: 0.85rem;
        margin: 4px;
        font-weight: 600;
    }

    .loader-overlay {
        display: none;
        position: fixed;
        top: 0; left: 0; width: 100%; height: 100%;
        background: rgba(15, 23, 42, 0.6);
        backdrop-filter: blur(4px);
        z-index: 9999;
        color: white;
        text-align: center;
        padding-top: 20vh;
    }

    .po-main-tab .nav-link {
        font-weight: 700;
        font-size: 1rem;
        padding: 12px 24px;
        border-radius: 8px 8px 0 0;
        background-color: #e2e8f0;
        color: #334155;
        margin-right: 4px;
        border: none;
        transition: all 0.2s ease;
    }
    .po-main-tab .nav-link.active {
        background-color: #2563eb !important;
        color: #ffffff !important;
        box-shadow: 0 4px 6px -1px rgba(37, 99, 235, 0.3);
    }

    .po-sub-tab .nav-link {
        font-weight: 600;
        font-size: 0.9rem;
        padding: 8px 18px;
        border-radius: 6px;
        color: #475569;
        background: #f1f5f9;
        margin-right: 6px;
        border: 1px solid #cbd5e1;
    }
    .po-sub-tab .nav-link.active {
        background-color: #0f172a !important;
        color: #ffffff !important;
        border-color: #0f172a !important;
    }

    .preview-card {
        border-radius: 12px;
        border: 1px solid #e2e8f0;
        background: #ffffff;
        box-shadow: 0 2px 10px rgba(0,0,0,0.03);
    }

    .invoice-box {
        border: 2px solid #000;
        padding: 20px;
        background-color: #ffffff;
        color: #000;
        font-family: Arial, sans-serif;
        font-size: 11px;
    }

    .invoice-lines-table, .packing-items-table {
        width: 100%;
        border-collapse: collapse;
    }
    .invoice-lines-table th, .invoice-lines-table td,
    .packing-items-table th, .packing-items-table td {
        border: 1px solid #000;
        padding: 5px 6px;
        font-size: 11px;
        text-align: center;
    }
    .invoice-lines-table th {
        background-color: #f2f2f2;
        font-weight: bold;
    }

    .mixed-carton-row {
        background-color: #fffbeb !important;
    }

    .sticky-bottom-bar {
        position: sticky;
        bottom: 0;
        background: rgba(255, 255, 255, 0.95);
        backdrop-filter: blur(8px);
        padding: 16px 24px;
        border-top: 2px solid #cbd5e1;
        box-shadow: 0 -4px 12px rgba(0,0,0,0.08);
        z-index: 1050;
    }
</style>

<div class="loader-overlay" id="loadingOverlay">
    <div class="spinner-border text-primary" style="width: 4rem; height: 4rem;" role="status">
        <span class="visually-hidden">Processing...</span>
    </div>
    <h4 class="mt-3 text-white">Extracting POs, Generating Packing Lists & Invoices...</h4>
    <p class="text-light">Please wait while the PDF engine processes your files.</p>
</div>

<div class="container-fluid">
    <!-- BreadCrumbs -->
    <div class="d-md-flex d-block align-items-center justify-content-between my-4 page-header-breadcrumb">
        <div class="my-auto">
            <h5 class="page-title fs-21 mb-1">Bulk Import Purchase Orders</h5>
            <nav>
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="javascript:void(0);">{{ $page_main_title }}</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Bulk Import</li>
                </ol>
            </nav>
        </div>
    </div>
    <!-- BreadCrumbs -->

    <div class="row">
        <div class="col-xl-12">
            <div class="card shadow-sm border-0 mb-4">
                <div class="card-header bulk-card-header d-flex justify-content-between align-items-center">
                    <h5 class="card-title text-white mb-0">
                        <i class="fas fa-file-import me-2 text-warning"></i>Bulk PO Upload & Document Generator
                    </h5>
                </div>
                <div class="card-body p-4">
                    <form id="bulkPdfExtractForm" enctype="multipart/form-data">
                        @csrf
                        <div class="row mb-4">
                            <div class="col-md-5">
                                <label for="vendor_id" class="form-label fw-bold text-dark">Select Vendor <span class="text-danger">*</span></label>
                                <select name="vendor_id" id="vendor_id" class="form-select select2" required>
                                    @foreach($vendors as $vendor)
                                    <option value="{{ $vendor->id }}" {{ $vendor->id == 1 ? 'selected' : '' }}>
                                        {{ $vendor->name }} {{ $vendor->id == 1 ? '(Jack & Jones)' : '' }}
                                    </option>
                                    @endforeach
                                </select>
                                <small class="text-muted d-block mt-1">Vendor ID 1 Jack & Jones is optimized for automatic color-wise packing lists and tax invoices.</small>
                            </div>

                            <div class="col-md-7">
                                <label for="pdf_files" class="form-label fw-bold text-dark">Upload Multiple PO PDF Files <span class="text-danger">*</span></label>
                                <input class="form-control form-control-lg" type="file" id="pdf_files" name="pdf_files[]" multiple accept=".pdf" required>
                                <div id="selectedFilesList" class="mt-2"></div>
                            </div>
                        </div>

                        <div class="d-flex justify-content-end">
                            <button type="submit" class="btn btn-primary px-4 py-2 text-white fw-bold" id="bulk_submit_btn">
                                <i class="fas fa-bolt me-2"></i> Extract & Preview All POs
                            </button>
                        </div>
                    </form>

                    <!-- Results Section -->
                    <div id="bulkResultsContainer" class="mt-4"></div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script type="text/javascript">
    var globalPosData = [];

    function updateJobNo(poIdx) {
        let newJobNo = $('#inputJobNo-' + poIdx).val().trim();

        $('#displayJobNoHeader-' + poIdx).text(newJobNo ? newJobNo : 'N/A');

        let poData = globalPosData[poIdx];
        if (!poData) return;

        poData.job_no = newJobNo;

        let plSeq = 1;
        let cIdx = 0;
        if (poData.packing_lists) {
            $.each(poData.packing_lists, function(colorName, pList) {
                let newPackRef = newJobNo ? (newJobNo + '/' + plSeq) : ('' + plSeq);
                pList.pack_ref_no = newPackRef;
                $('#packRefNoDisplay-' + poIdx + '-' + cIdx).text(newPackRef);
                plSeq++;
                cIdx++;
            });
        }

        $('#pos_data_payload').val(JSON.stringify(globalPosData));
    }

    function updateInvoiceHeader(poIdx, colorName, invIdx) {
        let refNo = $('#invRefNo-' + poIdx + '-' + invIdx).val();
        let invDate = $('#invDate-' + poIdx + '-' + invIdx).val();
        let gstRate = parseFloat($('#invGstRate-' + poIdx + '-' + invIdx).val()) || 5.00;
        let transportId = $('#invTransport-' + poIdx + '-' + invIdx).val();
        let transporterName = $('#invTransport-' + poIdx + '-' + invIdx + ' option:selected').text();
        let supplyDate = $('#invSupplyDate-' + poIdx + '-' + invIdx).val();

        $('#displayInvRefNo-' + poIdx + '-' + invIdx).text(refNo);
        $('#displayInvDate-' + poIdx + '-' + invIdx).text(invDate);
        $('#displayTransporterName-' + poIdx + '-' + invIdx).text(transporterName);
        $('#displaySupplyDate-' + poIdx + '-' + invIdx).text(supplyDate);

        let invData = globalPosData[poIdx].invoices_by_color[colorName];
        if (invData) {
            invData.ref_no = refNo;
            invData.inv_date = invDate;
            invData.igst_rate = gstRate;
            invData.transport_id = transportId;
            invData.supply_date = supplyDate;

            let perUnitPrice = globalPosData[poIdx].per_unit_price || 0;
            let vendorDiscount = globalPosData[poIdx].vendor_discount || 0;

            let newGrossTotal = 0;
            let newDiscountTotal = 0;
            let newTaxableTotal = 0;
            let newIgstTotal = 0;
            let newFinalTotal = 0;

            let linesHtml = '';
            let lineCount = invData.invoice_lines.length;
            let totCartons = invData.total_cartons;

            $.each(invData.invoice_lines, function(lIdx, line) {
                line.igst_rate = gstRate;
                let grossAmt = line.qty * perUnitPrice;
                let discAmt = grossAmt * (vendorDiscount / 100);
                let taxable = grossAmt - discAmt;
                let igstAmt = taxable * (gstRate / 100);
                let lineTotal = taxable + igstAmt;

                line.taxable_value = taxable;
                line.igst_amount = igstAmt;
                line.total_line_amount = lineTotal;

                newGrossTotal += grossAmt;
                newDiscountTotal += discAmt;
                newTaxableTotal += taxable;
                newIgstTotal += igstAmt;
                newFinalTotal += lineTotal;

                linesHtml += `<tr>
                    <td>${line.sno}</td>
                    <td class="text-start"><strong>${line.description}</strong></td>
                    <td>${line.hsn_code}</td>
                    <td>${line.style_no}</td>
                    <td>${line.color}</td>
                    ${lIdx === 0 ? `<td rowspan="${lineCount}" class="align-middle fw-bold inv-tot-cartons-cell-${poIdx}-${invIdx}">${totCartons}</td>` : ''}
                    <td>${line.unit}</td>
                    <td><strong>${line.qty}</strong></td>
                    <td>${line.rate.toFixed(2)}</td>
                    <td>${grossAmt.toFixed(2)}</td>
                    <td>${discAmt.toFixed(2)}</td>
                    <td class="line-taxable-val">${taxable.toFixed(2)}</td>
                    <td class="line-igst-rate">${gstRate.toFixed(2)}%</td>
                    <td class="line-igst-amt">${igstAmt.toFixed(2)}</td>
                </tr>`;
            });

            invData.gross_amount = newGrossTotal;
            invData.total_discount = newDiscountTotal;
            invData.taxable_value = newTaxableTotal;
            invData.igst_amount = newIgstTotal;
            invData.final_total = newFinalTotal;
            invData.amount_in_words = convertNumberToWords(newFinalTotal);

            $('#invLinesTbody-' + poIdx + '-' + invIdx).html(linesHtml);
            $('#invTotGross-' + poIdx + '-' + invIdx).text(newGrossTotal.toFixed(2));
            $('#invTotDisc-' + poIdx + '-' + invIdx).text(newDiscountTotal.toFixed(2));
            $('#invTotTaxable-' + poIdx + '-' + invIdx).text(newTaxableTotal.toFixed(2));
            $('#invTotIgst-' + poIdx + '-' + invIdx).text(newIgstTotal.toFixed(2));
            $('#invGrandTotal-' + poIdx + '-' + invIdx).text('INR ' + newFinalTotal.toFixed(2));
            $('#invWords-' + poIdx + '-' + invIdx).text(invData.amount_in_words);
        }

        $('#pos_data_payload').val(JSON.stringify(globalPosData));
    }

    function recalculateCartons(poIdx) {
        let cartonOpt = $('#cartonTypeSelect-' + poIdx + ' option:selected');
        let cartonId = cartonOpt.val();
        let cLen = parseFloat(cartonOpt.data('len')) || 60;
        let cBrd = parseFloat(cartonOpt.data('brd')) || 40;
        let cHgt = parseFloat(cartonOpt.data('hgt')) || 40;
        let cWt = parseFloat(cartonOpt.data('wt')) || 1.2;

        let poData = globalPosData[poIdx];
        if (!poData || !poData.packing_lists) return;

        let excessPct = poData.vendor_excess || 0;
        poData.selected_carton_id = cartonId;
        let sizeOrderMap = poData.size_order_map || {};

        let sizeCaps = {};
        let sizeNetWts = {};
        $('#sizeCapsContainer-' + poIdx + ' .per-size-cap-input').each(function() {
            let sz = $(this).data('size');
            let cap = parseInt($(this).val()) || 60;
            sizeCaps[sz.toUpperCase()] = cap;
        });
        $('#sizeCapsContainer-' + poIdx + ' .per-size-netwt-input').each(function() {
            let sz = $(this).data('size');
            let nw = parseFloat($(this).val());
            if (!isNaN(nw)) {
                sizeNetWts[sz.toUpperCase()] = nw;
            }
        });

        let cIdxCounter = 0;

        $.each(poData.packing_lists, function(colorName, pList) {
            let cartonCounter = 1;
            let newCartonList = [];
            let sizePackQuantities = {};

            let sortedPoItems = [...poData.po_items];
            sortedPoItems.sort(function(a, b) {
                let szA = (a.size_years || '').toUpperCase().replace(/\s+/g, '');
                let szB = (b.size_years || '').toUpperCase().replace(/\s+/g, '');
                let posA = sizeOrderMap[szA] !== undefined ? sizeOrderMap[szA] : 999;
                let posB = sizeOrderMap[szB] !== undefined ? sizeOrderMap[szB] : 999;
                return posA - posB;
            });

            $.each(sortedPoItems, function(i, pi) {
                let idColorField = pi.artcicle_id_color || '';
                let cName = idColorField.includes('/') ? idColorField.split('/')[1].trim() : idColorField.trim();
                if (!cName) cName = poData.po_details.Colors || 'DEFAULT';

                if (cName !== colorName) return;

                let qOrder = parseFloat((pi.quatity_uom || '0').replace(/[^0-9.]/g, '')) || 0;
                let sz = pi.size_years || 'OS';
                let cleanSz = sz.toUpperCase().replace(/\s+/g, '');
                let art = pi.article_number || '';
                let ean = pi.ean_code || '';

                let maxPackCap = Math.floor(qOrder * (1 + (excessPct / 100)));
                let qPack = maxPackCap;

                sizePackQuantities[sz] = (sizePackQuantities[sz] || 0) + qPack;

                let cap = sizeCaps[sz.toUpperCase()] || (['9/10Y', '11/12Y', '13/14Y', 'XL', 'XXL', 'XXXL'].includes(cleanSz) ? 50 : 60);

                if (qPack <= cap) {
                    let cNameTag = 'C' + cartonCounter;
                    let netWeight = sizeNetWts[sz.toUpperCase()] !== undefined ? sizeNetWts[sz.toUpperCase()] : parseFloat((qPack * 0.25).toFixed(2));
                    let grossWeight = parseFloat((netWeight + cWt).toFixed(2));
                    let cbm = parseFloat(((cLen * cBrd * cHgt) / 1000000).toFixed(4));

                    newCartonList.push({
                        carton_name: cNameTag,
                        po_no: pList.po_no,
                        article_number: art,
                        article_description: poData.article_info['Article description'] || '',
                        ean_code: ean,
                        color: colorName,
                        size: sz,
                        quantity: qPack,
                        net_weight: netWeight,
                        gross_weight: grossWeight,
                        cbm: cbm,
                        carton_length: cLen,
                        carton_breadth: cBrd,
                        carton_height: cHgt,
                        carton_id: cartonId,
                        is_mixed: false
                    });
                    cartonCounter++;
                } else {
                    let fullCount = Math.floor(qPack / cap);
                    let fullNetWeight = sizeNetWts[sz.toUpperCase()] !== undefined ? sizeNetWts[sz.toUpperCase()] : parseFloat((cap * 0.25).toFixed(2));

                    for (let fc = 0; fc < fullCount; fc++) {
                        let cNameTag = 'C' + cartonCounter;
                        let netWeight = fullNetWeight;
                        let grossWeight = parseFloat((netWeight + cWt).toFixed(2));
                        let cbm = parseFloat(((cLen * cBrd * cHgt) / 1000000).toFixed(4));

                        newCartonList.push({
                            carton_name: cNameTag,
                            po_no: pList.po_no,
                            article_number: art,
                            article_description: poData.article_info['Article description'] || '',
                            ean_code: ean,
                            color: colorName,
                            size: sz,
                            quantity: cap,
                            net_weight: netWeight,
                            gross_weight: grossWeight,
                            cbm: cbm,
                            carton_length: cLen,
                            carton_breadth: cBrd,
                            carton_height: cHgt,
                            carton_id: cartonId,
                            is_mixed: false
                        });
                        cartonCounter++;
                    }
                }
            });

            let uniqueCartons = [...new Set(newCartonList.map(c => c.carton_name))];
            let totCartonCount = uniqueCartons.length;

            pList.cartons = newCartonList;
            pList.total_cartons = totCartonCount;
            pList.size_pack_summary = sizePackQuantities;

            if (poData.invoices_by_color && poData.invoices_by_color[colorName]) {
                poData.invoices_by_color[colorName].total_cartons = totCartonCount;
            }

            renderCartonTableDOM(poIdx, colorName, cIdxCounter);
            cIdxCounter++;
        });

        $('#pos_data_payload').val(JSON.stringify(globalPosData));
    }

    function renderCartonTableDOM(poIdx, colorName, cIdx) {
        let poData = globalPosData[poIdx];
        let pList = poData ? poData.packing_lists[colorName] : null;
        if (!pList) return;

        let tbodyHtml = '';
        let totQty = 0, totNet = 0, totGross = 0, totCbm = 0;

        let uniqueCartons = [...new Set(pList.cartons.map(c => c.carton_name))];
        let totCartonCount = uniqueCartons.length;

        $.each(pList.cartons, function(crIdx, cRow) {
            totQty += cRow.quantity;
            totNet += cRow.net_weight;
            totGross += cRow.gross_weight;
            totCbm += cRow.cbm;
            let isMixed = cRow.is_mixed || false;

            tbodyHtml += `<tr class="${isMixed ? 'mixed-carton-row' : ''}">
                <td><strong>${cRow.carton_name}</strong> ${isMixed ? '<span class="badge bg-warning text-dark" style="font-size: 8px;">MIXED</span>' : ''}</td>
                <td>${cRow.po_no}</td>
                <td>${cRow.article_number}</td>
                <td>${cRow.article_description}</td>
                <td>${cRow.ean_code}</td>
                <td><strong>${cRow.size}</strong></td>
                <td><strong>${cRow.quantity}</strong></td>
                <td>${cRow.carton_length}</td>
                <td>${cRow.carton_breadth}</td>
                <td>${cRow.carton_height}</td>
                <td class="net-wt-val">${cRow.net_weight.toFixed(2)}</td>
                <td class="gross-wt-val">${cRow.gross_weight.toFixed(2)}</td>
                <td class="cbm-val">${cRow.cbm.toFixed(3)}</td>
                <td>
                    <button type="button" class="btn btn-sm btn-outline-danger shadow-sm py-0 px-1" title="Remove Carton" onclick="deleteCarton(${poIdx}, '${colorName}', '${cRow.carton_name}', ${cIdx})"><i class="fas fa-trash-alt"></i></button>
                </td>
            </tr>`;
        });

        pList.total_cartons = totCartonCount;
        if (poData && poData.invoices_by_color && poData.invoices_by_color[colorName]) {
            poData.invoices_by_color[colorName].total_cartons = totCartonCount;
        }

        $('#cartonTbody-' + poIdx + '-' + cIdx).html(tbodyHtml);
        $('#totalCartonQty-' + poIdx + '-' + cIdx).text(totQty);
        $('#totalNetWt-' + poIdx + '-' + cIdx).text(totNet.toFixed(2));
        $('#totalGrossWt-' + poIdx + '-' + cIdx).text(totGross.toFixed(2));
        $('#totalCbm-' + poIdx + '-' + cIdx).text(totCbm.toFixed(3));
        $('#totalCartonsBadge-' + poIdx + '-' + cIdx).text('Total Cartons: ' + totCartonCount);
        $('.inv-tot-cartons-cell-' + poIdx + '-' + cIdx).text(totCartonCount);

        let szHtml = '';
        $.each(pList.size_summary, function(szKey, szOrdQty) {
            let szPackQty = pList.size_pack_summary[szKey] || szOrdQty;
            let packedSoFar = 0;
            $.each(pList.cartons, function(k, c) {
                if (c.size === szKey) packedSoFar += c.quantity;
            });

            let balance = szPackQty - packedSoFar;
            let pct = szOrdQty > 0 ? ((szPackQty / szOrdQty) * 100).toFixed(2) : '100.00';

            szHtml += `<tr>
                <td><strong>${szKey}</strong></td>
                <td>${szOrdQty}</td>
                <td class="text-success fw-bold">${szPackQty}</td>
                <td class="${balance > 0 ? 'text-danger fw-bold' : ''}">${balance}</td>
                <td>${pct}%</td>
            </tr>`;
        });
        $('#sizeSummaryTbody-' + poIdx + '-' + cIdx).html(szHtml);
    }

    function deleteCarton(poIdx, colorName, cartonName, cIdx) {
        Swal.fire({
            title: 'Remove Carton ' + cartonName + '?',
            text: 'Are you sure you want to remove ' + cartonName + '? Its quantities will be returned to the balance pool for creating mixed cartons.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Yes, Remove'
        }).then((result) => {
            if (result.isConfirmed) {
                let poData = globalPosData[poIdx];
                let pList = poData ? poData.packing_lists[colorName] : null;

                if (pList && pList.cartons) {
                    pList.cartons = pList.cartons.filter(c => c.carton_name !== cartonName);

                    let remainingCartonNames = [];
                    $.each(pList.cartons, function(i, c) {
                        if (!remainingCartonNames.includes(c.carton_name)) {
                            remainingCartonNames.push(c.carton_name);
                        }
                    });

                    let cartonNameMap = {};
                    $.each(remainingCartonNames, function(idx, oldName) {
                        cartonNameMap[oldName] = 'C' + (idx + 1);
                    });

                    $.each(pList.cartons, function(i, c) {
                        if (cartonNameMap[c.carton_name]) {
                            c.carton_name = cartonNameMap[c.carton_name];
                        }
                    });

                    pList.total_cartons = remainingCartonNames.length;

                    if (poData.invoices_by_color && poData.invoices_by_color[colorName]) {
                        poData.invoices_by_color[colorName].total_cartons = remainingCartonNames.length;
                    }

                    renderCartonTableDOM(poIdx, colorName, cIdx);
                    $('#pos_data_payload').val(JSON.stringify(globalPosData));

                    Swal.fire({
                        title: 'Carton Removed!',
                        text: cartonName + ' removed and remaining carton names updated.',
                        icon: 'success',
                        timer: 1500,
                        showConfirmButton: false
                    });
                }
            }
        });
    }

    function deleteMixedCarton(poIdx, colorName, cartonName, cIdx) {
        deleteCarton(poIdx, colorName, cartonName, cIdx);
    }

    function openAddMixedModal(poIdx, colorName, cIdx) {
        $('#modalPoIdx').val(poIdx);
        $('#modalColorName').val(colorName);
        $('#modalCIdx').val(cIdx);

        let poData = globalPosData[poIdx];
        let pList = poData ? poData.packing_lists[colorName] : null;
        if (!pList) return;

        let hasRemaining = false;
        $.each(pList.size_summary, function(szKey, szOrdQty) {
            let szPackQty = pList.size_pack_summary[szKey] || szOrdQty;
            let packedSoFar = 0;
            $.each(pList.cartons, function(k, c) {
                if (c.size === szKey) packedSoFar += c.quantity;
            });
            if ((szPackQty - packedSoFar) > 0) {
                hasRemaining = true;
            }
        });

        let container = $('#modalSizeRowsContainer');
        container.empty();

        if (!hasRemaining) {
            $('#modalNoticeBox').removeClass('alert-info').addClass('alert-warning').text('Notice: No remaining balance quantity available for any size to generate a mixed carton.');
            $('#submitMixedBtn').attr('disabled', 'disabled');
            $('#addSizeRowBtn').attr('disabled', 'disabled');
        } else {
            $('#modalNoticeBox').removeClass('alert-warning').addClass('alert-info').text('Select sizes and enter remaining quantities to pack into this mixed carton, then enter the mixed carton Net Weight.');
            $('#submitMixedBtn').removeAttr('disabled');
            $('#addSizeRowBtn').removeAttr('disabled');
            addModalSizeRow();
        }

        $('#addMixedCartonModal').modal('show');
    }

    function addModalSizeRow() {
        let poIdx = $('#modalPoIdx').val();
        let colorName = $('#modalColorName').val();
        let poData = globalPosData[poIdx];
        let pList = poData ? poData.packing_lists[colorName] : null;
        if (!pList) return;

        let sizeOptionsHtml = '';

        $.each(pList.size_summary, function(szKey, szOrdQty) {
            let szPackQty = pList.size_pack_summary[szKey] || szOrdQty;
            let packedSoFar = 0;
            $.each(pList.cartons, function(k, c) {
                if (c.size === szKey) packedSoFar += c.quantity;
            });
            let remBal = szPackQty - packedSoFar;

            if (remBal > 0) {
                sizeOptionsHtml += `<option value="${szKey}">Size ${szKey} (Remaining Balance: ${remBal} pcs)</option>`;
            } else {
                sizeOptionsHtml += `<option value="${szKey}" disabled>Size ${szKey} (Full - 0 pcs left)</option>`;
            }
        });

        let rowId = 'modalRow_' + Date.now() + '_' + Math.floor(Math.random() * 1000);
        let rowHtml = `<div class="row g-2 mb-2 align-items-center modal-size-row" id="${rowId}">
            <div class="col-md-6">
                <select class="form-select form-select-sm modal-size-select">
                    ${sizeOptionsHtml}
                </select>
            </div>
            <div class="col-md-4">
                <input type="number" class="form-control form-control-sm modal-qty-input" placeholder="Qty">
            </div>
            <div class="col-md-2 text-end">
                <button type="button" class="btn btn-sm btn-outline-danger" onclick="$('#${rowId}').remove()"><i class="fas fa-trash"></i></button>
            </div>
        </div>`;

        $('#modalSizeRowsContainer').append(rowHtml);
    }

    function submitMixedCarton() {
        let poIdx = $('#modalPoIdx').val();
        let colorName = $('#modalColorName').val();
        let cIdx = $('#modalCIdx').val();

        let mixedNetWt = parseFloat($('#modalMixedNetWeight').val()) || 18.50;

        let poData = globalPosData[poIdx];
        let pList = poData ? poData.packing_lists[colorName] : null;
        if (!pList) return;

        let cartonOpt = $('#cartonTypeSelect-' + poIdx + ' option:selected');
        let cartonId = cartonOpt.val();
        let cLen = parseFloat(cartonOpt.data('len')) || 60;
        let cBrd = parseFloat(cartonOpt.data('brd')) || 40;
        let cHgt = parseFloat(cartonOpt.data('hgt')) || 40;
        let cWt = parseFloat(cartonOpt.data('wt')) || 1.2;

        let uniqueCartons = [...new Set(pList.cartons.map(c => c.carton_name))];
        let nextCartonNum = uniqueCartons.length + 1;
        let mixedCartonName = 'C' + nextCartonNum;

        let sizeEntries = [];
        let validationError = false;

        $('#modalSizeRowsContainer .modal-size-row').each(function() {
            let sz = $(this).find('.modal-size-select').val();
            let qty = parseInt($(this).find('.modal-qty-input').val()) || 0;

            if (sz && qty > 0) {
                let szPackQty = pList.size_pack_summary[sz] || pList.size_summary[sz] || 0;
                let packedSoFar = 0;
                $.each(pList.cartons, function(k, c) {
                    if (c.size === sz) packedSoFar += c.quantity;
                });
                let remBal = szPackQty - packedSoFar;

                if (qty > remBal) {
                    Swal.fire('Error', `Entered Qty (${qty}) for Size ${sz} exceeds available remaining balance (${remBal} pcs).`, 'error');
                    validationError = true;
                    return false;
                }

                sizeEntries.push({ size: sz, quantity: qty });
            }
        });

        if (validationError) return;

        if (sizeEntries.length === 0) {
            Swal.fire('Notice', 'Please select at least one size and enter a quantity greater than 0.', 'warning');
            return;
        }

        $.each(sizeEntries, function(k, entry) {
            let matchingPoItem = poData.po_items.find(pi => (pi.size_years || 'OS') === entry.size);

            let grossWeight = parseFloat((mixedNetWt + cWt).toFixed(2));
            let cbm = parseFloat(((cLen * cBrd * cHgt) / 1000000).toFixed(4));

            pList.cartons.push({
                carton_name: mixedCartonName,
                po_no: pList.po_no,
                article_number: matchingPoItem ? matchingPoItem.article_number : '',
                article_description: poData.article_info['Article description'] || '',
                ean_code: matchingPoItem ? (matchingPoItem.ean_code || '') : '',
                color: colorName,
                size: entry.size,
                quantity: entry.quantity,
                net_weight: mixedNetWt,
                gross_weight: grossWeight,
                cbm: cbm,
                carton_length: cLen,
                carton_breadth: cBrd,
                carton_height: cHgt,
                carton_id: cartonId,
                is_mixed: true
            });
        });

        $('#addMixedCartonModal').modal('hide');
        renderCartonTableDOM(poIdx, colorName, cIdx);
        $('#pos_data_payload').val(JSON.stringify(globalPosData));

        Swal.fire({
            title: 'Mixed Carton Created!',
            text: mixedCartonName + ' with ' + sizeEntries.length + ' size(s) added successfully.',
            icon: 'success',
            timer: 1500,
            showConfirmButton: false
        });
    }

    function convertNumberToWords(amount) {
        let number = Math.floor(amount);
        let fraction = Math.round((amount - number) * 100);

        let words = {
            0: '', 1: 'ONE', 2: 'TWO', 3: 'THREE', 4: 'FOUR', 5: 'FIVE', 6: 'SIX', 7: 'SEVEN', 8: 'EIGHT', 9: 'NINE',
            10: 'TEN', 11: 'ELEVEN', 12: 'TWELVE', 13: 'THIRTEEN', 14: 'FOURTEEN', 15: 'FIFTEEN', 16: 'SIXTEEN', 17: 'SEVENTEEN',
            18: 'EIGHTEEN', 19: 'NINETEEN', 20: 'TWENTY', 30: 'THIRTY', 40: 'FORTY', 50: 'FIFTY', 60: 'SIXTY', 70: 'SEVENTY', 80: 'EIGHTY', 90: 'NINETY'
        };

        if (number === 0) return 'ZERO RUPEES';

        let str = [];
        let crore = Math.floor(number / 10000000); number %= 10000000;
        let lakh = Math.floor(number / 100000); number %= 100000;
        let thousand = Math.floor(number / 1000); number %= 1000;
        let hundred = Math.floor(number / 100); number %= 100;

        if (crore) str.push(convertTwoDigit(crore, words) + ' CRORE');
        if (lakh) str.push(convertTwoDigit(lakh, words) + ' LAKH');
        if (thousand) str.push(convertTwoDigit(thousand, words) + ' THOUSAND');
        if (hundred) str.push(convertTwoDigit(hundred, words) + ' HUNDRED');
        if (number) str.push(convertTwoDigit(number, words));

        let res = str.join(' ') + ' RUPEES';
        if (fraction > 0) res += ' AND ' + convertTwoDigit(fraction, words) + ' PAISA';
        else res += ' ONLY';
        return res;
    }

    function convertTwoDigit(num, words) {
        if (num < 20) return words[num];
        let tens = Math.floor(num / 10) * 10;
        let units = num % 10;
        return (words[tens] + ' ' + words[units]).trim();
    }

    $(document).ready(function() {
        $.ajaxSetup({
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            }
        });

        if ($.fn.select2) {
            $('.select2').select2();
        }

        // File selection display
        $('#pdf_files').on('change', function(e) {
            let files = e.target.files;
            let listHtml = '';
            if (files && files.length > 0) {
                listHtml += '<strong>Selected (' + files.length + ' files):</strong> ';
                for (let i = 0; i < files.length; i++) {
                    listHtml += '<span class="file-badge"><i class="fas fa-file-pdf text-danger me-1"></i>' + files[i].name + '</span>';
                }
            }
            $('#selectedFilesList').html(listHtml);
        });

        // Form Submission
        $('#bulkPdfExtractForm').on('submit', function(e) {
            e.preventDefault();

            let filesInput = $('#pdf_files')[0];
            if (!filesInput.files || filesInput.files.length === 0) {
                Swal.fire('Warning', 'Please select at least one PDF file to upload.', 'warning');
                return false;
            }

            let formData = new FormData(this);
            $('#loadingOverlay').fadeIn(200);

            $.ajax({
                url: "{{ route('bulk_pdf_process') }}",
                method: "POST",
                data: formData,
                contentType: false,
                processData: false,
                success: function(response) {
                    $('#loadingOverlay').fadeOut(200);
                    if (response.status && response.html) {
                        $('#bulkResultsContainer').html(response.html).show();
                        let payloadVal = $('#pos_data_payload').val();
                        if (payloadVal) {
                            try {
                                globalPosData = JSON.parse(payloadVal);
                            } catch (e) {
                                console.error('Error parsing PO data payload', e);
                            }
                        }
                        $('html, body').animate({
                            scrollTop: $("#bulkResultsContainer").offset().top - 20
                        }, 500);
                    } else if (response.error) {
                        Swal.fire('Error', response.error, 'error');
                    }
                },
                error: function(xhr) {
                    $('#loadingOverlay').fadeOut(200);
                    let err = xhr.responseJSON ? xhr.responseJSON.error : 'Failed to process PDFs. Please ensure Python server is running on port 8000.';
                    Swal.fire('Extraction Error', err, 'error');
                }
            });
        });

        // Event delegation for dynamically rendered bottom bar check & button
        $(document).on('change', '#verifyBulkCheck', function() {
            if ($(this).is(':checked')) {
                $('#saveBulkAllBtn').removeAttr('disabled');
            } else {
                $('#saveBulkAllBtn').attr('disabled', 'disabled');
            }
        });

        $(document).on('click', '#saveBulkAllBtn', function() {
            let posPayload = $('#pos_data_payload').val();
            let vendorId = $('#bulk_vendor_id_payload').val();

            Swal.fire({
                title: 'Save All POs?',
                text: 'This will save all extracted POs along with their Job Orders, Packing Lists and Invoices into the system.',
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#28a745',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Yes, Save All!'
            }).then((result) => {
                if (result.isConfirmed) {
                    $('#loadingOverlay').fadeIn(200);

                    $.ajax({
                        url: "{{ route('bulk_store') }}",
                        method: "POST",
                        data: {
                            vendor_id: vendorId,
                            pos_data: posPayload
                        },
                        success: function(response) {
                            $('#loadingOverlay').fadeOut(200);
                            if (response.success) {
                                Swal.fire({
                                    title: 'Success!',
                                    text: response.message,
                                    icon: 'success',
                                    confirmButtonText: 'View All POs'
                                }).then(() => {
                                    window.location.href = "{{ route('pdf_extract_all_master') }}";
                                });
                            } else {
                                Swal.fire('Error', response.message || 'Failed to save POs.', 'error');
                            }
                        },
                        error: function(xhr) {
                            $('#loadingOverlay').fadeOut(200);
                            let err = xhr.responseJSON ? xhr.responseJSON.message : 'Error storing POs.';
                            Swal.fire('Error', err, 'error');
                        }
                    });
                }
            });
        });
    });
</script>
@endpush
