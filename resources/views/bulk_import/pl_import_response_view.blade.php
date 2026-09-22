<div class="row g-3 mb-4">
    <!-- Summary Metrics (Clean, without card or card-body) -->
    <div class="col-xl-3 col-lg-6 col-md-6 col-sm-6">
        <div class="p-3 rounded border bg-primary-transparent shadow-sm">
            <div class="d-flex align-items-center">
                <div class="me-3">
                    <span class="avatar avatar-md bg-primary text-white rounded-circle">
                        <i class="fas fa-file-invoice fs-18"></i>
                    </span>
                </div>
                <div class="flex-fill">
                    <span class="text-muted fs-11 fw-semibold d-block text-uppercase">Total POs</span>
                    <h4 class="fw-bold mb-0 text-primary">{{ $summary['total_pos'] }}</h4>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-3 col-lg-6 col-md-6 col-sm-6">
        <div class="p-3 rounded border bg-info-transparent shadow-sm">
            <div class="d-flex align-items-center">
                <div class="me-3">
                    <span class="avatar avatar-md bg-info text-white rounded-circle">
                        <i class="fas fa-list-check fs-18"></i>
                    </span>
                </div>
                <div class="flex-fill">
                    <span class="text-muted fs-11 fw-semibold d-block text-uppercase">Packing Lists (Color)</span>
                    <h4 class="fw-bold mb-0 text-info">{{ $summary['total_pls'] }}</h4>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-3 col-lg-6 col-md-6 col-sm-6">
        <div class="p-3 rounded border bg-secondary-transparent shadow-sm">
            <div class="d-flex align-items-center">
                <div class="me-3">
                    <span class="avatar avatar-md bg-secondary text-white rounded-circle">
                        <i class="fas fa-boxes-stacked fs-18"></i>
                    </span>
                </div>
                <div class="flex-fill">
                    <span class="text-muted fs-11 fw-semibold d-block text-uppercase">Total Cartons</span>
                    <h4 class="fw-bold mb-0 text-secondary">{{ $summary['total_cartons'] }}</h4>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-3 col-lg-6 col-md-6 col-sm-6">
        <div class="p-3 rounded border bg-success-transparent shadow-sm">
            <div class="d-flex align-items-center">
                <div class="me-3">
                    <span class="avatar avatar-md bg-success text-white rounded-circle">
                        <i class="fas fa-shirt fs-18"></i>
                    </span>
                </div>
                <div class="flex-fill">
                    <span class="text-muted fs-11 fw-semibold d-block text-uppercase">Total Pcs / Weight</span>
                    <h4 class="fw-bold mb-0 text-success">{{ number_format($summary['total_qty']) }} pcs <span class="fs-12 text-muted">({{ $summary['total_weight'] }} kg)</span></h4>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- POs Accordion Container (Clean without card-header / card-body) -->
