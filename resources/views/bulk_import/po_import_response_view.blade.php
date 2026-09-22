<style>
    /* Hide the single PO verify/save footer from the included partials in bulk import */
    .vendor-single-response-wrapper .fixed-bottom {
        display: none !important;
    }
    .vendor-single-response-wrapper {
        margin-bottom: 20px;
    }
</style>

<div class="row">
    <!-- PO Navigation Tabs -->
    <div class="col-12 mb-3">
        <ul class="nav nav-tabs po-main-tab flex-wrap" id="bulkPoTabs" role="tablist">
            @foreach($pos as $index => $po)
            <li class="nav-item" role="presentation">
                <button class="nav-link {{ $index === 0 ? 'active' : '' }}" 
                        id="po-tab-{{ $index }}" 
                        data-bs-toggle="tab" 
                        data-bs-target="#po-pane-{{ $index }}" 
                        type="button" role="tab"
                        aria-controls="po-pane-{{ $index }}"
                        aria-selected="{{ $index === 0 ? 'true' : 'false' }}">
                    <i class="fas fa-file-pdf text-danger me-1"></i>
                    <strong>{{ $po['po_num'] }}</strong>
                    @if($po['duplicate_status'] === 'amended')
                        <span class="badge bg-danger ms-1" style="font-size: 9px;">AMENDED</span>
                    @elseif($po['duplicate_status'] === 'unamended')
                        <span class="badge bg-warning text-dark ms-1" style="font-size: 9px;">EXISTS</span>
                    @else
                        <span class="badge bg-success ms-1" style="font-size: 9px;">READY</span>
                    @endif
                </button>
            </li>
            @endforeach
        </ul>
    </div>

    <!-- PO Tab Panes -->
    <div class="col-12">
        <div class="tab-content" id="bulkPoTabsContent">
            @foreach($pos as $index => $po)
            <div class="tab-pane fade {{ $index === 0 ? 'show active' : '' }} mb-4" 
                 id="po-pane-{{ $index }}" 
                 role="tabpanel" 
                 aria-labelledby="po-tab-{{ $index }}">

                {{-- Already Exists Alert Alone --}}
                @if($po['duplicate_status'] === 'amended')
                    <div class="alert alert-danger fw-bold shadow-sm mb-3">
                        <i class="fas fa-exclamation-triangle me-2"></i> {{ $po['duplicate_message'] }}
                    </div>
                @elseif($po['duplicate_status'] === 'unamended')
                    <div class="alert alert-warning fw-bold shadow-sm mb-3">
                        <i class="fas fa-info-circle me-2"></i> {{ $po['duplicate_message'] }}
                    </div>
                @endif

                {{-- Include Vendor Single Extract Response View --}}
                <div class="vendor-single-response-wrapper">
                    @include($singleView, [
                        'data' => $po['single_view_data'],
                        'sizes' => $sizes ?? [],
                        'totalCaseLot' => $po['total_case_lot'] ?? 0
                    ])
                </div>

            </div>
            @endforeach
        </div>
    </div>

    <!-- Sticky Bottom Save Action Bar -->
    <div class="col-12 mt-3">
        <div class="sticky-save-bar d-flex justify-content-between align-items-center">
            <div>
                <span class="fw-bold text-dark">
                    <i class="fas fa-info-circle text-teal me-1" style="color: #0f766e;"></i> 
                    Ready to store <strong>{{ count($pos) }} Purchase Order(s)</strong> alone.
                </span>
            </div>
            <div class="d-flex gap-2">
                <button type="button" class="btn btn-outline-danger px-4" id="cancel_all_btn">
                    <i class="fas fa-trash-alt me-1"></i> Discard
                </button>
                <button type="button" class="btn btn-success px-5 fw-bold" id="save_all_pos_btn" style="background-color: #0f766e; border-color: #0f766e;">
                    <i class="fas fa-save me-2"></i> Save All POs Alone
                </button>
            </div>
        </div>
    </div>
</div>
