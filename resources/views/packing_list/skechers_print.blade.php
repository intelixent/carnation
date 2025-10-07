<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Skechers Packing List</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <!-- Hidden Fields for JavaScript Access -->
    <input type="hidden" id="packing_list_id" value="{{ $packing_list->id }}">
    <input type="hidden" id="po_id" value="{{ $packing_list->po_id }}">

    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 10px;
            padding: 10px;
            font-size: 10px;
            line-height: 1.2;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th,
        td {
            border: 1px solid #000;
            padding: 4px;
            text-align: center;
            font-size: 9px;
        }

        th {
            background-color: #f0f0f0;
            font-weight: bold;
        }

        .summary-table th,
        .summary-table td {
            font-size: 8px;
            padding: 2px;
        }

        .summary-section {
            margin-top: 20px;
        }

        .totals-row {
            font-weight: bold;
            background-color: #f5f5f5;
        }

        /* LP Number cell styling */
        .lp-no-cell {
            position: relative;
            min-width: 120px;
        }

        .lp-no-input {
            width: 100px;
            padding: 2px 4px;
            font-size: 9px;
            border: 1px solid #ccc;
            border-radius: 3px;
        }

        .lp-edit-btn {
            padding: 2px 6px;
            font-size: 10px;
            background-color: #4CAF50;
            color: white;
            border: none;
            border-radius: 3px;
            cursor: pointer;
            margin-left: 5px;
        }

        .lp-edit-btn:hover {
            background-color: #45a049;
        }

        .lp-save-btn {
            background-color: #2196F3;
        }

        .lp-save-btn:hover {
            background-color: #0b7dda;
        }

        /* Loading indicator */
        .loading {
            opacity: 0.6;
            pointer-events: none;
        }

        .error-message {
            color: #d32f2f;
            font-size: 11px;
            margin-top: 5px;
        }

        .success-message {
            color: #2e7d32;
            font-size: 11px;
            margin-top: 5px;
        }

        /* Hide edit buttons during print */
        @media print {
            @page {
                size: A4 landscape;
                margin: 10mm 5mm 10mm 5mm !important;
            }

            .lp-edit-btn {
                display: none !important;
            }

            .lp-no-input {
                border: none !important;
                background: transparent !important;
            }

            .error-message,
            .success-message {
                display: none !important;
            }
        }
    </style>
</head>