<div class="accordion custom-accordionwithplus" id="poAccordion">
    @foreach($pos as $pIdx => $po)
    <div class="accordion-item mb-3 border shadow-sm" style="border-radius: 8px; overflow: hidden;">
        <h2 class="accordion-header" id="heading_po_{{ $pIdx }}">
            <button class="accordion-button {{ $pIdx === 0 ? '' : 'collapsed' }} fw-bold bg-light text-dark py-3" type="button" data-bs-toggle="collapse" data-bs-target="#collapse_po_{{ $pIdx }}" aria-expanded="{{ $pIdx === 0 ? 'true' : 'false' }}">
                <div class="d-flex align-items-center justify-content-between w-100 me-3 flex-wrap gap-2">
                    <div class="d-flex align-items-center gap-3 flex-wrap">
                        <span class="badge bg-primary fs-13 px-3 py-2">PO #{{ $po['po_num'] }}</span>
                        <span class="text-secondary fw-semibold fs-13"><i class="fas fa-hashtag me-1"></i>Job: <strong>{{ $po['job_no'] }}</strong></span>
                        <span class="text-muted fs-13"><i class="fas fa-tag me-1"></i>{{ $po['style_description'] }}</span>
                    </div>
                    <div class="d-flex align-items-center gap-2">
                        @if(!$po['po_exists'])
                            <span class="badge bg-danger-transparent text-danger border border-danger px-2 py-1 fs-11" title="Please upload this PO first via Bulk PO Import">
                                <i class="fas fa-triangle-exclamation me-1"></i>PO Not Uploaded (Skipped)
                            </span>
                        @elseif($po['is_amended'])
                            <span class="badge bg-warning-transparent text-dark border border-warning px-2 py-1 fs-11" title="PO has already been amended">
                                <i class="fas fa-ban me-1 text-warning"></i>Already Amended (Skipped)
                            </span>
                        @elseif($po['has_existing_packing_list'])
                            <span class="badge bg-warning-transparent text-dark border border-warning px-2 py-1 fs-11" title="Packing list already added for this PO">
                                <i class="fas fa-lock me-1 text-warning"></i>PL Already Added (Skipped)
                            </span>
                        @else
                            <span class="badge bg-success-transparent text-success border border-success px-2 py-1 fs-11">
                                <i class="fas fa-check-circle me-1"></i>Ready to Amend & Import
                            </span>
                        @endif
                        <span class="badge bg-info text-white fs-11">{{ count($po['colors']) }} Color Packing List(s)</span>
                    </div>
                </div>
            </button>
        </h2>
        <div id="collapse_po_{{ $pIdx }}" class="accordion-collapse collapse {{ $pIdx === 0 ? 'show' : '' }}" data-bs-parent="#poAccordion">
            <div class="p-4 bg-white">
                @if(!$po['po_exists'])
                <div class="alert alert-danger d-flex align-items-center mb-4" role="alert">
                    <i class="fas fa-exclamation-circle fs-20 me-3"></i>
                    <div>
                        <strong>PO #{{ $po['po_num'] }} has not been uploaded yet.</strong> This packing list will <u>NOT</u> be saved into the database until the PO is first uploaded via <a href="{{ route('bulk_po_import') }}" class="alert-link text-decoration-underline" target="_blank">Bulk PO Import</a>.
                    </div>
                </div>
                @elseif($po['has_existing_packing_list'])
                <div class="alert alert-warning d-flex align-items-center mb-4" role="alert">
                    <i class="fas fa-lock fs-20 me-3 text-warning"></i>
                    <div>
                        <strong>Packing list already exists for PO #{{ $po['po_num'] }}.</strong> A packing list has already been created for this PO ({{ implode(', ', $po['existing_packing_lists'] ?? []) }}). This PO cannot be uploaded again and will be skipped.
                    </div>
                </div>
                @endif

                <!-- Sub-tabs for Packing Lists under this PO -->
                <ul class="nav nav-pills pl-main-tab mb-3" id="po_{{ $pIdx }}_pl_tabs" role="tablist">
                    @php $cCounter = 0; @endphp
                    @foreach($po['packing_lists'] as $plKey => $plInfo)
                    <li class="nav-item" role="presentation">
                        <button class="nav-link {{ $cCounter === 0 ? 'active' : '' }}" 
                                id="tab_po_{{ $pIdx }}_{{ md5($plKey) }}" 
                                data-bs-toggle="pill" 
                                data-bs-target="#content_po_{{ $pIdx }}_{{ md5($plKey) }}" 
                                type="button" 
                                role="tab">
                            <i class="fas fa-boxes-packing me-1"></i> PL #{{ $plInfo['pl_no'] }}@if(!empty($plInfo['location'])) [{{ $plInfo['location'] }}]@endif ({{ $plInfo['color'] }})
                            <span class="badge bg-white text-dark ms-2">{{ $plInfo['total_cartons'] }} Ctn ({{ $plInfo['total_qty'] }} pcs)</span>
                        </button>
                    </li>
                    @php $cCounter++; @endphp
                    @endforeach
                </ul>

                <div class="tab-content" id="po_{{ $pIdx }}_tabContent">
                    @php $cCounter = 0; @endphp
                    @foreach($po['packing_lists'] as $plKey => $colorInfo)
                    <div class="tab-pane fade {{ $cCounter === 0 ? 'show active' : '' }}" 
                         id="content_po_{{ $pIdx }}_{{ md5($plKey) }}" 
                         role="tabpanel">

                        <!-- Packing List Info Bar -->
                        <div class="p-3 mb-3 bg-light rounded border d-flex flex-wrap align-items-center justify-content-between gap-3">
                            <div class="d-flex align-items-center gap-4 flex-wrap">
                                <div>
                                    <span class="text-muted fs-11 text-uppercase d-block">PL Ref No</span>
                                    <strong class="text-primary fs-14"><i class="fas fa-barcode me-1"></i>{{ $colorInfo['pack_ref_no'] }}</strong>
                                </div>
                                @if(!empty($colorInfo['location']))
                                <div>
                                    <span class="text-muted fs-11 text-uppercase d-block">Location</span>
                                    <span class="badge bg-primary-transparent text-primary fs-12 px-2 py-1 border border-primary"><i class="fas fa-location-dot me-1"></i>{{ $colorInfo['location'] }}</span>
                                </div>
                                @endif
                                <div>
                                    <span class="text-muted fs-11 text-uppercase d-block">Color</span>
                                    <strong class="text-dark fs-14">{{ $colorInfo['color'] }}</strong>
                                </div>
                                <div>
                                    <span class="text-muted fs-11 text-uppercase d-block">Packing Table</span>
                                    <span class="badge bg-dark fs-12 px-2 py-1">Table {{ $colorInfo['packing_table_no'] }}</span>
                                </div>
                                <div>
                                    <span class="text-muted fs-11 text-uppercase d-block">Carton Dimension</span>
                                    <span class="badge bg-secondary-transparent text-secondary fs-12 border border-secondary">{{ $colorInfo['carton_dimension'] }}</span>
                                </div>
                                <div>
                                    <span class="text-muted fs-11 text-uppercase d-block">Total Cartons</span>
                                    <strong class="text-dark fs-14">{{ $colorInfo['total_cartons'] }} Cartons</strong>
                                </div>
                                <div>
                                    <span class="text-muted fs-11 text-uppercase d-block">Total Pcs</span>
                                    <strong class="text-success fs-14">{{ number_format($colorInfo['total_qty']) }} pcs</strong>
                                </div>
                                <div>
                                    <span class="text-muted fs-11 text-uppercase d-block">Total Net Weight</span>
                                    <strong class="text-dark fs-14">{{ $colorInfo['total_net_weight'] }} kg</strong>
                                </div>
                            </div>
                            <div>
                                @if($colorInfo['existing_pl'])
                                    <span class="badge bg-danger-transparent text-danger border border-danger px-3 py-2">
                                        <i class="fas fa-lock me-1"></i> Already Exists: <strong>{{ $colorInfo['existing_pl']['pack_ref_no'] }}</strong> (Cannot upload again)
                                    </span>
                                @else
                                    <span class="badge bg-success-transparent text-success border border-success px-3 py-2">
                                        <i class="fas fa-check-circle me-1"></i> Ref: <strong>{{ $colorInfo['pack_ref_no'] }}</strong>
                                    </span>
                                @endif
                            </div>
                        </div>

                        <!-- Size Breakdown Matrix (Clean table without card wrappers) -->
                        <div class="mb-4">
                            <div class="d-flex align-items-center justify-content-between mb-2">
                                <h6 class="fs-13 mb-0 text-dark fw-bold">
                                    <i class="fas fa-table-cells me-1 text-primary"></i> Size Breakdown & Verification Matrix
                                </h6>
                            </div>
                            <div class="table-responsive rounded border">
                                <table class="table table-bordered table-sm text-center mb-0 align-middle">
                                    <thead class="table-light">
                                        <tr>
                                            <th class="text-start ps-3" style="width: 140px;">Metric</th>
                                            @foreach($colorInfo['size_totals'] as $sName => $sQty)
                                            <th class="fw-bold">{{ $sName }}</th>
                                            @endforeach
                                            <th class="table-primary fw-bold">Total</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <td class="text-start ps-3 fw-semibold text-muted">Excel Pack Qty</td>
                                            @foreach($colorInfo['size_totals'] as $sName => $sQty)
                                            <td class="fw-bold text-primary">{{ $sQty }}</td>
                                            @endforeach
                                            <td class="table-primary fw-bold text-primary">{{ $colorInfo['total_qty'] }}</td>
                                        </tr>
                                        @if($po['po_exists'])
                                        <tr>
                                            <td class="text-start ps-3 fw-semibold text-muted">PO Item Qty</td>
                                            @php $poTot = 0; @endphp
                                            @foreach($colorInfo['size_totals'] as $sName => $sQty)
                                                @php 
                                                    $valInfo = $colorInfo['size_validation'][$sName] ?? null; 
                                                    $pQty = $valInfo ? $valInfo['po_qty'] : null;
                                                    if ($pQty !== null) $poTot += $pQty;
                                                @endphp
                                                <td class="text-dark">{{ $pQty !== null ? $pQty : '-' }}</td>
                                            @endforeach
                                            <td class="table-primary fw-bold text-dark">{{ $poTot > 0 ? $poTot : '-' }}</td>
                                        </tr>
                                        @endif
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <!-- Carton-by-Carton Details (Clean table without card wrappers) -->
                        <div>
                            <div class="d-flex align-items-center justify-content-between mb-2">
                                <h6 class="fs-13 mb-0 text-dark fw-bold">
                                    <i class="fas fa-box-open me-1 text-primary"></i> Carton-by-Carton Details ({{ count($colorInfo['cartons']) }} Cartons)
                                </h6>
                                <span class="badge bg-dark-transparent text-dark fs-11">Carton Format: {{ in_array($vendor_id, [1, 5, 6]) ? 'C1, C2, ...' : '1, 2, ...' }}</span>
                            </div>
                            <div class="table-responsive rounded border" style="max-height: 380px; overflow-y: auto;">
                                <table class="table table-hover table-striped table-bordered table-sm text-center mb-0 align-middle">
                                    <thead class="table-light sticky-top">
                                        <tr>
                                            <th style="width: 80px;">Carton #</th>
                                            <th>Color</th>
                                            <th class="text-start ps-3">Size & Quantity Breakdown</th>
                                            <th style="width: 100px;">Pcs / Ctn</th>
                                            <th style="width: 130px;">Dimension</th>
                                            <th style="width: 120px;">Net Wt (Kg)</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($colorInfo['cartons'] as $carton)
                                        <tr>
                                            <td>
                                                <span class="badge bg-primary text-white fw-bold px-2 py-1 fs-12">{{ $carton['carton_name'] }}</span>
                                            </td>
                                            <td>{{ $colorInfo['color'] }}</td>
                                            <td class="text-start ps-3">
                                                @foreach($carton['sizes'] as $sName => $sQty)
                                                    @if($sQty > 0)
                                                    <span class="badge bg-light text-dark border me-1 px-2 py-1">
                                                        <strong>{{ $sName }}:</strong> <span class="text-primary fw-bold">{{ $sQty }}</span> pcs
                                                    </span>
                                                    @endif
                                                @endforeach
                                            </td>
                                            <td class="fw-bold text-success">{{ $carton['total_qty'] }}</td>
                                            <td><span class="badge bg-secondary-transparent text-secondary">{{ $carton['dimension'] }}</span></td>
                                            <td class="fw-bold">{{ $carton['net_weight'] }} kg</td>
                                        </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>

                    </div>
                    @php $cCounter++; @endphp
                    @endforeach
                </div>
            </div>
        </div>
    </div>
    @endforeach
</div>

<!-- Sticky Bottom Save Bar -->
<div class="sticky-save-bar d-flex justify-content-between align-items-center mt-4">
    <button type="button" class="btn btn-outline-danger px-4 py-2" id="cancel_pl_import_btn">
        <i class="fas fa-trash me-2"></i> Discard Preview
    </button>
    
    <div class="d-flex align-items-center gap-3">
        <span class="text-muted fs-13 d-none d-md-inline">
            Ready to generate <strong>{{ $summary['total_pls'] }}</strong> Packing Lists across <strong>{{ $summary['total_pos'] }}</strong> PO(s)
        </span>
        <button type="button" class="btn btn-primary px-5 py-2 fw-bold text-white shadow" id="save_all_pls_btn" style="background-color: #1e3a8a;">
            <i class="fas fa-save me-2"></i> Save & Generate Packing Lists
        </button>
    </div>
</div>
