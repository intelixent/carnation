<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\PoMaster;
use App\Models\PoDmartSizes;
use App\Models\PackingListConfigMaster;
use App\Models\PackingListMaster;
use App\Models\PackingListItem;
use App\Models\CartonMaster;
use App\Models\PackingListConfigItem;

class DmartPackingListController extends BaseController
{
    protected $isSuperAdmin;

    // Ship From is fixed for D-Mart POs (vendor's own factory address / GSTIN).
    // Kept as a constant so it only needs to change in one place if it ever does.
    const SHIP_FROM_NAME    = 'M/s. CARNATION CREATIONS PVT LTD.';
    const SHIP_FROM_ADDRESS = '376/1, NARASIMHA NAICKEN PALAYAM,';
    const SHIP_FROM_PINCODE = 'COIMBATORE - 641031,';
    const SHIP_FROM_COUNTRY = 'INDIA';

    public function __construct()
    {
        parent::__construct();
        $this->isSuperAdmin = request()->attributes->get('isSuperAdmin', false);
        $this->middleware('auth');

        if (!$this->isSuperAdmin) {
        }
    }

    public function item_add(Request $request)
    {
        $poId = $request->input('id');

        $po = PoMaster::with('vendor')->find($poId);
        if (!$po) {
            return response()->json(['error' => 'PO not found'], 404);
        }

        $job_num = $po->po_job_num;

        $packingConfig = PackingListConfigMaster::where('po_id', $poId)->first();
        if (!$packingConfig) {
            return response()->json(['error' => 'Packing list configuration not found for this PO'], 404);
        }

        $carton = CartonMaster::where('id', $packingConfig->carton_id)
            ->where('vendor_id', 8)
            ->where('status', 0)
            ->first();

        $carton_id = $carton->id ?? null;

        // Build the color x size grid from po_dmart_sizes
        $dmartRows = PoDmartSizes::where('po_id', $poId)->get();

        if ($dmartRows->isEmpty()) {
            return response()->json(['error' => 'No carton/size data found for this PO'], 404);
        }

        $colorSizeMatrix = [];
        $sizes = [];

        foreach ($dmartRows as $row) {
            $color = $row->color ?: 'N/A';
            $size = $row->size ?: 'N/A';

            $colorSizeMatrix[$color][$size] = ($colorSizeMatrix[$color][$size] ?? 0) + (int) $row->carton_qty;
            $sizes[] = $size;
        }

        $sizes = array_values(array_unique($sizes));

        // ratio and total_cartons are identical on every row (same convention used
        // when these rows were created - see createDmartItems())
        $ratio = (float) $dmartRows->first()->ratio;
        $totalCartons = (float) $dmartRows->first()->total_cartons;

        $remainingCartons = $this->getRemainingCartons($poId, $totalCartons);

        // Packing table number - same "set once, can't change after" convention as
        // the generic packing-list flow. D-Mart has one packing list per PO (no color).
        $existingPackingList = PackingListMaster::where('po_id', $poId)
            ->where('pack_status', 0)
            ->first();
        $existingPackingTableNo = $existingPackingList->packing_table_no ?? null;
        $isFirstTime = is_null($existingPackingTableNo);

        return view('packing_list.dmart_item_add', compact(
            'po',
            'poId',
            'job_num',
            'carton_id',
            'colorSizeMatrix',
            'sizes',
            'ratio',
            'totalCartons',
            'remainingCartons',
            'isFirstTime',
            'existingPackingTableNo'
        ));
    }