<body>
    <!-- Main Packing Table -->
    <table style="width:100%; border-collapse: collapse; border: 1px solid black; font-weight: bold;">
        <!-- Header -->
        <tr>
            <td colspan="6" style="text-align: center; font-size: 18px; border-bottom: 1px solid black;">
                PACKING LIST
            </td>
        </tr>
        <!-- Row 1 -->
        <tr>
            <td style="width: 10%; text-align: left; border: none;">UNIT</td>
            <td style="width: 20%; text-align: left; border: none;"></td>
            <td style="width: 10%; border: none;"></td>
            <td style="width: 25%; border: none;"></td>
            <td style="width: 10%; border: none;"></td>
            <!-- Last column: only left border -->
            <td style="width: 25%; border: none; border-left: 1px solid black;"></td>
        </tr>

        @php
        $allAddressLines = [];

        if(isset($packing_list->po->vendor_del_adr)) {
        $vendorAddress = $packing_list->po->vendor_del_adr;
        if(is_string($vendorAddress)) {
        $vendorAddress = json_decode($vendorAddress, true);
        }
        if(is_array($vendorAddress)) {
        foreach($vendorAddress as $line) {
        if(is_string($line)) {
        $parts = explode('",', $line);
        foreach($parts as $part) {
        $trimmed = trim($part);
        if(!empty($trimmed)) {
        $allAddressLines[] = $trimmed;
        }
        }
        }
        }
        }
        }
        @endphp

        <!-- Row 2 -->
        <tr>
            <td style="border: none; text-align: left;">PO NO</td>
            <td style="border: none; text-align: left;">{{ $packing_list->packing_po_num }}</td>
            <td style="border: none; text-align: right;">Ship From</td>
            <td style="border: none; text-align: left;">Ms.CARNATION CREATIONS PVT LTD.</td>
            <td style="border: none; text-align: right;">Ship To,</td>
            <!-- Last column: only left border -->
            <td style="width: 25%; border: none; border-left: 1px solid black;">
                {{ $allAddressLines[0] ?? 'No Address Line 1' }}
            </td>
        </tr>

        <!-- Row 3 -->
        <tr>
            <td style="border: none; text-align: left;">Style .NO.</td>
            <td style="border: none; text-align: left;">{{ $articleNumbersDisplay }}</td>
            <td style="border: none;"></td>
            <td style="border: none; text-align: left;">376/1, NARASIMHA NAICKEN PALAYAM,</td>
            <td style="border: none;"></td>
            <!-- Last column: only left border -->
            <td style="border: none; border-left: 1px solid black;">
                {{ $allAddressLines[1] ?? '' }}
            </td>
        </tr>

        <!-- Row 4 -->
        <tr>
            <td style="border: none; text-align: left;">Style. Description</td>
            <td style="border: none; text-align: left;">{{ $genderDisplay }} {{ $styleDescriptionsDisplay }}</td>
            <td style="border: none;"></td>
            <td style="border: none; text-align: left;">COIMBATORE-641031</td>
            <td style="border: none;"></td>
            <!-- Last column: only left border -->
            <td style="border: none; border-left: 1px solid black;">
                {{ $allAddressLines[2] ?? '' }}
            </td>
        </tr>

        <!-- Row 5 -->
        <tr>
            <td style="border: none; text-align: left;">Customs Code</td>
            <td style="border: none; text-align: left;">MEE</td>
            <td style="border: none;"></td>
            <td style="border: none; text-align: left;">INDIA</td>
            <td style="border: none;"></td>
            <!-- Last column: only left border -->
            <td style="border: none; border-left: 1px solid black;">
                {{ $allAddressLines[3] ?? '' }}
            </td>
        </tr>

        <!-- Row 6 -->
        <tr>
            <td style="border: none;"></td>
            <td style="border: none;"></td>
            <td style="border: none;"></td>
            <td style="border: none; text-align: left;"></td>
            <td style="border: none;"></td>
            <!-- Last column: only left border -->
            <td style="border: none; border-left: 1px solid black;">
                {{ $allAddressLines[4] ?? '' }}
            </td>
        </tr>

        <!-- Additional rows for remaining address lines -->
        @if(count($allAddressLines) > 5)
        @for($i = 5; $i < count($allAddressLines); $i++)
            <tr>
            <td style="border: none;"></td>
            <td style="border: none;"></td>
            <td style="border: none;"></td>
            <td style="border: none;"></td>
            <td style="border: none;"></td>
            <!-- Last column: only left border -->
            <td style="border: none; border-left: 1px solid black;">{{ $allAddressLines[$i] }}</td>
            </tr>
            @endfor
            @endif
    </table>

    @if($tableData)
    @php
    // 🔹 Determine which sizes have any values > 0
    $displaySizes = collect($tableData['sizeOrder'])->filter(function($size) use ($tableData) {
    // Check all rows + totals
    foreach ($tableData['rows'] as $row) {
    if (!empty($row['per_size'][$size]) && $row['per_size'][$size] > 0) {
    return true;
    }
    }
    if (!empty($tableData['totals']['per_size'][$size]) && $tableData['totals']['per_size'][$size] > 0) {
    return true;
    }
    return false;
    });
    @endphp

    <table>
        <thead>
            {{-- Header row 1: blank over first columns, then MRP label over 2 cols, then G.WT & N.WT with rowspan --}}
            <tr class="header-row">
                <th colspan="{{ count($displaySizes) + ($packing_list->pack_status == 1 ? 5 : 4) }}"></th>
                <th>MRP</th>
                <th></th>

                {{-- Weight columns with rowspan=4 --}}
                <th rowspan="4">G.WT</th>
                <th rowspan="4">N.WT</th>
            </tr>

            {{-- Header row 2 --}}
            <tr class="header-row">
                <th colspan="{{ count($displaySizes) + ($packing_list->pack_status == 1 ? 5 : 4) }}"></th>
                <th>CTN Dimension</th>
                <th>{{ $ctnDimDisplay }}</th>
            </tr>

            {{-- Header row 3 --}}
            <tr class="header-row">
                <th colspan="{{ count($displaySizes) + ($packing_list->pack_status == 1 ? 5 : 4) }}"></th>
                <th>CTN W.T</th>
                <th>{{ $ctnWeight }}</th>
            </tr>

            {{-- Header row 4: actual column headers --}}
            <tr class="header-row">
                <th colspan="2">CTN NO</th>
                <th>CTN</th>
                @if($packing_list->pack_status == 1)
                <th>LP NO</th>
                @endif
                <th>Color</th>
                @foreach($displaySizes as $size)
                <th class="rotate-text">{{ $size }}</th>
                @endforeach
                <th>PCS/CTN</th>
                <th>TOTAL</th>
            </tr>
        </thead>

        <tbody>
            {{-- Data rows --}}
            @foreach($tableData['rows'] as $index => $row)
            <tr data-row-index="{{ $index }}"
                data-article="{{ $row['article_number'] }}"
                data-color="{{ $row['color'] }}"
                data-carton-range="{{ $row['ctn_range'] }}">

                {{-- CTN NO split --}}
                <td>{{ $row['ctn_first'] }}</td>
                <td>{{ $row['ctn_last'] }}</td>

                {{-- CTN (total cartons) --}}
                <td>{{ $row['ttl_ctn'] }}</td>

                {{-- LP NO Column (only if pack_status == 1) --}}
                @if($packing_list->pack_status == 1)
                @php
                $lpKey = $row['article_number'] . '|' . $row['color'] . '|' . $row['ctn_range'];
                // Access lpNumbers from tableData instead of directly
                $lpValue = isset($tableData['lpNumbers']) && isset($tableData['lpNumbers'][$lpKey]) ? $tableData['lpNumbers'][$lpKey] : '';
                @endphp
                <td class="lp-no-cell" data-lp-key="{{ $lpKey }}">
                    <span class="lp-no-display">{{ $lpValue }}</span>
                    <input type="text"
                        class="lp-no-input"
                        value="{{ $lpValue }}"
                        style="display:none;"
                        data-original-value="{{ $lpValue }}">
                    <button class="lp-edit-btn" onclick="editLpNo(this)">Edit</button>
                    <div class="message-container"></div>
                </td>
                @endif

                {{-- Color --}}
                <td>{{ $row['color'] }}</td>

                @foreach($displaySizes as $size)
                <td>{{ ($row['per_size'][$size] ?? 0) > 0 ? $row['per_size'][$size] : '' }}</td>
                @endforeach

                {{-- PCS/CTN --}}
                <td>{{ $row['per_ctn'] }}</td>

                {{-- TOTAL pieces --}}
                <td>{{ $row['total'] }}</td>

                {{-- G.WT --}}
                <td>{{ $row['grs_wt_per'] }}</td>

                {{-- N.WT --}}
                <td>{{ $row['net_wt_per'] }}</td>
            </tr>
            @endforeach

            {{-- Totals Row --}}
            <tr class="totals-row">
                <td colspan="2">TOTAL</td>
                <td>{{ $tableData['totals']['carton_count'] }}</td>

                @if($packing_list->pack_status == 1)
                <td></td>
                @endif

                <td></td>

                @foreach($displaySizes as $size)
                <td>{{ $tableData['totals']['per_size'][$size] > 0 ? $tableData['totals']['per_size'][$size] : '' }}</td>
                @endforeach

                <td></td>
                <td>{{ $tableData['totals']['total_pieces'] }}</td>
                <td>{{ number_format($tableData['totals']['total_gross_weight'] ?? 0, 2) }}</td>
                <td>{{ number_format($tableData['totals']['total_net_weight'] ?? 0, 2) }}</td>
            </tr>
        </tbody>
    </table>
    @endif

    <!-- Summary Section -->
    <div class="summary-section">
        <table class="summary-table">
            <thead>
                <tr>
                    <th>{{ $packing_list->packing_po_num }}</th>
                    <th>SIZE</th>
                    @foreach($all_sizes as $size)
                    <th>{{ $size }}</th>
                    @endforeach
                    <th>TOTAL</th>
                </tr>
            </thead>
            <tbody>
                @php
                $orderTotal = 0;
                $cumulativePackTotal = 0;
                $balTotal = 0;
                @endphp

                {{-- ORDER QTY Row (Total from all packing lists for this PO) --}}
                <tr>
                    <td>{{ $articleNumbersDisplay }}</td>
                    <td>ORDER. QTY</td>
                    @foreach($all_sizes as $size)
                    @php $q = $orderQuantitiesFromAllPacks->get($size, 0); $orderTotal += $q; @endphp
                    <td>{{ $q }}</td>
                    @endforeach
                    <td><strong>{{ $orderTotal }}</strong></td>
                </tr>

                {{-- DISPATCH QTY Rows --}}
                @foreach($dispatchQuantities as $dispatchNumber => $dispatchQty)
                @php
                $dispatchTotal = 0;
                $isCurrentPackingList = $dispatchNumber == $currentDispatchNumber;

                // Check if this is before the current dispatch
                $isPreviousDispatch = $dispatchNumber < $currentDispatchNumber;
                    @endphp

                    {{-- Show previous dispatch rows only if they have quantity --}}
                    @if($isPreviousDispatch && $dispatchQty->sum() > 0)
                    @php
                    $ordinalNumber = $dispatchNumber == 1 ? '1st'
                    : ($dispatchNumber == 2 ? '2nd'
                    : ($dispatchNumber == 3 ? '3rd' : $dispatchNumber.'th'));
                    @endphp
                    <tr>
                        <td></td>
                        <td>{{ $ordinalNumber }} DISPATCH QTY</td>
                        @foreach($all_sizes as $size)
                        @php
                        $q = $dispatchQty->get($size, 0);
                        $dispatchTotal += $q;
                        @endphp
                        <td>{{ $q }}</td>
                        @endforeach
                        <td><strong>{{ $dispatchTotal }}</strong></td>
                    </tr>
                    @endif

                    {{-- Current dispatch row (always show) --}}
                    @if($isCurrentPackingList)
                    <tr>
                        <td>{{ $genderDisplay . ' ' . $styleDescriptionsDisplay }}</td>
                        <td>PACKING LIST QTY</td>
                        @foreach($all_sizes as $size)
                        @php
                        $q = $dispatchQty->get($size, 0);
                        $dispatchTotal += $q;
                        @endphp
                        <td>{{ $q }}</td>
                        @endforeach
                        <td><strong>{{ $dispatchTotal }}</strong></td>
                    </tr>
                    @endif
                    @endforeach

                    {{-- BALANCE Row --}}
                    <tr>
                        <td></td>
                        <td>BALANCE</td>
                        @foreach($all_sizes as $size)
                        @php
                        $totalDispatched = 0;
                        foreach($dispatchQuantities as $dispatchQty) {
                        $totalDispatched += $dispatchQty->get($size, 0);
                        }
                        $orderQty = $orderQuantitiesFromAllPacks->get($size, 0);
                        $b = $orderQty - $totalDispatched;
                        $balTotal += $b;
                        @endphp
                        <td>{{ $b }}</td>
                        @endforeach
                        <td><strong>{{ $balTotal }}</strong></td>
                    </tr>

                    {{-- PACK QTY % Row --}}
                    <tr>
                        <td>{{ $uniqueColorDisplay }}</td>
                        <td>PACK QTY %</td>
                        @foreach($all_sizes as $size)
                        @php
                        $totalDispatched = 0;
                        foreach($dispatchQuantities as $dispatchQty) {
                        $totalDispatched += $dispatchQty->get($size, 0);
                        }
                        $orderQty = $orderQuantitiesFromAllPacks->get($size, 0);
                        $pct = $orderQty > 0 ? ($totalDispatched / $orderQty) * 100 : 0;
                        @endphp
                        <td>{{ $pct > 0 ? round($pct) . '%' : '-' }}</td>
                        @endforeach
                        <td>
                            <strong>
                                @php
                                $totalDispatched = 0;
                                foreach($dispatchQuantities as $dispatchQty) {
                                foreach($all_sizes as $size) {
                                $totalDispatched += $dispatchQty->get($size, 0);
                                }
                                }
                                @endphp
                                {{ $orderTotal > 0 ? round(($totalDispatched / $orderTotal) * 100) . '%' : '-' }}
                            </strong>
                        </td>
                    </tr>
            </tbody>
        </table>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        // Set up CSRF token for all AJAX requests
        $.ajaxSetup({
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            }
        });

        // LP Number Edit Function
        function editLpNo(button) {
            const cell = $(button).closest('.lp-no-cell');
            const display = cell.find('.lp-no-display');
            const input = cell.find('.lp-no-input');
            const btn = $(button);
            const messageContainer = cell.find('.message-container');

            // Clear any previous messages
            messageContainer.empty();

            // Enter edit mode
            display.hide();
            input.show().focus();
            btn.text('Save').removeClass('lp-edit-btn').addClass('lp-save-btn');
            btn.attr('onclick', '').off('click').on('click', function() {
                saveLpNo(this);
            });

            // Handle Enter key to save
            input.off('keypress').on('keypress', function(e) {
                if (e.which == 13) { // Enter key
                    saveLpNo(btn[0]);
                }
            });

            // Handle Escape key to cancel
            input.off('keyup').on('keyup', function(e) {
                if (e.which == 27) { // Escape key
                    cancelLpNoEdit(btn[0]);
                }
            });
        }

        // LP Number Save Function (Gets values from hidden fields)
        function saveLpNo(button) {
            const cell = $(button).closest('.lp-no-cell');
            const display = cell.find('.lp-no-display');
            const input = cell.find('.lp-no-input');
            const btn = $(button);
            const row = $(button).closest('tr');
            const messageContainer = cell.find('.message-container');

            const lpValue = input.val().trim();
            const originalValue = input.data('original-value');
            const articleNumber = row.data('article');
            const color = row.data('color');
            const cartonRange = row.data('carton-range');

            // Get values from hidden fields
            const packingListId = $('#packing_list_id').val();
            const poId = $('#po_id').val();

            // Check if value has changed
            if (lpValue === originalValue) {
                cancelLpNoEdit(button);
                return;
            }

            // Validate hidden field values
            if (!packingListId || !poId) {
                messageContainer.html('<div class="error-message">Error: Missing required data</div>');
                return;
            }

            // Disable button and input during save
            btn.prop('disabled', true).text('Saving...');
            input.prop('disabled', true);
            messageContainer.empty();

            // Save via AJAX using values from hidden fields
            $.ajax({
                url: '{{ route("packing_list_store_lp_number") }}',
                method: 'POST',
                data: {
                    packing_list_id: packingListId,
                    po_id: poId,
                    article_number: articleNumber,
                    color: color,
                    carton_range: cartonRange,
                    lp_no: lpValue
                },
                success: function(data) {
                    if (data.success) {
                        // Update display
                        display.text(lpValue);
                        display.show();
                        input.hide().prop('disabled', false);
                        input.data('original-value', lpValue);

                        // Reset button
                        btn.text('Edit').removeClass('lp-save-btn').addClass('lp-edit-btn');
                        btn.prop('disabled', false);
                        btn.off('click').attr('onclick', 'editLpNo(this)');

                        // Show success message
                        messageContainer.html('<div class="success-message">Saved successfully!</div>');
                        setTimeout(() => {
                            messageContainer.empty();
                        }, 3000);

                    } else {
                        // Show error message
                        messageContainer.html('<div class="error-message">Error: ' + (data.message || 'Unknown error') + '</div>');
                        btn.prop('disabled', false).text('Save');
                        input.prop('disabled', false);
                    }
                },
                error: function(xhr, status, error) {
                    console.error('AJAX Error:', error);
                    console.error('Response:', xhr.responseText);

                    let errorMessage = 'Failed to save LP Number. Please try again.';
                    if (xhr.responseJSON && xhr.responseJSON.message) {
                        errorMessage = xhr.responseJSON.message;
                    }

                    messageContainer.html('<div class="error-message">' + errorMessage + '</div>');
                    btn.prop('disabled', false).text('Save');
                    input.prop('disabled', false);
                }
            });
        }

        // Cancel LP Number Edit Function
        function cancelLpNoEdit(button) {
            const cell = $(button).closest('.lp-no-cell');
            const display = cell.find('.lp-no-display');
            const input = cell.find('.lp-no-input');
            const btn = $(button);
            const messageContainer = cell.find('.message-container');
            const originalValue = input.data('original-value');

            // Reset to original value
            input.val(originalValue);

            // Reset display
            display.show();
            input.hide().prop('disabled', false);

            // Reset button
            btn.text('Edit').removeClass('lp-save-btn').addClass('lp-edit-btn');
            btn.prop('disabled', false);
            btn.off('click').attr('onclick', 'editLpNo(this)');

            // Clear messages
            messageContainer.empty();

            // Remove event handlers
            input.off('keypress keyup');
        }

        // Auto-print on page load
        window.onload = function() {
            // Wait a moment for content to render, then print
            setTimeout(function() {
                window.print();
            }, 500);
        };

        // Handle print media queries
        window.addEventListener('beforeprint', function() {
            // Cancel any ongoing edit operations before printing
            $('.lp-save-btn').each(function() {
                cancelLpNoEdit(this);
            });
        });
    </script>
</body>

</html>