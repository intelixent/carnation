<style>
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

<div class="row">
    <div class="col-12 mb-3">
        <h4 class="fw-bold text-dark mb-1">
            <i class="fas fa-layer-group text-primary me-2"></i>Extracted Purchase Orders ({{ count($pos) }})
        </h4>
        <p class="text-muted small">Standalone Cartons ordered strictly by Size Chart Master sequence. Add Mixed Cartons manually for remaining balance quantities or remove mixed cartons individually.</p>
    </div>

    <!-- PO Outer Main Tabs -->
    <ul class="nav nav-tabs po-main-tab mb-3" id="bulkPoMainTabs" role="tablist">
        @foreach($pos as $index => $po)
        <li class="nav-item" role="presentation">
            <button class="nav-link {{ $index === 0 ? 'active' : '' }}" 
                    id="po-tab-header-{{ $index }}" 
                    data-bs-toggle="tab" 
                    data-bs-target="#po-tab-content-{{ $index }}" 
                    type="button" role="tab">
                <i class="fas fa-file-pdf me-1"></i> PO: {{ $po['po_details']['PO Number'] ?? ('#' . ($index + 1)) }}
                @if(!empty($po['existing_po_status']['exists']))
                    @if($po['existing_po_status']['blocked'])
                        <span class="badge bg-danger ms-1" style="font-size: 9px;">PL/INV IN PROGRESS</span>
                    @else
                        <span class="badge bg-warning text-dark ms-1" style="font-size: 9px;">PO UPLOADED</span>
                    @endif
                @endif
            </button>
        </li>
        @endforeach
    </ul>

    <!-- PO Outer Tab Contents -->
    <div class="tab-content" id="bulkPoMainTabsContent">
        @foreach($pos as $index => $po)
        <div class="tab-pane fade {{ $index === 0 ? 'show active' : '' }}" 
             id="po-tab-content-{{ $index }}" 
             role="tabpanel">

            <!-- Sub Tabs inside each PO -->
            <div class="preview-card p-4 mb-4">

                <!-- Existing PO Alert Banner -->
                @if(!empty($po['existing_po_status']['exists']))
                    @if($po['existing_po_status']['blocked'])
                        <div class="alert alert-danger fw-bold shadow-sm mb-3">
                            <i class="fas fa-exclamation-triangle me-2"></i> {{ $po['existing_po_status']['message'] }}
                        </div>
                    @else
                        <div class="alert alert-warning fw-bold shadow-sm mb-3">
                            <i class="fas fa-info-circle me-2"></i> {{ $po['existing_po_status']['message'] }}
                        </div>
                    @endif
                @endif

                <!-- Summary Header -->
                <div class="p-3 mb-4 rounded bg-light border">
                    <div class="row align-items-center">
                        <div class="col-md-4">
                            <span class="badge bg-primary me-1">Vendor Excess: {{ $po['vendor_excess'] }}%</span>
                            <span class="badge bg-secondary me-1">Shortage: {{ $po['vendor_shortage'] }}%</span>
                            <span class="badge bg-info text-dark">Discount: {{ $po['vendor_discount'] }}%</span>
                        </div>
                        <div class="col-md-4 text-center">
                            <small class="text-muted"><strong>Job Order No:</strong> <span class="fw-bold text-primary" id="displayJobNoHeader-{{ $index }}">{{ !empty($po['job_no']) ? $po['job_no'] : 'N/A' }}</span> | <strong>Status:</strong> <span class="text-success fw-bold">Amended & Configured</span></small>
                        </div>
                        <div class="col-md-4 text-md-end">
                            <small class="text-dark fw-bold"><i class="fas fa-boxes text-warning me-1"></i> Standalone Cartons (Ordered by Size Chart)</small>
                        </div>
                    </div>
                </div>

                <ul class="nav nav-pills po-sub-tab mb-4" id="subTabs-{{ $index }}" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" 
                                id="sub-extracted-tab-{{ $index }}" 
                                data-bs-toggle="pill" 
                                data-bs-target="#sub-extracted-content-{{ $index }}" 
                                type="button" role="tab">
                            <i class="fas fa-list-alt me-1 text-primary"></i> 1. Extracted PO Data
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" 
                                id="sub-packing-tab-{{ $index }}" 
                                data-bs-toggle="pill" 
                                data-bs-target="#sub-packing-content-{{ $index }}" 
                                type="button" role="tab">
                            <i class="fas fa-boxes me-1 text-warning"></i> 2. Packing List
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" 
                                id="sub-invoice-tab-{{ $index }}" 
                                data-bs-toggle="pill" 
                                data-bs-target="#sub-invoice-content-{{ $index }}" 
                                type="button" role="tab">
                            <i class="fas fa-file-invoice-dollar me-1 text-success"></i> 3. Invoices
                        </button>
                    </li>
                </ul>

                <div class="tab-content" id="subTabsContent-{{ $index }}">

                    <!-- SUB-TAB 1: EXTRACTED PO DATA -->
                    <div class="tab-pane fade show active" id="sub-extracted-content-{{ $index }}" role="tabpanel">
                        <div class="accordion" id="accordionExtracted-{{ $index }}">
                            <!-- PO Header Details -->
                            <div class="accordion-item mb-2 border">
                                <h2 class="accordion-header" id="headingDetails-{{ $index }}">
                                    <button class="accordion-button fw-bold text-dark" type="button" data-bs-toggle="collapse" data-bs-target="#collapseDetails-{{ $index }}">
                                        PO Information & Addresses
                                    </button>
                                </h2>
                                <div id="collapseDetails-{{ $index }}" class="accordion-collapse collapse show" data-bs-parent="#accordionExtracted-{{ $index }}">
                                    <div class="accordion-body">
                                        <div class="row">
                                            <div class="col-md-6">
                                                <h6 class="fw-bold text-primary">General Info</h6>
                                                <ul class="list-group list-group-flush mb-3">
                                                    <li class="list-group-item bg-light border-start border-4 border-primary py-2 my-1">
                                                        <div class="d-flex align-items-center justify-content-between">
                                                            <strong class="text-primary" style="font-size: 0.9rem;"><i class="fas fa-hashtag me-1"></i> Job Order Number:</strong>
                                                            <input type="text" class="form-control form-control-sm fw-bold border-primary text-center" 
                                                                   style="width: 140px; font-size: 0.9rem;" 
                                                                   id="inputJobNo-{{ $index }}" 
                                                                   value="{{ $po['job_no'] ?? '' }}" 
                                                                   placeholder="e.g. 3337"
                                                                   onchange="updateJobNo({{ $index }})">
                                                        </div>
                                                    </li>
                                                    <li class="list-group-item"><strong>PO Number:</strong> {{ $po['po_details']['PO Number'] ?? 'N/A' }}</li>
                                                    <li class="list-group-item"><strong>PO Date:</strong> {{ $po['po_details']['PO Date'] ?? 'N/A' }}</li>
                                                    <li class="list-group-item"><strong>Goods Ready Date:</strong> {{ $po['po_details']['Goods Ready Date'] ?? 'N/A' }}</li>
                                                    <li class="list-group-item"><strong>MRP:</strong> {{ $po['po_details']['MRP'] ?? 'N/A' }}</li>
                                                    <li class="list-group-item"><strong>VCP:</strong> {{ $po['po_details']['VCP'] ?? 'N/A' }}</li>
                                                    <li class="list-group-item"><strong>Colors:</strong> {{ $po['po_details']['Colors'] ?? 'N/A' }}</li>
                                                    <li class="list-group-item"><strong>GSTIN:</strong> {{ $po['po_details']['GSTIN'] ?? 'N/A' }}</li>
                                                    <li class="list-group-item"><strong>CIN:</strong> {{ $po['po_details']['CIN'] ?? 'N/A' }}</li>
                                                </ul>
                                            </div>
                                            <div class="col-md-6">
                                                <h6 class="fw-bold text-primary">Addresses</h6>
                                                <ul class="list-group list-group-flush mb-3">
                                                    <li class="list-group-item"><strong>Delivery Address:</strong><br>{{ $po['po_details']['Delivery Address'] ?? 'N/A' }}</li>
                                                    <li class="list-group-item"><strong>Communication Address:</strong><br>{{ $po['po_details']['Communication Address'] ?? 'N/A' }}</li>
                                                </ul>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Article Info -->
                            <div class="accordion-item mb-2 border">
                                <h2 class="accordion-header" id="headingArticle-{{ $index }}">
                                    <button class="accordion-button collapsed fw-bold text-dark" type="button" data-bs-toggle="collapse" data-bs-target="#collapseArticle-{{ $index }}">
                                        Article Information
                                    </button>
                                </h2>
                                <div id="collapseArticle-{{ $index }}" class="accordion-collapse collapse" data-bs-parent="#accordionExtracted-{{ $index }}">
                                    <div class="accordion-body">
                                        <table class="table table-bordered table-sm mb-0">
                                            <tbody>
                                                <tr>
                                                    <td><strong>ARTICLE:</strong> {{ $po['article_info']['ARTICLE'] ?? '' }}</td>
                                                    <td colspan="4"><strong>Article Description:</strong> {{ $po['article_info']['Article description'] ?? '' }}</td>
                                                </tr>
                                                <tr>
                                                    <td><strong>Customs Code:</strong> {{ $po['article_info']['Customs code'] ?? '' }}</td>
                                                    <td><strong>Fabric Composition:</strong> {{ $po['article_info']['Fabric composition'] ?? '' }}</td>
                                                    <td><strong>Construction Type:</strong> {{ $po['article_info']['Construction type'] ?? '' }}</td>
                                                    <td><strong>Gender:</strong> {{ $po['article_info']['Gender'] ?? '' }}</td>
                                                    <td><strong>Article Group:</strong> {{ $po['article_info']['Article group'] ?? '' }}</td>
                                                </tr>
                                                <tr>
                                                    <td><strong>Price per unit:</strong> {{ $po['article_info']['Price per unit'] ?? '' }}</td>
                                                    <td><strong>Total unit:</strong> {{ $po['article_info']['Total unit'] ?? '' }}</td>
                                                    <td><strong>Net Value:</strong> {{ $po['article_info']['Net Value'] ?? '' }}</td>
                                                    <td><strong>Currency:</strong> {{ $po['article_info']['Currency'] ?? '' }}</td>
                                                    <td><strong>Country of Origin:</strong> {{ $po['article_info']['Country of origin'] ?? '' }}</td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>

                            <!-- PO Items Table -->
                            <div class="accordion-item border">
                                <h2 class="accordion-header" id="headingItems-{{ $index }}">
                                    <button class="accordion-button collapsed fw-bold text-dark" type="button" data-bs-toggle="collapse" data-bs-target="#collapseItems-{{ $index }}">
                                        PO Items (Line Items)
                                    </button>
                                </h2>
                                <div id="collapseItems-{{ $index }}" class="accordion-collapse collapse" data-bs-parent="#accordionExtracted-{{ $index }}">
                                    <div class="accordion-body p-0">
                                        <div class="table-responsive">
                                            <table class="table table-striped table-bordered mb-0">
                                                <thead class="table-dark">
                                                    <tr>
                                                        @if(!empty($po['po_items']) && isset($po['po_items'][0]))
                                                            @foreach(array_keys($po['po_items'][0]) as $header)
                                                            <th>{{ ucwords(str_replace('_', ' ', $header)) }}</th>
                                                            @endforeach
                                                        @endif
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    @foreach($po['po_items'] as $pItem)
                                                    <tr>
                                                        @foreach($pItem as $val)
                                                        <td>{{ $val }}</td>
                                                        @endforeach
                                                    </tr>
                                                    @endforeach
                                                </tbody>
                                                <tfoot class="table-light fw-bold">
                                                    <tr>
                                                        <td colspan="7">Total Value (INR {{ number_format($po['per_unit_price'], 2) }} / EA)</td>
                                                        <td colspan="2">INR {{ number_format($po['total_value'], 2) }}</td>
                                                    </tr>
                                                    <tr>
                                                        <td colspan="7">Total IGST (5%)</td>
                                                        <td colspan="2">INR {{ number_format($po['tax_amount'], 2) }}</td>
                                                    </tr>
                                                    <tr>
                                                        <td colspan="7">Total Value Inc Tax</td>
                                                        <td colspan="2" class="text-success">INR {{ number_format($po['final_total'], 2) }}</td>
                                                    </tr>
                                                    <tr>
                                                        <td colspan="7">Total Quantity</td>
                                                        <td colspan="2">{{ number_format($po['total_qty']) }} Pcs</td>
                                                    </tr>
                                                </tfoot>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- SUB-TAB 2: PACKING LIST (STANDALONE CARTONS + MANUAL MIXED CARTONS ADD & REMOVE) -->
                    <div class="tab-pane fade" id="sub-packing-content-{{ $index }}" role="tabpanel">
                        @if(empty($po['packing_lists']))
                            <div class="alert alert-warning">No packing list generated for this PO.</div>
                        @else

                            <!-- Standalone Carton Configuration Rules -->
                            <div class="card mb-4 border-primary shadow-sm">
                                <div class="card-header bg-primary text-white fw-bold d-flex justify-content-between align-items-center">
                                    <span><i class="fas fa-boxes me-2"></i> Standalone Carton Rules (Per Size Pieces Setup)</span>
                                    <button type="button" class="btn btn-sm btn-light fw-bold" onclick="recalculateCartons({{ $index }})">
                                        <i class="fas fa-sync-alt me-1 text-primary"></i> Re-generate Standalone Cartons
                                    </button>
                                </div>
                                <div class="card-body bg-light">
                                    <div class="row g-3 align-items-center">
                                        <!-- Select Carton Type -->
                                        <div class="col-md-5">
                                            <label class="form-label fw-bold text-dark mb-1">Select Carton Type / Dimensions:</label>
                                            <select class="form-select form-select-sm" id="cartonTypeSelect-{{ $index }}" onchange="recalculateCartons({{ $index }})">
                                                @foreach($allCartons as $cOption)
                                                    <option value="{{ $cOption->id ?? 0 }}" 
                                                            data-len="{{ $cOption->length }}" 
                                                            data-brd="{{ $cOption->breadth }}" 
                                                            data-hgt="{{ $cOption->height }}" 
                                                            data-wt="{{ $cOption->weight }}">
                                                        Carton {{ $cOption->length }}x{{ $cOption->breadth }}x{{ $cOption->height }} cm (Tare Wt: {{ $cOption->weight }} kg)
                                                    </option>
                                                @endforeach
                                            </select>
                                        </div>

                                        <!-- Fixed Excess Info -->
                                        <div class="col-md-3">
                                            <label class="form-label fw-bold text-dark mb-1">Vendor Excess % (Fixed):</label>
                                            <div class="form-control form-control-sm bg-white fw-bold text-primary">
                                                {{ $po['vendor_excess'] }}% (Max Cap)
                                            </div>
                                        </div>

                                        <!-- Per Carton Pieces & Net Weight for Each Size (Compact 3 Columns Grid) -->
                                        <div class="col-12 border-top pt-2 mt-1">
                                            <label class="form-label fw-bold text-dark mb-2" style="font-size: 0.85rem;">Per Size Standalone Carton Rules (Pieces & Net Weight Setup):</label>
                                            <div class="row g-2" id="sizeCapsContainer-{{ $index }}">
                                                @foreach($po['packing_lists'] as $colorName => $pList)
                                                    @foreach($pList['size_summary'] as $szKey => $szOrdQty)
                                                        @php 
                                                            $cleanSzKey = strtoupper(preg_replace('/\s+/', '', $szKey));
                                                            $defaultCtnPcs = in_array($cleanSzKey, ['9/10Y', '11/12Y', '13/14Y', 'XL', 'XXL', 'XXXL']) ? 50 : 60;
                                                            $calcPackQty = floor($szOrdQty * (1 + ($po['vendor_excess'] / 100)));
                                                            $initPcs = ($calcPackQty <= $defaultCtnPcs) ? $calcPackQty : $defaultCtnPcs;
                                                            $defaultNetWt = number_format($initPcs * 0.25, 2, '.', '');
                                                        @endphp
                                                        <div class="col-md-4 col-sm-6">
                                                            <div class="d-flex align-items-center justify-content-between bg-white border rounded px-2 py-1 shadow-sm">
                                                                <span class="fw-bold me-1 text-primary" style="font-size: 0.85rem;">{{ $szKey }}:</span>
                                                                <div class="d-flex align-items-center">
                                                                    <input type="number" class="form-control form-control-sm per-size-cap-input me-1 px-1 text-center fw-bold" 
                                                                           style="width: 50px; font-size: 0.8rem;" 
                                                                           data-po-idx="{{ $index }}"
                                                                           data-size="{{ $szKey }}" 
                                                                           id="perSizeCap-{{ $index }}-{{ Str::slug($szKey) }}"
                                                                           value="{{ $defaultCtnPcs }}" 
                                                                           title="Pieces Per Carton"
                                                                           onchange="recalculateCartons({{ $index }})">
                                                                    <span class="text-muted me-2" style="font-size: 0.75rem;">pcs</span>

                                                                    <span class="fw-bold text-dark me-1" style="font-size: 0.75rem;">Net:</span>
                                                                    <input type="number" step="0.01" class="form-control form-control-sm per-size-netwt-input me-1 px-1 text-center fw-bold border-info" 
                                                                           style="width: 60px; font-size: 0.8rem;" 
                                                                           data-po-idx="{{ $index }}"
                                                                           data-size="{{ $szKey }}" 
                                                                           id="perSizeNetWt-{{ $index }}-{{ Str::slug($szKey) }}"
                                                                           value="{{ $defaultNetWt }}" 
                                                                           title="Net Weight (kg)"
                                                                           onchange="recalculateCartons({{ $index }})">
                                                                    <span class="text-muted" style="font-size: 0.75rem;">kg</span>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    @endforeach
                                                    @break
                                                @endforeach
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Color Sub-Tabs inside Packing List -->
                            <ul class="nav nav-pills mb-3" id="colorTabs-{{ $index }}" role="tablist">
                                @php $cIdx = 0; @endphp
                                @foreach($po['packing_lists'] as $colorName => $pList)
                                <li class="nav-item" role="presentation">
                                    <button class="nav-link btn-sm {{ $cIdx === 0 ? 'active' : '' }}" 
                                            id="color-tab-header-{{ $index }}-{{ $cIdx }}" 
                                            data-bs-toggle="pill" 
                                            data-bs-target="#color-tab-content-{{ $index }}-{{ $cIdx }}" 
                                            type="button" role="tab">
                                        <i class="fas fa-palette me-1"></i> Color: {{ $colorName }}
                                    </button>
                                </li>
                                @php $cIdx++; @endphp
                                @endforeach
                            </ul>

                            <div class="tab-content" id="colorTabsContent-{{ $index }}">
                                @php $cIdx = 0; @endphp
                                @foreach($po['packing_lists'] as $colorName => $pList)
                                <div class="tab-pane fade {{ $cIdx === 0 ? 'show active' : '' }}" 
                                     id="color-tab-content-{{ $index }}-{{ $cIdx }}" 
                                     role="tabpanel">

                                    <div class="p-3 border rounded bg-white">
                                        <div class="d-flex justify-content-between align-items-center mb-3">
                                            <div>
                                                <h4 class="fw-bold text-dark mb-1">PACKING LIST PREVIEW</h4>
                                                <span class="badge bg-secondary me-2">Ref No: <span id="packRefNoDisplay-{{ $index }}-{{ $cIdx }}">{{ $pList['pack_ref_no'] }}</span></span>
                                                <span class="badge bg-primary" id="totalCartonsBadge-{{ $index }}-{{ $cIdx }}">Total Cartons: {{ $pList['total_cartons'] }}</span>
                                            </div>
                                            <button type="button" class="btn btn-warning btn-sm fw-bold shadow-sm" onclick="openAddMixedModal({{ $index }}, '{{ $colorName }}', {{ $cIdx }})">
                                                <i class="fas fa-plus-circle me-1"></i> + Add Mixed Carton
                                            </button>
                                        </div>

                                        <table class="table table-bordered table-sm mb-3">
                                            <tr>
                                                <th width="20%" class="bg-light">Invoice No.</th>
                                                <td>{{ $po['invoices_by_color'][$colorName]['ref_no'] ?? 'N/A' }}</td>
                                                <th width="20%" class="bg-light">Date:</th>
                                                <td>{{ $pList['po_date'] }}</td>
                                            </tr>
                                            <tr>
                                                <th class="bg-light">Shipped / Exported By:</th>
                                                <td colspan="3">CARNATION CREATIONS PVT LTD 376,Narasimha Naicken Palayam, Coimbatore 641031</td>
                                            </tr>
                                            <tr>
                                                <th class="bg-light">Bill To Address:</th>
                                                <td colspan="3">{{ $po['invoices_by_color'][$colorName]['buyer']['address'] ?? '' }}</td>
                                            </tr>
                                            <tr>
                                                <th class="bg-light">Ship To Address:</th>
                                                <td colspan="3">{{ $po['invoices_by_color'][$colorName]['consignee']['address'] ?? '' }}</td>
                                            </tr>
                                            <tr>
                                                <th class="bg-light">Final Destination:</th>
                                                <td>{{ $po['invoices_by_color'][$colorName]['consignee']['place_of_supply'] ?? 'BHIWANDI' }}</td>
                                                <th class="bg-light">Color:</th>
                                                <td><span class="badge bg-info text-dark">{{ $colorName }}</span></td>
                                            </tr>
                                            <tr>
                                                <th class="bg-light">Item Description:</th>
                                                <td>{{ $po['article_info']['Article description'] ?? '' }}</td>
                                                <th class="bg-light">PO No:</th>
                                                <td>{{ $pList['po_no'] }}</td>
                                            </tr>
                                        </table>

                                        <h6 class="fw-bold text-primary mt-4 mb-2"><i class="fas fa-boxes me-1"></i> Carton Breakdown List (Ordered by Size Chart Master)</h6>
                                        <div class="table-responsive">
                                            <table class="packing-items-table mb-3" id="cartonTable-{{ $index }}-{{ $cIdx }}">
                                                <thead>
                                                    <tr>
                                                        <th>Ctn #</th>
                                                        <th>PO No</th>
                                                        <th>SAP Article No</th>
                                                        <th>Short Desc.</th>
                                                        <th>EAN / SKU</th>
                                                        <th>Size</th>
                                                        <th>Shipped Units</th>
                                                        <th>L</th>
                                                        <th>B</th>
                                                        <th>H</th>
                                                        <th>Net Weight (kg)</th>
                                                        <th>Gross Weight (kg)</th>
                                                        <th>CBM</th>
                                                        <th style="width: 50px;">Action</th>
                                                    </tr>
                                                </thead>
                                                <tbody id="cartonTbody-{{ $index }}-{{ $cIdx }}">
                                                    @php 
                                                        $totalCartonQty = 0;
                                                        $totalNetWt = 0;
                                                        $totalGrossWt = 0;
                                                        $totalCbm = 0;
                                                    @endphp
                                                    @foreach($pList['cartons'] as $crIdx => $cRow)
                                                    @php 
                                                        $totalCartonQty += $cRow['quantity'];
                                                        $totalNetWt += $cRow['net_weight'];
                                                        $totalGrossWt += $cRow['gross_weight'];
                                                        $totalCbm += $cRow['cbm'];
                                                        $isMixed = $cRow['is_mixed'] ?? false;
                                                    @endphp
                                                    <tr class="{{ $isMixed ? 'mixed-carton-row' : '' }}" data-po-idx="{{ $index }}" data-color-key="{{ $colorName }}" data-carton-idx="{{ $crIdx }}">
                                                        <td>
                                                            <strong>{{ $cRow['carton_name'] }}</strong>
                                                            @if($isMixed)
                                                                <span class="badge bg-warning text-dark" style="font-size: 8px;">MIXED</span>
                                                            @endif
                                                        </td>
                                                        <td>{{ $cRow['po_no'] }}</td>
                                                        <td>{{ $cRow['article_number'] }}</td>
                                                        <td>{{ $cRow['article_description'] }}</td>
                                                        <td>{{ $cRow['ean_code'] }}</td>
                                                        <td><strong>{{ $cRow['size'] }}</strong></td>
                                                        <td><strong>{{ $cRow['quantity'] }}</strong></td>
                                                        <td class="c-len">{{ $cRow['carton_length'] }}</td>
                                                        <td class="c-brd">{{ $cRow['carton_breadth'] }}</td>
                                                        <td class="c-hgt">{{ $cRow['carton_height'] }}</td>
                                                        <td class="net-wt-val">{{ number_format($cRow['net_weight'], 2) }}</td>
                                                        <td class="gross-wt-val">{{ number_format($cRow['gross_weight'], 2) }}</td>
                                                        <td class="cbm-val">{{ number_format($cRow['cbm'], 3) }}</td>
                                                        <td>
                                                            @if($isMixed)
                                                                <button type="button" class="btn btn-sm btn-outline-danger shadow-sm py-0 px-1" title="Remove Mixed Carton" onclick="deleteMixedCarton({{ $index }}, '{{ $colorName }}', '{{ $cRow['carton_name'] }}', {{ $cIdx }})">
                                                                    <i class="fas fa-trash-alt"></i>
                                                                </button>
                                                            @else
                                                                <span class="text-muted">-</span>
                                                            @endif
                                                        </td>
                                                    </tr>
                                                    @endforeach
                                                </tbody>
                                                <tfoot class="fw-bold bg-light">
                                                    <tr>
                                                        <td colspan="6" class="text-end">Total Summary:</td>
                                                        <td id="totalCartonQty-{{ $index }}-{{ $cIdx }}">{{ number_format($totalCartonQty) }}</td>
                                                        <td colspan="3"></td>
                                                        <td id="totalNetWt-{{ $index }}-{{ $cIdx }}">{{ number_format($totalNetWt, 2) }}</td>
                                                        <td id="totalGrossWt-{{ $index }}-{{ $cIdx }}">{{ number_format($totalGrossWt, 2) }}</td>
                                                        <td id="totalCbm-{{ $index }}-{{ $cIdx }}">{{ number_format($totalCbm, 3) }}</td>
                                                        <td></td>
                                                    </tr>
                                                </tfoot>
                                            </table>
                                        </div>

                                        <h6 class="fw-bold text-primary mt-3 mb-2"><i class="fas fa-list-ol me-1"></i> Size Summary Table (Order Qty vs Pack Qty)</h6>
                                        <table class="table table-bordered table-sm style-summary-table" style="max-width: 650px;">
                                            <thead class="table-secondary">
                                                <tr>
                                                    <th>Size</th>
                                                    <th>ORDER. QTY</th>
                                                    <th>PACK QTY</th>
                                                    <th>BALANCE</th>
                                                    <th>PACK QTY %</th>
                                                </tr>
                                            </thead>
                                            <tbody id="sizeSummaryTbody-{{ $index }}-{{ $cIdx }}">
                                                @foreach($pList['size_summary'] as $szKey => $szOrdQty)
                                                @php 
                                                    $szPackQty = $pList['size_pack_summary'][$szKey] ?? $szOrdQty;
                                                    $packedSoFar = 0;
                                                    foreach($pList['cartons'] as $cItem) {
                                                        if (($cItem['size'] ?? '') === $szKey) {
                                                            $packedSoFar += ($cItem['quantity'] ?? 0);
                                                        }
                                                    }
                                                    $balance = $szPackQty - $packedSoFar;
                                                    $pct = $szOrdQty > 0 ? round(($szPackQty / $szOrdQty) * 100, 2) : 100;
                                                @endphp
                                                <tr>
                                                    <td><strong>{{ $szKey }}</strong></td>
                                                    <td>{{ $szOrdQty }}</td>
                                                    <td class="text-success fw-bold">{{ $szPackQty }}</td>
                                                    <td class="{{ $balance > 0 ? 'text-danger fw-bold' : '' }}">{{ $balance }}</td>
                                                    <td>{{ $pct }}%</td>
                                                </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>

                                </div>
                                @php $cIdx++; @endphp
                                @endforeach
                            </div>
                        @endif
                    </div>

                    <!-- SUB-TAB 3: TAX INVOICE PREVIEW (EDITABLE TAX / GST %) -->
                    <div class="tab-pane fade" id="sub-invoice-content-{{ $index }}" role="tabpanel">
                        @if(empty($po['invoices_by_color']))
                            <div class="alert alert-warning">No invoice generated for this PO.</div>
                        @else
                            <!-- Color Sub-Tabs inside Invoice -->
                            <ul class="nav nav-pills mb-3" id="invColorTabs-{{ $index }}" role="tablist">
                                @php $invIdx = 0; @endphp
                                @foreach($po['invoices_by_color'] as $colorName => $inv)
                                <li class="nav-item" role="presentation">
                                    <button class="nav-link btn-sm {{ $invIdx === 0 ? 'active' : '' }}" 
                                            id="inv-color-tab-header-{{ $index }}-{{ $invIdx }}" 
                                            data-bs-toggle="pill" 
                                            data-bs-target="#inv-color-tab-content-{{ $index }}-{{ $invIdx }}" 
                                            type="button" role="tab">
                                        <i class="fas fa-file-invoice-dollar me-1"></i> Invoice (Color: {{ $colorName }})
                                    </button>
                                </li>
                                @php $invIdx++; @endphp
                                @endforeach
                            </ul>

                            <div class="tab-content" id="invColorTabsContent-{{ $index }}">
                                @php $invIdx = 0; @endphp
                                @foreach($po['invoices_by_color'] as $colorName => $inv)
                                <div class="tab-pane fade {{ $invIdx === 0 ? 'show active' : '' }}" 
                                     id="inv-color-tab-content-{{ $index }}-{{ $invIdx }}" 
                                     role="tabpanel">

                                    <!-- Invoice User Input Setup Panel -->
                                    <div class="card mb-3 border-success shadow-sm">
                                        <div class="card-header bg-success text-white fw-bold">
                                            <i class="fas fa-edit me-1"></i> Invoice Setup (Editable Invoice No, Date, Tax %, Transport & Supply Date)
                                        </div>
                                        <div class="card-body bg-light p-3">
                                            <div class="row g-2">
                                                <div class="col-md-3">
                                                    <label class="form-label small fw-bold mb-1">Invoice No:</label>
                                                    <input type="text" class="form-control form-control-sm" 
                                                           id="invRefNo-{{ $index }}-{{ $invIdx }}" 
                                                           value="{{ $inv['ref_no'] }}" 
                                                           onchange="updateInvoiceHeader({{ $index }}, '{{ $colorName }}', {{ $invIdx }})">
                                                </div>
                                                <div class="col-md-2">
                                                    <label class="form-label small fw-bold mb-1">Invoice Date:</label>
                                                    <input type="date" class="form-control form-control-sm" 
                                                           id="invDate-{{ $index }}-{{ $invIdx }}" 
                                                           value="{{ date('Y-m-d') }}" 
                                                           onchange="updateInvoiceHeader({{ $index }}, '{{ $colorName }}', {{ $invIdx }})">
                                                </div>
                                                <div class="col-md-2">
                                                    <label class="form-label small fw-bold text-danger mb-1"><i class="fas fa-percent me-1"></i> Tax % (IGST):</label>
                                                    <div class="input-group input-group-sm">
                                                        <input type="number" step="0.01" class="form-control border-danger fw-bold" 
                                                               id="invGstRate-{{ $index }}-{{ $invIdx }}" 
                                                               value="{{ $inv['igst_rate'] }}" 
                                                               onchange="updateInvoiceHeader({{ $index }}, '{{ $colorName }}', {{ $invIdx }})">
                                                        <span class="input-group-text bg-danger text-white">%</span>
                                                    </div>
                                                </div>
                                                <div class="col-md-3">
                                                    <label class="form-label small fw-bold mb-1">Select Transporter:</label>
                                                    <select class="form-select form-select-sm" 
                                                            id="invTransport-{{ $index }}-{{ $invIdx }}" 
                                                            onchange="updateInvoiceHeader({{ $index }}, '{{ $colorName }}', {{ $invIdx }})">
                                                        @foreach($transports as $tp)
                                                            <option value="{{ $tp->id }}" {{ $tp->id == ($inv['transport_id'] ?? null) ? 'selected' : '' }}>
                                                                {{ $tp->name }}
                                                            </option>
                                                        @endforeach
                                                    </select>
                                                </div>
                                                <div class="col-md-2">
                                                    <label class="form-label small fw-bold mb-1">Supply Date:</label>
                                                    <input type="date" class="form-control form-control-sm" 
                                                           id="invSupplyDate-{{ $index }}-{{ $invIdx }}" 
                                                           value="{{ date('Y-m-d') }}" 
                                                           onchange="updateInvoiceHeader({{ $index }}, '{{ $colorName }}', {{ $invIdx }})">
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="invoice-box">
                                        <!-- Supplier Banner -->
                                        <div class="text-center border-bottom pb-2 mb-3">
                                            <h4 class="fw-bold mb-1" style="letter-spacing: 1px;">CARNATION CREATIONS PRIVATE LIMITED</h4>
                                            <p class="mb-1" style="font-size: 11px;">
                                                376/1, NARASIMHANAICKEN PALAYAM VILLAGE, COIMBATORE, TAMILNADU, INDIA. 641031
                                            </p>
                                            <h5 class="fw-bold text-decoration-underline mb-0" style="letter-spacing: 1.5px;">TAX INVOICE</h5>
                                        </div>

                                        <!-- Header Specs -->
                                        <table class="table table-bordered table-sm mb-3" style="font-size: 10px;">
                                            <tr>
                                                <td width="50%">
                                                    <strong>Registered under MSME UDYAM:</strong> {{ $inv['seller']['udyam'] }}<br>
                                                    <strong>Type:</strong> Small | <strong>Date of Reg:</strong> 25-Aug-2020<br>
                                                    <strong>PAN NO:</strong> {{ $inv['seller']['pan'] }}<br>
                                                    <strong>GST IN:</strong> {{ $inv['seller']['gstin'] }}<br>
                                                    <strong>Serial No. of Invoice:</strong> <strong id="displayInvRefNo-{{ $index }}-{{ $invIdx }}">{{ $inv['ref_no'] }}</strong> &nbsp;&nbsp;&nbsp;&nbsp; <strong>DT:</strong> <span id="displayInvDate-{{ $index }}-{{ $invIdx }}">{{ date('d.m.Y') }}</span>
                                                </td>
                                                <td width="50%">
                                                    <strong>Mode of Transport:</strong> {{ $inv['mode_of_transport'] ?? 'By Road' }}<br>
                                                    <strong>Transporter:</strong> <span id="displayTransporterName-{{ $index }}-{{ $invIdx }}">{{ $transports->first()->name ?? 'N/A' }}</span><br>
                                                    <strong>Date & Time of Supply:</strong> <span id="displaySupplyDate-{{ $index }}-{{ $invIdx }}">{{ date('d.m.Y') }}</span><br>
                                                    <strong>Place OF Supply:</strong> {{ $inv['consignee']['place_of_supply'] }}<br>
                                                    <strong>State Code:</strong> {{ $inv['consignee']['state_code'] }}
                                                </td>
                                            </tr>
                                        </table>

                                        <!-- Bill To & Ship To Boxes -->
                                        <table class="table table-bordered table-sm mb-3" style="font-size: 10px;">
                                            <tr>
                                                <td width="50%">
                                                    <strong>Details of Receiver (Billed to):</strong><br>
                                                    <strong>Name:</strong> {{ $inv['buyer']['name'] }}<br>
                                                    <strong>Address:</strong> {{ $inv['buyer']['address'] }}<br>
                                                    <strong>State Code:</strong> {{ $inv['buyer']['state_code'] }}<br>
                                                    <strong>GSTIN/UNIQUE ID:</strong> {{ $inv['buyer']['gstin'] }}
                                                </td>
                                                <td width="50%">
                                                    <strong>Details of Consignee (Shipped to):</strong><br>
                                                    <strong>Name:</strong> {{ $inv['consignee']['name'] }}<br>
                                                    <strong>Address:</strong> {{ $inv['consignee']['address'] }}<br>
                                                    <strong>State Code:</strong> {{ $inv['consignee']['state_code'] }}<br>
                                                    <strong>GSTIN/UNIQUE ID:</strong> {{ $inv['consignee']['gstin'] }}
                                                </td>
                                            </tr>
                                        </table>

                                        <!-- Lines Table -->
                                        <div class="table-responsive">
                                            <table class="invoice-lines-table mb-3" id="invoiceLinesTable-{{ $index }}-{{ $invIdx }}">
                                                <thead>
                                                    <tr>
                                                        <th>S.No</th>
                                                        <th>Description of Goods</th>
                                                        <th>HSN Code</th>
                                                        <th>STYLE NO</th>
                                                        <th>COLOR</th>
                                                        <th>Total Cartons</th>
                                                        <th>unit</th>
                                                        <th>Qty</th>
                                                        <th>Rate</th>
                                                        <th>Amount</th>
                                                        <th>Discount</th>
                                                        <th>Taxable Value</th>
                                                        <th colspan="2">IGST</th>
                                                    </tr>
                                                    <tr>
                                                        <th colspan="12"></th>
                                                        <th>Rate</th>
                                                        <th>Amount</th>
                                                    </tr>
                                                </thead>
                                                <tbody id="invLinesTbody-{{ $index }}-{{ $invIdx }}">
                                                    @php 
                                                        $lineCount = count($inv['invoice_lines']);
                                                        $totCartons = $inv['total_cartons'];
                                                    @endphp
                                                    @foreach($inv['invoice_lines'] as $lIndex => $line)
                                                    <tr>
                                                        <td>{{ $line['sno'] }}</td>
                                                        <td class="text-start"><strong>{{ $line['description'] }}</strong></td>
                                                        <td>{{ $line['hsn_code'] }}</td>
                                                        <td>{{ $line['style_no'] }}</td>
                                                        <td>{{ $line['color'] }}</td>
                                                        
                                                        @if($lIndex === 0)
                                                        <td rowspan="{{ $lineCount }}" class="align-middle fw-bold inv-tot-cartons-cell-{{ $index }}-{{ $invIdx }}">{{ $totCartons }}</td>
                                                        @endif

                                                        <td>{{ $line['unit'] }}</td>
                                                        <td><strong>{{ number_format($line['qty']) }}</strong></td>
                                                        <td>{{ number_format($line['rate'], 2) }}</td>
                                                        <td>{{ number_format($line['amount'], 2) }}</td>
                                                        <td>{{ number_format($line['discount'], 2) }}</td>
                                                        <td class="line-taxable-val">{{ number_format($line['taxable_value'], 2) }}</td>
                                                        <td class="line-igst-rate">{{ number_format($line['igst_rate'], 2) }}%</td>
                                                        <td class="line-igst-amt">{{ number_format($line['igst_amount'], 2) }}</td>
                                                    </tr>
                                                    @endforeach
                                                </tbody>
                                                <tfoot class="fw-bold bg-light">
                                                    <tr>
                                                        <td colspan="5" class="text-end">Total:</td>
                                                        <td class="inv-tot-cartons-cell-{{ $index }}-{{ $invIdx }}">{{ $totCartons }}</td>
                                                        <td>PCS</td>
                                                        <td id="invTotQty-{{ $index }}-{{ $invIdx }}">{{ number_format($inv['total_qty']) }}</td>
                                                        <td></td>
                                                        <td id="invTotTaxable-{{ $index }}-{{ $invIdx }}">{{ number_format($inv['taxable_value'], 2) }}</td>
                                                        <td>{{ number_format($inv['total_discount'], 2) }}</td>
                                                        <td id="invTotTaxable2-{{ $index }}-{{ $invIdx }}">{{ number_format($inv['taxable_value'], 2) }}</td>
                                                        <td></td>
                                                        <td id="invTotIgst-{{ $index }}-{{ $invIdx }}">{{ number_format($inv['igst_amount'], 2) }}</td>
                                                    </tr>
                                                    <tr class="table-dark text-white">
                                                        <td colspan="12" class="text-end fs-6">Grand Invoice Total:</td>
                                                        <td colspan="2" class="fs-6" id="invGrandTotal-{{ $index }}-{{ $invIdx }}">INR {{ number_format($inv['final_total'], 2) }}</td>
                                                    </tr>
                                                </tfoot>
                                            </table>
                                        </div>

                                        <div class="border p-2 mb-3 bg-light" style="font-size: 10px;">
                                            <strong>Invoice Total (In Words): INR</strong> <span class="fw-bold text-dark" id="invWords-{{ $index }}-{{ $invIdx }}">{{ $inv['amount_in_words'] }}</span>
                                        </div>

                                        <div class="border p-2" style="font-size: 9px;">
                                            <strong>Certified that the Particulars given above are true and correct and the amount indicated</strong><br>
                                            a) represent the price actually charged and that there is no flow additional consideration directly or indirectly from the buyer<br>
                                            b) is provisional as additional consideration will be received from the buyer on account of<br><br>
                                            <strong>TERMS OF SALE</strong><br>
                                            1) Goods once sold will not be taken back or exchanged | 2) Jurisdiction: Coimbatore | 3) Payment Terms: 45 Days
                                        </div>
                                    </div>

                                </div>
                                @php $invIdx++; @endphp
                                @endforeach
                            </div>
                        @endif
                    </div>

                </div>
            </div>

        </div>
        @endforeach
    </div>