    public function item_store(Request $request)
    {
        $validated = $request->validate([
            'po_id'                 => 'required|exists:po_masters,id',
            'carton_id'             => 'required|exists:carton_master,id',
            'generate_carton_count' => 'required|integer|min:1',
            'net_weight'            => 'nullable',
            'packing_table_no'      => 'required|in:1,2',
            'carton_qty_sizes'      => 'nullable', // JSON: [{color, size, qty}, ...]
        ]);

        $poId             = $validated['po_id'];
        $cartonId         = $validated['carton_id'];
        $requestedCartons = (int) $validated['generate_carton_count'];
        $netWeight        = $validated['net_weight'] ?? null;
        $packingTableNo   = $validated['packing_table_no'];

        $po = PoMaster::find($poId);
        if (!$po) {
            return response()->json(['error' => 'PO not found'], 404);
        }

        $dmartRows = PoDmartSizes::where('po_id', $poId)->get();
        if ($dmartRows->isEmpty()) {
            return response()->json(['error' => 'No carton/size data found for this PO'], 400);
        }

        // If the color/size qty grid was edited on the "Add Item" screen, persist those
        // edits to po_dmart_sizes first, so this batch AND any future batches for this
        // PO both generate cartons using the corrected per-carton quantities.
        $editedRows = json_decode($request->input('carton_qty_sizes'), true) ?? [];
        if (!empty($editedRows)) {
            foreach ($editedRows as $edited) {
                $color = trim($edited['color'] ?? '');
                $size  = $edited['size'] ?? null;
                $qty   = isset($edited['qty']) ? (int) $edited['qty'] : null;

                if ($color === '' || empty($size) || is_null($qty)) {
                    continue;
                }

                PoDmartSizes::where('po_id', $poId)
                    ->where('color', $color)
                    ->where('size', $size)
                    ->update(['carton_qty' => $qty]);
            }

            // Re-pull with the edits applied
            $dmartRows = PoDmartSizes::where('po_id', $poId)->get();
        }

        $totalCartons = (float) $dmartRows->first()->total_cartons;
        $remainingCartons = $this->getRemainingCartons($poId, $totalCartons);

        if ($requestedCartons > $remainingCartons) {
            return response()->json([
                'error' => "Requested cartons exceed remaining cartons. Available: {$remainingCartons}, Requested: {$requestedCartons}"
            ], 400);
        }

        try {
            $existingCount = PackingListMaster::where('po_id', $poId)->count();
            $suffix = $existingCount + 1;
            $generatedPackRefNo = "{$po->po_job_num}/{$suffix}";

            $packingList = PackingListMaster::firstOrCreate(
                [
                    'po_id'       => $poId,
                    'pack_status' => 0,
                ],
                [
                    'pack_ref_no'      => $generatedPackRefNo,
                    'vendor_id'        => 8,
                    'po_no'            => $po->po_num,
                    'po_date'          => $po->po_date,
                    'packing_table_no' => $packingTableNo,
                    'created_by'       => auth()->user()->id,
                    'created_at'       => now(),
                ]
            );

            $createdAt = now();

            for ($i = 0; $i < $requestedCartons; $i++) {
                $cartonNumber = $this->getNextCartonNumber($packingList->id);
                $cartonName = (string) $cartonNumber;

                foreach ($dmartRows as $row) {
                    $qtyPerCarton = (int) round($row->carton_qty);
                    if ($qtyPerCarton <= 0) {
                        continue;
                    }

                    PackingListItem::create([
                        'packing_list_id' => $packingList->id,
                        'vendor_id'       => 8,
                        'po_item_id'      => null,
                        'carton_id'       => $cartonId,
                        'carton_name'     => $cartonName,
                        'article_number'  => $row->article_description,
                        'color'           => $row->color,
                        'size'            => $row->size,
                        'quantity'        => $qtyPerCarton,
                        'net_weight'      => $netWeight,
                        'created_by'      => auth()->user()->id,
                        'created_at'      => $createdAt,
                    ]);
                }
            }

            return response()->json([
                'success' => true,
                'po_id'   => $poId,
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function list_items(Request $request)
    {
        $poId = $request->input('po_id');

        $packingList = PackingListMaster::where('po_id', $poId)
            ->where('pack_status', 0)
            ->first();

        $dmartRows = PoDmartSizes::where('po_id', $poId)->get();
        $totalCartons = $dmartRows->first()->total_cartons ?? 0;
        $eanCode = $dmartRows->first()->ean_code ?? '';
        $articleDescription = $dmartRows->first()->article_description ?? '';

        if (!$packingList) {
            return response()->json([
                'packing_lists'     => [],
                'can_add_items'     => true,
                'remaining_cartons' => $totalCartons,
            ]);
        }

        $items = PackingListItem::where('packing_list_id', $packingList->id)->get();

        $groupedByCarton = $items->groupBy('carton_name')->map(function ($cartonItems, $cartonName) {
            return [
                'carton_name' => $cartonName,
                'total_qty'   => (int) $cartonItems->sum('quantity'),
                'items'       => $cartonItems->map(function ($item) {
                    return [
                        'id'             => $item->id,
                        'article_number' => $item->article_number,
                        'color'          => $item->color,
                        'size'           => $item->size,
                        'quantity'       => $item->quantity,
                    ];
                })->values(),
            ];
        })->values();

        $cartonsGenerated = $items->pluck('carton_name')->unique()->count();
        $remainingCartons = max(0, $totalCartons - $cartonsGenerated);

        return response()->json([
            'packing_lists' => [
                [
                    'packing_list_id'     => $packingList->id,
                    'pack_ref_no'         => $packingList->pack_ref_no,
                    'pack_status'         => $packingList->pack_status,
                    'ean_code'            => $eanCode,
                    'article_description' => $articleDescription,
                    'cartons'             => $groupedByCarton,
                ],
            ],
            'can_add_items'     => $remainingCartons > 0,
            'remaining_cartons' => $remainingCartons,
        ]);
    }

    public function item_delete_carton(Request $request)
    {
        $packingListId = $request->input('packing_list_id');
        $cartonName    = $request->input('carton_name');

        try {
            $count = PackingListItem::where('packing_list_id', $packingListId)
                ->where('carton_name', $cartonName)
                ->count();

            if ($count === 0) {
                return response()->json(['error' => 'Carton not found'], 404);
            }

            PackingListItem::where('packing_list_id', $packingListId)
                ->where('carton_name', $cartonName)
                ->delete();

            return response()->json(['success' => true]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    private function getRemainingCartons($poId, $totalCartons)
    {
        $cartonsGenerated = PackingListItem::whereHas('packingList', function ($q) use ($poId) {
            $q->where('po_id', $poId);
        })->distinct('carton_name')->count('carton_name');

        return max(0, $totalCartons - $cartonsGenerated);
    }

    private function getNextCartonNumber($packingListId)
    {
        $lastCarton = PackingListItem::where('packing_list_id', $packingListId)
            ->whereRaw('carton_name REGEXP "^[0-9]+$"')
            ->orderByRaw('CAST(carton_name AS UNSIGNED) DESC')
            ->first();

        return $lastCarton ? ((int) $lastCarton->carton_name) + 1 : 1;
    }

    public function po_print($id)
    {
        $packingList = PackingListMaster::with(['items', 'po'])->find($id);

        if (!$packingList) {
            abort(404, 'Packing list not found');
        }

        $po = $packingList->po;

        // Carton dimensions come from the carton chosen during packing list config
        $config = PackingListConfigMaster::where('po_id', $po->id)->first();
        $carton = $config ? CartonMaster::find($config->carton_id) : null;

        $ctnDimDisplay = '';
        if ($carton && ($carton->length || $carton->breadth || $carton->height)) {
            $ctnDimDisplay = "{$carton->length}*{$carton->breadth}*{$carton->height}";
        }

        // ---------------------------------------------------------------
        // Ship From / Ship To
        // ---------------------------------------------------------------
        $shipFromName    = self::SHIP_FROM_NAME;
        $shipFromAddress = self::SHIP_FROM_ADDRESS;
        $shipFromPincode = self::SHIP_FROM_PINCODE;
        $shipFromCountry = self::SHIP_FROM_COUNTRY;

        $shipToName    = $po->vendor_customer_name ?: 'Avenue Supermarts Ltd.';
        $shipToAddress = $po->vendor_del_adr ?? '';
        $shipToGstin   = $po->vendor_gst ?? '';

        $dmartRows = PoDmartSizes::where('po_id', $po->id)->get();
        $firstDmartRow = $dmartRows->first();

        $eanNo = $firstDmartRow->ean_code ?? '';
        $articleDescription = $firstDmartRow->article_description ?? '';
        $mrp = $firstDmartRow->mrp_price ?? '';
        $caseLot = $firstDmartRow->case_lot ?? 0;   // "Pcs per Carton"
        $ratio = $firstDmartRow->ratio ?? 0;

        // No.Of CTNS must reflect cartons actually generated for THIS packing
        // list, not the PO's planned total_cartons - group by unique carton_name
        // on the packing list's items, same convention used in list_items()/getRemainingCartons().
        $totalCartons = $packingList->items->pluck('carton_name')->unique()->count();

        // Main table: color x size grid of carton_qty (the per-carton qty grid -
        // this stays as the per-carton ratio/quantities, unaffected by how many
        // cartons were generated)
        $colorSizeMatrix = [];
        $sizes = [];
        foreach ($dmartRows as $row) {
            $color = $row->color ?: 'N/A';
            $size = $row->size ?: 'N/A';
            $colorSizeMatrix[$color][$size] = ($colorSizeMatrix[$color][$size] ?? 0) + (int) $row->carton_qty;
            $sizes[] = $size;
        }
        $sizes = array_values(array_unique($sizes));

        $colorCount = count($colorSizeMatrix);

        // Total Pcs now driven off the ACTUAL generated carton count
        $totalPcsPerColor = $ratio * $totalCartons;

        $sizeFooterTotals = array_fill_keys($sizes, 0);
        foreach ($colorSizeMatrix as $color => $sizeQtys) {
            foreach ($sizes as $size) {
                $sizeFooterTotals[$size] += $sizeQtys[$size] ?? 0;
            }
        }
        $ratioFooterTotal = $ratio * $colorCount;
        $totalPcsFooterTotal = $totalPcsPerColor * $colorCount;

        // Summary table: Order Qty per size from packing_list_config_items.po_qty,
        // Pack Qty per size from the actual packed quantities in this packing list.
        $configItems = PackingListConfigItem::where('po_id', $po->id)
            ->where('vendor_id', 8)
            ->get();

        $orderQtyBySize = [];
        $packQtyBySize = [];
        $balanceBySize = [];
        $percentBySize = [];

        foreach ($sizes as $size) {
            $orderQtyBySize[$size] = $configItems->where('size', $size)->sum('po_qty');
            $packQtyBySize[$size] = $packingList->items->where('size', $size)->sum('quantity');
            $balanceBySize[$size] = $orderQtyBySize[$size] - $packQtyBySize[$size];
            $percentBySize[$size] = $orderQtyBySize[$size] > 0
                ? round(($packQtyBySize[$size] / $orderQtyBySize[$size]) * 100, 2)
                : 0;
        }

        $orderQtyTotal = array_sum($orderQtyBySize);
        $packQtyTotal = array_sum($packQtyBySize);
        $balanceTotal = $orderQtyTotal - $packQtyTotal;
        $percentTotal = $orderQtyTotal > 0 ? round(($packQtyTotal / $orderQtyTotal) * 100, 2) : 0;

        return view('packing_list.dmart_print', compact(
            'packingList',
            'po',
            'ctnDimDisplay',
            'shipFromName',
            'shipFromAddress',
            'shipFromPincode',
            'shipFromCountry',
            'shipToName',
            'shipToAddress',
            'shipToGstin',
            'eanNo',
            'articleDescription',
            'mrp',
            'caseLot',
            'totalCartons',
            'ratio',
            'colorSizeMatrix',
            'sizes',
            'totalPcsPerColor',
            'sizeFooterTotals',
            'ratioFooterTotal',
            'totalPcsFooterTotal',
            'orderQtyBySize',
            'packQtyBySize',
            'balanceBySize',
            'percentBySize',
            'orderQtyTotal',
            'packQtyTotal',
            'balanceTotal',
            'percentTotal'
        ));
    }
}
