<?php

namespace App\Http\Controllers;

use App\Http\Controllers\BaseController;
use Illuminate\Http\Request;
use App\Models\VendorMaster;
use App\Models\CartonMaster;
use App\Models\PoMaster;
use App\Models\PoItems;
use App\Models\PoSizes;
use App\Models\PrefixSetting;
use App\Models\PackingListMaster;
use App\Models\PackingListItem;
use App\Models\PackingListConfigMaster;
use App\Models\PackingListConfigItem;
use Illuminate\Support\Facades\Http;
use Barryvdh\DomPDF\Facade\Pdf;

class AutoPackingListController extends BaseController
{
    protected $isSuperAdmin;

    public function __construct()
    {
        parent::__construct();
        $this->isSuperAdmin = request()->attributes->get('isSuperAdmin', false);
        $this->middleware('auth');

        if (!$this->isSuperAdmin) {
        }
    }

    public function auto()
    {
        $page_data = [
            'page_title' => "Automatic Packing List",
            'page_main_title' => "Packing List",
            'page_child_title' => "Auto",
            'isSuperAdmin' => $this->isSuperAdmin,

        ];

        return view('packing_list.auto', $page_data);
    }

    public function get_auto_packing_list_items(Request $request)
    {
        $poId = $request->input('po_id');
        $color = $request->input('color');

        // Get PO details to check vendor
        $po = PoMaster::with('vendor')->find($poId);

        if (!$po) {
            return response()->json(['error' => 'PO not found'], 404);
        }

        // Check if vendor is in allowed list (1, 5, 6)
        $allowedVendors = [1, 5, 6];
        if (!in_array($po->vendor_id, $allowedVendors)) {
            return response()->json([
                'packing_lists' => [],
                'can_add_items' => false,
                'message' => 'Packing list not available for this vendor'
            ]);
        }

        // Get all config items for this PO and color, ordered by position
        $configItems = PackingListConfigItem::whereHas('config', function ($q) use ($poId) {
            $q->where('po_id', $poId);
        })
            ->where('color', $color)
            ->orderBy('position')
            ->with(['poItem', 'config'])
            ->get();

        if ($configItems->isEmpty()) {
            return response()->json([
                'packing_lists' => [],
                'can_add_items' => false,
                'message' => 'No items found for the selected criteria'
            ]);
        }

        // Simulate the packing list creation logic
        $simulatedPackingList = $this->simulatePackingListCreation($po, $color, $configItems);

        return response()->json([
            'packing_lists' => [$simulatedPackingList],
            'can_add_items' => true
        ]);
    }

    /**
     * Simulate packing list creation without storing in database
     */
    private function simulatePackingListCreation($po, $color, $configItems)
    {
        // Generate pack reference number (simulated)
        $generatedPackRefNo = "AUTO-$po->po_job_num/$color";

        // Get carton details from config
        $packingConfig = $configItems->first()->config;
        $carton_id = $packingConfig->carton_id;

        $cartonCounter = 1;
        $packingListItems = [];

        // Get article info
        $info = json_decode($po->article_info, true) ?? [];

        // Process each config item for full cartons first
        foreach ($configItems as $configItem) {
            $poItem = $configItem->poItem;
            if (!$poItem) continue;

            $carton = $configItem->config->carton ?? null;
            $articleNumber = $poItem->article_number;
            $size = $configItem->size;
            $packQty = $configItem->pack_qty ?? 0;
            $perCartonQty = $configItem->per_carton_qty ?? 0;

            // Skip if pack quantity is 0 or per carton quantity is 0
            if ($packQty <= 0 || $perCartonQty <= 0) {
                continue;
            }

            // Calculate how many full cartons we can create
            $fullCartons = intval($packQty / $perCartonQty);

            // Create full cartons
            for ($i = 0; $i < $fullCartons; $i++) {
                $cartonName = $this->formatCartonName($po->vendor_id, $cartonCounter);

                // Get weight per piece from config item (database)
                $weightPerPiece = $configItem->weight_per_piece;
                $net_weight = $perCartonQty * $weightPerPiece;

                $packingListItems[] = [
                    'id' => 'temp_' . $configItem->id . '_' . $cartonCounter,
                    'carton_name' => $cartonName,
                    'article_number' => $articleNumber,
                    'article_description' => $info['Article description'] ?? '',
                    'ean_code' => $poItem->ean_code ?? '',
                    'color' => $color,
                    'size' => $size,
                    'quantity' => $perCartonQty,
                    'config_item_id' => $configItem->id,
                    'po_item_id' => $poItem->id,
                    'carton_id' => $carton_id,
                    'net_weight' => $net_weight,
                    'carton' => $carton ? [
                        'length' => $carton->length ?? 0,
                        'breadth' => $carton->breadth ?? 0,
                        'height' => $carton->height ?? 0,
                    ] : ['length' => 0, 'breadth' => 0, 'height' => 0]
                ];

                $cartonCounter++;
            }
        }

        // Now collect all remaining items for the final carton
        $remainingItems = [];
        foreach ($configItems as $configItem) {
            $poItem = $configItem->poItem;
            if (!$poItem) continue;

            $packQty = $configItem->pack_qty ?? 0;
            $perCartonQty = $configItem->per_carton_qty ?? 0;

            // Skip if quantities are invalid
            if ($packQty <= 0 || $perCartonQty <= 0) {
                continue;
            }

            $remaining = $packQty % $perCartonQty;

            if ($remaining > 0) {
                $remainingItems[] = [
                    'po_item_id' => $poItem->id,
                    'article_number' => $poItem->article_number,
                    'article_description' => $info['Article description'] ?? '',
                    'ean_code' => $poItem->ean_code ?? '',
                    'size' => $configItem->size,
                    'quantity' => $remaining,
                    'config_item_id' => $configItem->id,
                    'weight_per_piece' => $configItem->weight_per_piece,
                ];
            }
        }

        // Create final carton with ALL remaining items
        if (!empty($remainingItems)) {
            $finalCartonName = $this->formatCartonName($po->vendor_id, $cartonCounter);

            foreach ($remainingItems as $item) {
                // Use weight from config item
                $net_weight = $item['quantity'] * $item['weight_per_piece'];

                $packingListItems[] = [
                    'id' => 'temp_' . $item['config_item_id'] . '_final',
                    'carton_name' => $finalCartonName,
                    'article_number' => $item['article_number'],
                    'article_description' => $item['article_description'],
                    'ean_code' => $item['ean_code'],
                    'color' => $color,
                    'size' => $item['size'],
                    'quantity' => $item['quantity'],
                    'config_item_id' => $item['config_item_id'],
                    'po_item_id' => $item['po_item_id'],
                    'carton_id' => $carton_id,
                    'net_weight' => $net_weight,
                    'carton' => $packingConfig->carton ? [
                        'length' => $packingConfig->carton->length ?? 0,
                        'breadth' => $packingConfig->carton->breadth ?? 0,
                        'height' => $packingConfig->carton->height ?? 0,
                    ] : ['length' => 0, 'breadth' => 0, 'height' => 0]
                ];
            }
        }

        return [
            'id' => 'temp_packing_list_' . $po->id . '_' . $color,
            'pack_ref_no' => $generatedPackRefNo,
            'pack_status' => 0,
            'po_no' => $po->po_num,
            'items' => $packingListItems
        ];
    }