</div>

<!-- MANUAL MIXED CARTON MODAL -->
<div class="modal fade" id="addMixedCartonModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-warning text-dark">
                <h5 class="modal-title fw-bold"><i class="fas fa-boxes me-2"></i> Add Manual Mixed Carton</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body bg-light">
                <input type="hidden" id="modalPoIdx">
                <input type="hidden" id="modalColorName">
                <input type="hidden" id="modalCIdx">

                <div class="alert alert-info py-2 small" id="modalNoticeBox">
                    Select sizes and enter remaining quantities to pack into this mixed carton, then enter the mixed carton Net Weight.
                </div>

                <div class="mb-3">
                    <label class="form-label fw-bold text-dark">Mixed Carton Net Weight (kg):</label>
                    <input type="number" step="0.01" id="modalMixedNetWeight" class="form-control" value="18.50">
                </div>

                <h6 class="fw-bold text-primary mb-2">Select Sizes & Quantities:</h6>
                <div id="modalSizeRowsContainer">
                    <!-- Dynamic Size rows inserted here -->
                </div>

                <button type="button" class="btn btn-sm btn-outline-primary fw-bold mt-2" id="addSizeRowBtn" onclick="addModalSizeRow()">
                    <i class="fas fa-plus me-1"></i> + Add Another Size
                </button>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-success fw-bold" id="submitMixedBtn" onclick="submitMixedCarton()">
                    <i class="fas fa-check me-1"></i> Create & Add Mixed Carton
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Sticky Bottom Save Action Bar -->
<div class="sticky-bottom-bar rounded-top mt-4 d-flex justify-content-between align-items-center">
    <div class="form-check">
        <input class="form-check-input" type="checkbox" id="verifyBulkCheck" style="width: 1.2rem; height: 1.2rem;">
        <label class="form-check-label fw-bold text-dark ms-2" for="verifyBulkCheck">
            I accept and verify all extracted POs, Packing Lists & Invoices.
        </label>
    </div>

    <input type="hidden" id="pos_data_payload" value='@json($pos)'>
    <input type="hidden" id="bulk_vendor_id_payload" value="{{ $vendor_id }}">

    <button type="button" class="btn btn-success btn-lg px-4 fw-bold" id="saveBulkAllBtn" disabled>
        <i class="fas fa-check-circle me-2"></i> Verify & Save All POs ({{ count($pos) }})
    </button>
</div>

<script type="text/javascript">
    let globalPosData = @json($pos);

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

            // Recalculate IGST for each line and totals
            let perUnitPrice = globalPosData[poIdx].per_unit_price || 0;
            let vendorDiscount = globalPosData[poIdx].vendor_discount || 0;

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

            invData.taxable_value = newTaxableTotal;
            invData.igst_amount = newIgstTotal;
            invData.final_total = newFinalTotal;
            invData.amount_in_words = convertNumberToWords(newFinalTotal);

            $('#invLinesTbody-' + poIdx + '-' + invIdx).html(linesHtml);
            $('#invTotTaxable-' + poIdx + '-' + invIdx).text(newTaxableTotal.toFixed(2));
            $('#invTotTaxable2-' + poIdx + '-' + invIdx).text(newTaxableTotal.toFixed(2));
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

        // Get per-size capacities & net weights
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
            let sizeQuantities = pList.size_summary || {};

            // Sort po_items strictly by size_chart_master order before generating cartons
            let sortedPoItems = [...poData.po_items];
            sortedPoItems.sort(function(a, b) {
                let szA = (a.size_years || '').toUpperCase().replace(/\s+/g, '');
                let szB = (b.size_years || '').toUpperCase().replace(/\s+/g, '');
                let posA = sizeOrderMap[szA] !== undefined ? sizeOrderMap[szA] : 999;
                let posB = sizeOrderMap[szB] !== undefined ? sizeOrderMap[szB] : 999;
                return posA - posB;
            });

            // Generate Standalone Cartons (Full Capacity Cartons Only)
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

                // Excess % Capped
                let maxPackCap = Math.floor(qOrder * (1 + (excessPct / 100)));
                let qPack = maxPackCap;

                sizePackQuantities[sz] = (sizePackQuantities[sz] || 0) + qPack;

                let cap = sizeCaps[sz.toUpperCase()] || (['9/10Y', '11/12Y', '13/14Y', 'XL', 'XXL', 'XXXL'].includes(cleanSz) ? 50 : 60);

                if (qPack <= cap) {
                    // Single standalone carton for entire size quantity
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
                    // Full cartons of cap pcs ONLY (remainders left in balance pool for mixed cartons)
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
        let pList = poData.packing_lists[colorName];
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
                    ${isMixed ? `<button type="button" class="btn btn-sm btn-outline-danger shadow-sm py-0 px-1" title="Remove Mixed Carton" onclick="deleteMixedCarton(${poIdx}, '${colorName}', '${cRow.carton_name}', ${cIdx})"><i class="fas fa-trash-alt"></i></button>` : '<span class="text-muted">-</span>'}
                </td>
            </tr>`;
        });

        $('#cartonTbody-' + poIdx + '-' + cIdx).html(tbodyHtml);
        $('#totalCartonQty-' + poIdx + '-' + cIdx).text(totQty);
        $('#totalNetWt-' + poIdx + '-' + cIdx).text(totNet.toFixed(2));
        $('#totalGrossWt-' + poIdx + '-' + cIdx).text(totGross.toFixed(2));
        $('#totalCbm-' + poIdx + '-' + cIdx).text(totCbm.toFixed(3));
        $('#totalCartonsBadge-' + poIdx + '-' + cIdx).text('Total Cartons: ' + totCartonCount);
        $('.inv-tot-cartons-cell-' + poIdx + '-' + cIdx).text(totCartonCount);

        // Render Size Summary Table (Balance = Pack Qty - Packed So Far)
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

    function deleteMixedCarton(poIdx, colorName, cartonName, cIdx) {
        Swal.fire({
            title: 'Remove Mixed Carton?',
            text: 'Are you sure you want to remove ' + cartonName + '? Its quantities will be returned to the balance pool.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Yes, Remove'
        }).then((result) => {
            if (result.isConfirmed) {
                let poData = globalPosData[poIdx];
                let pList = poData.packing_lists[colorName];

                if (pList && pList.cartons) {
                    pList.cartons = pList.cartons.filter(c => c.carton_name !== cartonName);

                    let uniqueCartons = [...new Set(pList.cartons.map(c => c.carton_name))];
                    pList.total_cartons = uniqueCartons.length;

                    if (poData.invoices_by_color && poData.invoices_by_color[colorName]) {
                        poData.invoices_by_color[colorName].total_cartons = uniqueCartons.length;
                    }

                    renderCartonTableDOM(poIdx, colorName, cIdx);
                    $('#pos_data_payload').val(JSON.stringify(globalPosData));

                    Swal.fire({
                        title: 'Removed!',
                        text: cartonName + ' has been removed.',
                        icon: 'success',
                        timer: 1200,
                        showConfirmButton: false
                    });
                }
            }
        });
    }

    function openAddMixedModal(poIdx, colorName, cIdx) {
        $('#modalPoIdx').val(poIdx);
        $('#modalColorName').val(colorName);
        $('#modalCIdx').val(cIdx);

        let poData = globalPosData[poIdx];
        let pList = poData.packing_lists[colorName];

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
        let pList = poData.packing_lists[colorName];

        let sizeOptionsHtml = '';
        let availableCount = 0;

        $.each(pList.size_summary, function(szKey, szOrdQty) {
            let szPackQty = pList.size_pack_summary[szKey] || szOrdQty;
            let packedSoFar = 0;
            $.each(pList.cartons, function(k, c) {
                if (c.size === szKey) packedSoFar += c.quantity;
            });
            let remBal = szPackQty - packedSoFar;

            if (remBal > 0) {
                sizeOptionsHtml += `<option value="${szKey}">Size ${szKey} (Remaining Balance: ${remBal} pcs)</option>`;
                availableCount++;
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
        let pList = poData.packing_lists[colorName];

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
                // Check against remaining balance
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

        // Add mixed carton items to packing list
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
        $('#verifyBulkCheck').on('change', function() {
            if ($(this).is(':checked')) {
                $('#saveBulkAllBtn').removeAttr('disabled');
            } else {
                $('#saveBulkAllBtn').attr('disabled', 'disabled');
            }
        });

        $('#saveBulkAllBtn').on('click', function() {
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