    /**
     * Format carton name based on vendor ID
     */
    private function formatCartonName($vendorId, $number)
    {
        if ($vendorId == 1) {
            return 'C' . $number;
        } else {
            return (string) $number;
        }
    }

    public function print(Request $request, $poId, $color)
    {
        // Get PO details
        $po = PoMaster::with('vendor')->find($poId);

        if (!$po) {
            abort(404, 'PO not found');
        }

        // Check if vendor is in allowed list (1, 5, 6)
        $allowedVendors = [1, 5, 6];
        if (!in_array($po->vendor_id, $allowedVendors)) {
            abort(404, 'Packing list not available for this vendor');
        }

        // Get all config items for this PO and color
        $configItems = PackingListConfigItem::whereHas('config', function ($q) use ($poId) {
            $q->where('po_id', $poId);
        })
            ->where('color', $color)
            ->orderBy('position')
            ->with(['poItem', 'config.carton'])
            ->get();

        if ($configItems->isEmpty()) {
            abort(404, 'No items found for the selected criteria');
        }

        // Simulate the packing list creation
        $simulatedPackingList = $this->simulatePackingListCreation($po, $color, $configItems);

        $generatedPackRefNo = "AUTO-$po->po_job_num/$color";

        // Create a mock packing list object for the view
        $packing_list = (object) [
            'id' => $simulatedPackingList['id'],
            'pack_ref_no' => $simulatedPackingList['pack_ref_no'],
            'po_no' => $po->po_num,
            'items' => collect($simulatedPackingList['items'])->map(function ($item) use ($configItems) {
                // Find the corresponding config item to get carton details
                $configItem = $configItems->firstWhere('id', $item['config_item_id']);
                $carton = $configItem ? $configItem->config->carton : null;

                // Use weight from config item instead of calculating
                $weightPerPiece = $configItem->weight_per_piece;
                $netWeight = $item['quantity'] * $weightPerPiece;

                return (object) [
                    'id' => $item['id'],
                    'carton_name' => $item['carton_name'],
                    'article_number' => $item['article_number'],
                    'color' => $item['color'],
                    'size' => $item['size'],
                    'quantity' => $item['quantity'],
                    'net_weight' => $netWeight,
                    'carton' => $carton ? (object) [
                        'length' => $carton->length ?? 0,
                        'breadth' => $carton->breadth ?? 0,
                        'height' => $carton->height ?? 0,
                    ] : (object) ['length' => 0, 'breadth' => 0, 'height' => 0],
                    'po_item' => $configItem ? $configItem->poItem : null
                ];
            })
        ];

        // Get PO date
        $po_date = $po->po_date;

        // Get all PO items for this PO and color to calculate summary
        $poItems = PoItems::where('po_id', $po->id)
            ->where('color', $color)
            ->get();

        // Get all unique sizes and sort them
        $all_sizes = $poItems->pluck('size')->unique()->sort()->values();

        // Calculate order quantities from all PO items by size
        $orderQuantitiesFromAllPacks = collect();
        foreach ($all_sizes as $size) {
            $qty = $poItems->where('size', $size)->sum('qty');
            $orderQuantitiesFromAllPacks->put($size, $qty);
        }

        // Calculate current dispatch/packing list quantities by size
        $dispatch = collect();
        $dispTotal = 0;
        foreach ($all_sizes as $size) {
            $qty = $packing_list->items->where('size', $size)->sum('quantity');
            $dispatch->put($size, $qty);
            $dispTotal += $qty;
        }

        // For handling multiple dispatches - currently just this one dispatch
        // You can modify this if you need to handle previous dispatches
        $dispatchQuantities = collect([$dispatch]);

        // Get additional info from PO
        $info = json_decode($po->article_info, true) ?? [];
        if (empty($info['Article description'])) {
            $info['Article description'] = 'Auto Generated Packing List';
        }

        return view('packing_list.auto_jack_print', compact(
            'packing_list',
            'po',
            'color',
            'po_date',
            'generatedPackRefNo',
            'info',
            'all_sizes',
            'orderQuantitiesFromAllPacks',
            'dispatch',
            'dispTotal',
            'dispatchQuantities'
        ));
    }
}
