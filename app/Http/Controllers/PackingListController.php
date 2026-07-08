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
use App\Models\PackingListLpNumber;
use App\Models\PoDmartSizes;
use Illuminate\Support\Facades\Http;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Http\Controllers\DmartPackingListController;

class PackingListController extends BaseController
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

    public function config()
    {
        $page_data = [
            'page_title' => "Config",
            'page_main_title' => "Packing List",
            'vendors' => VendorMaster::where('status', 0)->get(),
            'isSuperAdmin' => $this->isSuperAdmin,
        ];

        return view('packing_list.config', $page_data);
    }

    public function get_config_vendor_po(Request $request)
    {
        $pos = PoMaster::with('vendor')
            ->where('vendor_id', $request->vendor_id)
            ->where('status', 1)
            ->get(['id', 'po_num', 'po_ref_num', 'po_job_num', 'vendor_id']);

        if ($pos->isEmpty()) {
            return response()->json(['error' => 'No PO for this vendor'], 404);
        }

        $transformedPos = $pos->map(function ($po) {
            return [
                'id' => $po->id,
                'po_num' => $po->po_num,
                'po_ref_num' => $po->po_ref_num,
                'po_job_num' => $po->po_job_num,
                'vendor_name' => $po->vendor->name ?? 'N/A'
            ];
        });

        return response()->json($transformedPos);
    }

    public function get_config_po_details(Request $request)
    {
        $po = PoMaster::with('vendor')->find($request->input('id'));
        if (!$po) {
            return response()->json(['error' => 'PO not found'], 404);
        }

        // Check if packing list items exist for this PO
        $hasPackingListItems = PackingListItem::whereHas('packingList', function ($q) use ($po) {
            $q->where('po_id', $po->id);
        })->exists();

        $existingConfig = PackingListConfigMaster::where('po_id', $po->id)->first();
        $selectedCartonId = $existingConfig ? $existingConfig->carton_id : null;

        // Determine styleRef based on vendor
        $articleInfo = json_decode($po->article_info, true) ?: [];
        switch ($po->vendor_id) {
            case 1:
                $styleRef = $articleInfo['Article description'] ?? '';
                break;
            case 3:
                $styleRef = $articleInfo['style_description'] ?? '';
                break;
            case 4:
                $styleRef = $articleInfo['benetton_style_ref'] ?? '';
                break;
            case 5:
                $styleRef = $articleInfo['style_reference'] ?? '';
                break;
            case 6:
                $styleRef = $articleInfo['style_ref'] ?? '';
                break;
            case 8:
                // D-Mart: article description lives on po_dmart_sizes, not article_info
                $firstDmartRow = PoDmartSizes::where('po_id', $po->id)->first();
                $styleRef = $firstDmartRow->article_description ?? '';
                break;
            default:
                $styleRef = $articleInfo['style_description'] ?? $articleInfo['Article description'] ?? '';
        }

        // Fetch PO items or config items for size matrix
        $colorSizeMatrix = [];
        $allSizes = [];

        if ($po->vendor_id == 4) {
            // Use PoSizes for Benetton (Vendor ID 4)
            $configItems = PoSizes::where('po_id', $request->input('id'))
                ->where('vendor_id', 4)
                ->get(['color', 'size', 'qty']);

            foreach ($configItems as $item) {
                $color = $item->color;
                $size = $item->size;
                $qty = $item->qty;

                $allSizes[] = $size;
                $colorSizeMatrix[$color][$size] = ($colorSizeMatrix[$color][$size] ?? 0) + $qty;
            }
        } elseif ($po->vendor_id == 8) {
            // D-Mart (Vendor ID 8): use po_dmart_sizes.
            // PO Qty per color/size = carton_qty (qty per carton, from the "Add Color" grid)
            // multiplied by total_cartons (total_qty from PDF / case_lot, same value on every row).
            $dmartItems = PoDmartSizes::where('po_id', $po->id)
                ->get(['color', 'size', 'carton_qty', 'total_cartons']);

            foreach ($dmartItems as $item) {
                $color = $item->color ?: 'N/A';
                $size = $item->size ?: 'N/A';
                $qty = $item->carton_qty * $item->total_cartons;

                $allSizes[] = $size;
                $colorSizeMatrix[$color][$size] = ($colorSizeMatrix[$color][$size] ?? 0) + $qty;
            }
        } else {
            // Default: use PoItems table for all other vendors (1, 2, 3, 5, 6)
            $poItems = PoItems::where('po_id', $po->id)->get();
            foreach ($poItems as $item) {
                $color = $item->color ?? $item->id_color ?? 'N/A';
                $size = $item->size ?? 'N/A';
                $qty = $item->qty ?? 0;

                $allSizes[] = $size;
                $colorSizeMatrix[$color][$size] = ($colorSizeMatrix[$color][$size] ?? 0) + $qty;
            }
        }

        $allSizes = array_values(array_unique($allSizes));

        // Get excess percentage and calculate packQtyMatrix (same excess-based rounding for every vendor, including D-Mart)
        $excessPercentage = $po->vendor->excess ?? 0;
        $packQtyMatrix = [];
        $totalPackQty = 0;
        foreach ($colorSizeMatrix as $color => $sizes) {
            foreach ($sizes as $size => $qty) {
                $calcQty = $qty * (1 + $excessPercentage / 100);
                $packQty = round($calcQty);
                if ($packQty > $calcQty) {
                    $packQty--;
                }
                $packQtyMatrix[$color][$size] = $packQty;
                $totalPackQty += $packQty;
            }
        }

        // Totals by size
        $poQtyBySizeTotal = [];
        $packQtyBySizeTotal = [];
        foreach ($allSizes as $size) {
            $poQty = array_sum(array_column($colorSizeMatrix, $size));
            $packQty = array_sum(array_column($packQtyMatrix, $size));
            $poQtyBySizeTotal[$size] = $poQty;
            $packQtyBySizeTotal[$size] = $packQty;
        }

        // Position / Per Carton Qty are not applicable for D-Mart (Vendor ID 8) - the UI hides
        // those rows entirely for this vendor and they are always stored as null on save.
        $showPositionCartonQty = $po->vendor_id != 8;

        // For ALL vendors - get existing position, per_carton_qty, and weight_per_piece data
        $positionData = [];
        $perCartonQtyData = [];
        $weightPerPieceData = [];

        if ($existingConfig) {
            $configItems = PackingListConfigItem::where('config_id', $existingConfig->id)
                ->select('size', 'position', 'per_carton_qty', 'weight_per_piece')
                ->groupBy('size', 'position', 'per_carton_qty', 'weight_per_piece')
                ->get();

            foreach ($configItems as $item) {
                $positionData[$item->size] = $item->position ?? 1;
                $perCartonQtyData[$item->size] = $item->per_carton_qty ?? 0;
                $weightPerPieceData[$item->size] = $item->weight_per_piece ?? 0;
            }
        } else {
            foreach ($allSizes as $size) {
                $weightPerPieceData[$size] = 0;
            }
        }

        // Get cartons based on vendor
        $cartons = CartonMaster::where('vendor_id', $po->vendor_id)
            ->where('status', 0)
            ->get();

        return view('packing_list.config_details', compact(
            'po',
            'styleRef',
            'colorSizeMatrix',
            'packQtyMatrix',
            'allSizes',
            'totalPackQty',
            'cartons',
            'poQtyBySizeTotal',
            'packQtyBySizeTotal',
            'selectedCartonId',
            'hasPackingListItems',
            'positionData',
            'perCartonQtyData',
            'weightPerPieceData',
            'showPositionCartonQty'
        ));
    }

    public function save_config_po_details(Request $request)
    {
        $po_id     = $request->input('po_id');
        $carton_id = $request->input('carton_id');

        // Validate PO exists
        $po = PoMaster::with('vendor')->find($po_id);
        if (!$po) {
            return response()->json(['error' => 'PO not found'], 404);
        }

        $excess    = $po->vendor->excess ?? 0;
        $shortage  = $po->vendor->shortage ?? 0;
        $vendor_id = $po->vendor_id;

        try {
            // Find or create master record
            $configMaster = PackingListConfigMaster::firstOrNew([
                'po_id'     => $po_id,
                'vendor_id' => $vendor_id,
            ]);

            // Update master fields
            $configMaster->fill([
                'carton_id' => $carton_id,
                'excess'    => $excess,
                'shortage'  => $shortage,
                'status'    => $configMaster->exists ? $configMaster->status : 0,
            ]);

            if (!$configMaster->exists) {
                $configMaster->created_by = auth()->user()->id;
                $configMaster->created_at = now();
            }
            $configMaster->save();

            // Get position, per_carton_qty, and weight_per_piece data (not used for D-Mart)
            $positions = $request->input('positions', []);
            $perCartonQtys = $request->input('per_carton_qtys', []);
            $weightPerPieces = $request->input('weight_per_pieces', []);

            // Prepare list of identifiers to keep
            $keepIds = [];

            if ($vendor_id == 4) {
                // For Benetton (Vendor ID 4): use PoSizes
                $items = PoSizes::where('po_id', $po_id)
                    ->where('vendor_id', 4)
                    ->get(['color', 'size', 'qty']);

                foreach ($items as $item) {
                    $poQty   = $item->qty;
                    $calcQty = $poQty * (1 + $excess / 100);
                    $packQty = round($calcQty);
                    if ($packQty > $calcQty) {
                        $packQty--;
                    }
                    $position = $positions[$item->size] ?? 1;
                    $perCartonQty = $perCartonQtys[$item->size] ?? 0;
                    $weightPerPiece = $weightPerPieces[$item->size] ?? 0;

                    $configItem = PackingListConfigItem::updateOrCreate([
                        'config_id' => $configMaster->id,
                        'color'     => $item->color,
                        'size'      => $item->size,
                    ], [
                        'po_id'           => $po_id,
                        'vendor_id'       => $vendor_id,
                        'po_qty'          => $poQty,
                        'pack_qty'        => $packQty,
                        'position'        => $position,
                        'per_carton_qty'  => $perCartonQty,
                        'weight_per_piece' => $weightPerPiece,
                        'status'          => 0,
                        'created_by'      => auth()->user()->id,
                        'created_at'      => now(),
                    ]);

                    $keepIds[] = $configItem->id;
                }
            } elseif ($vendor_id == 8) {
                // For D-Mart (Vendor ID 8): use PoDmartSizes.
                // PO Qty = carton_qty * total_cartons (recomputed server-side, same as the config screen).
                // Position and Per Carton Qty are not applicable for D-Mart - always saved as null,
                // regardless of what (if anything) was posted for them.
                $items = PoDmartSizes::where('po_id', $po_id)
                    ->get(['color', 'size', 'carton_qty', 'total_cartons']);

                foreach ($items as $item) {
                    $color   = $item->color ?: 'N/A';
                    $size    = $item->size ?: 'N/A';
                    $poQty   = $item->carton_qty * $item->total_cartons;
                    $calcQty = $poQty * (1 + $excess / 100);
                    $packQty = round($calcQty);
                    if ($packQty > $calcQty) {
                        $packQty--;
                    }

                    $weightPerPiece = $weightPerPieces[$size] ?? 0;

                    $configItem = PackingListConfigItem::updateOrCreate([
                        'config_id' => $configMaster->id,
                        'color'     => $color,
                        'size'      => $size,
                    ], [
                        'po_id'           => $po_id,
                        'vendor_id'       => $vendor_id,
                        'po_qty'          => $poQty,
                        'pack_qty'        => $packQty,
                        'position'        => null,
                        'per_carton_qty'  => null,
                        'weight_per_piece' => $weightPerPiece,
                        'status'          => 0,
                        'created_by'      => auth()->user()->id,
                        'created_at'      => now(),
                    ]);

                    $keepIds[] = $configItem->id;
                }
            } elseif ($vendor_id == 2) {
                // For Vendor ID 2: use PoItems and include country field
                $items = PoItems::where('po_id', $po_id)->get();

                foreach ($items as $item) {
                    $color   = $item->color ?? $item->id_color ?? 'N/A';
                    $size    = $item->size ?? 'N/A';
                    $poQty   = $item->qty ?? 0;
                    $calcQty = $poQty * (1 + $excess / 100);
                    $packQty = round($calcQty);
                    if ($packQty > $calcQty) {
                        $packQty--;
                    }
                    $country = $item->country ?? null; // Get country from PoItems

                    // Get position, per_carton_qty, and weight_per_piece
                    $position = $positions[$size] ?? 1;
                    $perCartonQty = $perCartonQtys[$size] ?? 0;
                    $weightPerPiece = $weightPerPieces[$size] ?? 0;

                    $configItem = PackingListConfigItem::updateOrCreate([
                        'config_id'  => $configMaster->id,
                        'po_item_id' => $item->id,
                    ], [
                        'po_id'           => $po_id,
                        'vendor_id'       => $vendor_id,
                        'color'           => $color,
                        'size'            => $size,
                        'country'         => $country,
                        'po_qty'          => $poQty,
                        'pack_qty'        => $packQty,
                        'position'        => $position,
                        'per_carton_qty'  => $perCartonQty,
                        'weight_per_piece' => $weightPerPiece,
                        'status'          => 0,
                        'created_by'      => auth()->user()->id,
                        'created_at'      => now(),
                    ]);

                    $keepIds[] = $configItem->id;
                }
            } else {
                // For all other vendors (1, 3, 5, 6): use PoItems
                $items = PoItems::where('po_id', $po_id)->get();

                foreach ($items as $item) {
                    $color   = $item->color ?? $item->id_color ?? 'N/A';
                    $size    = $item->size ?? 'N/A';
                    $poQty   = $item->qty ?? 0;
                    $calcQty = $poQty * (1 + $excess / 100);
                    $packQty = round($calcQty);
                    if ($packQty > $calcQty) {
                        $packQty--;
                    }
                    // Get position, per_carton_qty, and weight_per_piece for ALL vendors
                    $position = $positions[$size] ?? 1;
                    $perCartonQty = $perCartonQtys[$size] ?? 0;
                    $weightPerPiece = $weightPerPieces[$size] ?? 0;

                    $configItem = PackingListConfigItem::updateOrCreate([
                        'config_id'  => $configMaster->id,
                        'po_item_id' => $item->id,
                    ], [
                        'po_id'           => $po_id,
                        'vendor_id'       => $vendor_id,
                        'color'           => $color,
                        'size'            => $size,
                        'po_qty'          => $poQty,
                        'pack_qty'        => $packQty,
                        'position'        => $position,
                        'per_carton_qty'  => $perCartonQty,
                        'weight_per_piece' => $weightPerPiece,
                        'status'          => 0,
                        'created_by'      => auth()->user()->id,
                        'created_at'      => now(),
                    ]);

                    $keepIds[] = $configItem->id;
                }
            }

            $isUpdate = PackingListItem::whereHas('packingList', function ($q) use ($po) {
                $q->where('po_id', $po->id);
            })->exists();

            $message = $isUpdate ?
                'Packing list configuration updated successfully' :
                'Packing list configuration saved successfully';

            return response()->json([
                'success' => true,
                'message' => $message
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to save configuration: ' . $e->getMessage()
            ], 500);
        }
    }

    public function index()
    {
        $packingLists = PackingListMaster::where('status', 0)
            ->orderBy('id', 'desc')
            ->get();

        $page_data = [
            'page_title' => "Master",
            'page_main_title' => "Packing List",
            'packingLists' => $packingLists,
            'isSuperAdmin' => $this->isSuperAdmin,
        ];

        return view('packing_list.master', $page_data);
    }

    public function updatePackingListPoNumber(Request $request)
    {
        try {
            $packingListId = $request->input('packing_list_id');
            $packingPoNumber = $request->input('packing_po_num');

            $packingList = PackingListMaster::find($packingListId);

            if (!$packingList) {
                return response()->json([
                    'success' => false,
                    'message' => 'Packing list not found.'
                ]);
            }

            // Check if this is vendor ID 2
            if ($packingList->vendor_id != 2) {
                return response()->json([
                    'success' => false,
                    'message' => 'Packing PO number can only be updated for vendor ID 2.'
                ]);
            }

            $packingList->packing_po_num = $packingPoNumber;
            $packingList->save();

            return response()->json([
                'success' => true,
                'message' => 'Packing PO number updated successfully.',
                'packing_po_number' => $packingPoNumber
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred: ' . $e->getMessage()
            ]);
        }
    }

    public function add()
    {
        $page_data = [
            'page_title' => "Packing List Entry",
            'page_main_title' => "Packing List",
            'page_child_title' => "Entry",
            'isSuperAdmin' => $this->isSuperAdmin,

        ];

        return view('packing_list.add', $page_data);
    }

    public function search_po(Request $request)
    {
        $search = $request->input('q');

        $results = PoMaster::with('vendor')
            ->whereHas('packingListConfigs')
            ->where(function ($query) use ($search) {
                $query->where('po_num', 'like', "%{$search}%")
                    ->orWhere('po_job_num', 'like', "%{$search}%")
                    ->orWhereHas('vendor', function ($q) use ($search) {
                        $q->where('name', 'like', "%{$search}%");
                    });
            })
            ->select('id', 'po_num', 'po_job_num', 'vendor_id', 'po_date')
            ->limit(10)
            ->get()
            ->map(function ($item) {
                return [
                    'id' => $item->id,
                    'po_num' => $item->po_num,
                    'po_job_num' => $item->po_job_num,
                    'vendor_name' => $item->vendor ? $item->vendor->name : 'N/A',
                    'po_date' => $item->po_date
                ];
            });

        return response()->json($results);
    }

    public function get_packing_po_details(Request $request)
    {
        $po = PoMaster::with('vendor')->find($request->input('id'));

        if (!$po) {
            return response()->json(['error' => 'PO not found'], 404);
        }

        return response()->json([
            'po_num' => $po->po_num,
            'po_job_num' => $po->po_job_num,
            'po_date_formatted' => $po->po_date,
            'vendor_name' => $po->vendor ? $po->vendor->name : 'N/A',
            'excess' => $po->vendor->excess,
            'vendor_id' => $po->vendor_id
        ]);
    }

    public function get_po_locations(Request $request)
    {
        $poId = $request->input('po_id');

        $locations = PoItems::where('po_id', $poId)
            ->distinct()
            ->pluck('location')
            ->filter()
            ->values();

        return response()->json($locations);
    }

    public function get_location_colors(Request $request)
    {
        $poId = $request->input('po_id');
        $location = $request->input('location');

        $colors = PoItems::where('po_id', $poId)
            ->where('location', $location)
            ->distinct()
            ->pluck('color')
            ->filter()
            ->values();

        return response()->json($colors);
    }

    public function get_po_colors(Request $request)
    {
        $poId = $request->input('po_id');

        $colors = PackingListConfigItem::whereHas('config', function ($query) use ($poId) {
            $query->where('po_id', $poId);
        })
            ->distinct()
            ->pluck('color')
            ->filter()
            ->values();

        return response()->json($colors);
    }

    public function get_po_styles(Request $request)
    {
        $poId = $request->input('po_id');

        $styles = PoItems::where('po_id', $poId)
            ->distinct()
            ->pluck('article_number')
            ->filter()
            ->values();

        return response()->json($styles);
    }

    public function get_style_colors(Request $request)
    {
        $poId = $request->input('po_id');
        $articleNumber = $request->input('article_number');

        $colors = PoItems::where('po_id', $poId)
            ->where('article_number', $articleNumber)
            ->distinct()
            ->pluck('color')
            ->filter()
            ->values();

        return response()->json($colors);
    }

    public function get_po_countries(Request $request)
    {
        $poId = $request->input('po_id');
        $articleNumber = $request->input('article_number');

        $countries = PoItems::where('po_id', $poId)
            ->where('article_number', $articleNumber)
            ->distinct()
            ->pluck('country')
            ->filter()
            ->values();

        return response()->json($countries);
    }

    public function get_country_colors(Request $request)
    {
        $poId = $request->input('po_id');
        $articleNumber = $request->input('article_number');
        $country = $request->input('country');

        $colors = PoItems::where('po_id', $poId)
            ->where('article_number', $articleNumber)
            ->where('country', $country)
            ->distinct()
            ->pluck('color')
            ->filter()
            ->values();

        return response()->json($colors);
    }

    public function get_sizes_with_qty(Request $request)
    {
        $poId     = $request->input('po_id');
        $article  = $request->input('article_number');
        $color    = $request->input('color');
        $location = $request->input('location');
        $country  = $request->input('country'); // Add country parameter

        // Fetch the PO (to get vendor_id)
        $po = PoMaster::with('vendor')->find($poId);
        if (! $po) {
            return response()->json(['error' => 'PO not found'], 404);
        }

        $vendorId = $po->vendor_id;
        $sizes = [];

        if ($vendorId == 2) {
            // Vendor 2 logic - get from PackingListConfigItem, group by size
            $configItems = PackingListConfigItem::whereHas('config', function ($q) use ($poId) {
                $q->where('po_id', $poId);
            })
                ->whereHas('poItem', function ($q) use ($article, $color, $country) {
                    $q->where('article_number', $article)
                        ->where('color', $color)
                        ->where('country', $country);
                })
                ->with(['poItem', 'config'])
                ->get();

            // Group by size and sum pack_qty
            $groupedSizes = $configItems->groupBy('size');

            foreach ($groupedSizes as $size => $items) {
                // Sum all pack_qty for this size
                $maxQty = $items->sum('pack_qty');

                // Calculate packed quantity for this size
                $packedQty = PackingListItem::whereHas('packingList', function ($q) use ($poId) {
                    $q->where('po_id', $poId);
                })
                    ->where('article_number', $article)
                    ->where('color', $color)
                    ->where('country', $country)
                    ->where('size', $size)
                    ->sum('quantity');

                $remainingQty = $maxQty - $packedQty;
                if ($remainingQty <= 0) {
                    continue;
                }

                $sizes[] = [
                    'size'           => $size,
                    'max_qty'        => $maxQty,
                    'packed_qty'     => $packedQty,
                    'remaining_qty'  => $remainingQty,
                    'config_item_id' => $items->first()->id,
                    'po_item_id'     => $items->first()->po_item_id,
                ];
            }
        } elseif ($vendorId == 4) {
            // Existing vendor 4 logic...
            $configItems = PackingListConfigItem::where('po_id', $poId)
                ->where('vendor_id', 4)
                ->where('color', $color)
                ->get();

            foreach ($configItems as $item) {
                $size   = $item->size;
                $maxQty = $item->pack_qty;

                $packedQty = PackingListItem::whereHas('packingList', function ($q) use ($poId) {
                    $q->where('po_id', $poId);
                })
                    ->where('color', $color)
                    ->where('size', $size)
                    ->sum('quantity');

                $remainingQty = $maxQty - $packedQty;
                if ($remainingQty <= 0) {
                    continue;
                }

                $sizes[] = [
                    'size'           => $size,
                    'max_qty'        => $maxQty,
                    'packed_qty'     => $packedQty,
                    'remaining_qty'  => $remainingQty,
                    'config_item_id' => $item->id,
                ];
            }
        } elseif ($vendorId == 7) {
            // Vendor 7 logic - directly from po_items
            $poItems = PoItems::where('po_id', $poId)
                ->where('color', $color)
                ->where('location', $location)
                ->where('article_number', $article)
                ->get();

            foreach ($poItems as $poItem) {
                $maxQty = $poItem->qty;

                $packedQty = PackingListItem::whereHas('packingList', function ($q) use ($poId, $location) {
                    $q->where('po_id', $poId)->where('location', $location);
                })
                    ->where('article_number', $article)
                    ->where('color', $color)
                    ->where('size', $poItem->size)
                    ->sum('quantity');

                $remainingQty = $maxQty - $packedQty;
                if ($remainingQty <= 0) {
                    continue;
                }

                $sizes[] = [
                    'size'           => $poItem->size,
                    'max_qty'        => $maxQty,
                    'packed_qty'     => $packedQty,
                    'remaining_qty'  => $remainingQty,
                    'config_item_id' => null, // No config for vendor 7
                    'po_item_id'     => $poItem->id,
                ];
            }
        } else {
            // Existing other vendors logic...
            $configItems = PackingListConfigItem::whereHas('config', function ($q) use ($poId) {
                $q->where('po_id', $poId);
            })
                ->whereHas('poItem', function ($q) use ($article) {
                    $q->where('article_number', $article);
                })
                ->with(['poItem', 'config'])
                ->get();

            foreach ($configItems as $item) {
                $maxQty = $item->pack_qty;

                $packedQty = PackingListItem::whereHas('packingList', function ($q) use ($poId) {
                    $q->where('po_id', $poId);
                })
                    ->where('article_number', $article)
                    ->where('size', $item->size)
                    ->sum('quantity');

                $remainingQty = $maxQty - $packedQty;
                if ($remainingQty <= 0) {
                    continue;
                }

                $sizes[] = [
                    'size'           => $item->size,
                    'max_qty'        => $maxQty,
                    'packed_qty'     => $packedQty,
                    'remaining_qty'  => $remainingQty,
                    'config_item_id' => $item->id,
                ];
            }
        }

        return response()->json($sizes);
    }

    public function get_packing_list_items(Request $request)
    {
        $poId = $request->input('po_id');
        $color = $request->input('color');
        $location = $request->input('location'); // For vendor 7
        $articleNumber = $request->input('article_number'); // For vendor 2
        $country = $request->input('country'); // For vendor 2

        // Get the PO to check vendor
        $po = PoMaster::find($poId);
        if (!$po) {
            return response()->json([
                'packing_lists' => [],
                'can_add_items' => false,
                'error' => 'PO not found'
            ], 404);
        }

        $vendorId = $po->vendor_id;

        // Build query based on vendor type
        $query = PackingListMaster::where('po_id', $poId)
            ->where('color', $color);

        // Filter by location for vendor 7
        if ($vendorId == 7 && $location) {
            $query->where('location', $location);
        }

        // Filter by article_number and country for vendor 2
        if ($vendorId == 2 && $articleNumber) {
            $query->whereHas('items', function ($q) use ($articleNumber, $country) {
                $q->where('article_number', $articleNumber);
                if ($country) {
                    $q->where('country', $country);
                }
            });
        }

        $packingLists = $query->orderBy('created_at', 'desc')->get();

        if ($packingLists->isEmpty()) {
            return response()->json([
                'packing_lists' => [],
                'can_add_items' => true
            ]);
        }

        $allPackingListsData = [];
        foreach ($packingLists as $packingList) {
            $itemsQuery = PackingListItem::where('packing_list_id', $packingList->id);

            // Filter items by article_number and country for vendor 2
            if ($vendorId == 2 && $articleNumber) {
                $itemsQuery->where('article_number', $articleNumber);
                if ($country) {
                    $itemsQuery->where('country', $country);
                }
            }

            $items = $itemsQuery->get()->map(function ($item) {
                return [
                    'id' => $item->id,
                    'carton_name' => $item->carton_name ?? 'N/A',
                    'article_number' => $item->article_number,
                    'color' => $item->color,
                    'size' => $item->size,
                    'quantity' => $item->quantity,
                    'carton_id' => $item->carton_id,
                    'country' => $item->country ?? 'N/A', // Include country in response
                ];
            });

            $allPackingListsData[] = [
                'packing_list_id' => $packingList->id,
                'pack_ref_no' => $packingList->pack_ref_no,
                'pack_status' => $packingList->pack_status,
                'items' => $items
            ];
        }

        // Check if we can add more items for this specific color/location/article/country
        $canAddItems = $this->checkIfCanAddItems($poId, $color, $location, $articleNumber, $vendorId, $country);

        return response()->json([
            'packing_lists' => $allPackingListsData,
            'can_add_items' => $canAddItems
        ]);
    }

    private function checkIfCanAddItems($poId, $color, $location = null, $articleNumber = null, $vendorId = null, $country = null)
    {
        // Get the PO if vendor ID not provided
        if (!$vendorId) {
            $po = PoMaster::find($poId);
            if (!$po) {
                return false;
            }
            $vendorId = $po->vendor_id;
        }

        if ($vendorId == 2) {
            // For vendor 2, check po_items directly for the specific article, color, and country
            if (!$articleNumber) {
                return false;
            }

            $query = PoItems::where('po_id', $poId)
                ->where('color', $color)
                ->where('article_number', $articleNumber);

            if ($country) {
                $query->where('country', $country);
            }

            $poItems = $query->get();

            foreach ($poItems as $poItem) {
                $maxQty = $poItem->qty;

                $packedQtyQuery = PackingListItem::whereHas('packingList', function ($q) use ($poId) {
                    $q->where('po_id', $poId);
                })
                    ->where('article_number', $articleNumber)
                    ->where('color', $color)
                    ->where('size', $poItem->size);

                if ($country) {
                    $packedQtyQuery->where('country', $country);
                }

                $packedQty = $packedQtyQuery->sum('quantity');

                if ($packedQty < $maxQty) {
                    return true; // Still have items to pack
                }
            }

            return false;
        } elseif ($vendorId == 4) {
            // For vendor 4, check config items directly for the specific color
            $configItems = PackingListConfigItem::where('po_id', $poId)
                ->where('vendor_id', 4)
                ->where('color', $color)
                ->get();

            foreach ($configItems as $item) {
                $maxQty = $item->pack_qty;
                $packedQty = PackingListItem::whereHas('packingList', function ($q) use ($poId) {
                    $q->where('po_id', $poId);
                })
                    ->where('color', $color)
                    ->where('size', $item->size)
                    ->sum('quantity');

                if ($packedQty < $maxQty) {
                    return true; // Still have items to pack for this color
                }
            }
        } elseif ($vendorId == 7) {
            // For vendor 7, check po_items directly for the specific location and color
            if (!$location) {
                return false;
            }

            $poItems = PoItems::where('po_id', $poId)
                ->where('color', $color)
                ->where('location', $location)
                ->get();

            foreach ($poItems as $poItem) {
                $maxQty = $poItem->qty;

                $packedQty = PackingListItem::whereHas('packingList', function ($q) use ($poId, $location) {
                    $q->where('po_id', $poId)->where('location', $location);
                })
                    ->where('article_number', $poItem->article_number)
                    ->where('color', $color)
                    ->where('size', $poItem->size)
                    ->sum('quantity');

                if ($packedQty < $maxQty) {
                    return true; // Still have items to pack
                }
            }

            return false;
        } else {
            // For other vendors, check through config items for the specific color
            $configItems = PackingListConfigItem::whereHas('config', function ($q) use ($poId) {
                $q->where('po_id', $poId);
            })
                ->where('color', $color)
                ->with(['poItem'])
                ->get();

            foreach ($configItems as $item) {
                $maxQty = $item->pack_qty;
                $packedQty = PackingListItem::whereHas('packingList', function ($q) use ($poId) {
                    $q->where('po_id', $poId);
                })
                    ->where('article_number', $item->poItem->article_number)
                    ->where('color', $color)
                    ->where('size', $item->size)
                    ->sum('quantity');

                if ($packedQty < $maxQty) {
                    return true; // Still have items to pack for this color
                }
            }
        }

        return false; // All items are fully packed
    }

    public function edit($id)
    {
        $packingList = PackingListMaster::with('items.carton')->find($id);

        if (!$packingList) {
            return redirect()->route('packing_list_list')->with('error', 'Packing list not found');
        }

        $page_data = [
            'page_title' => "Edit Packing List",
            'page_main_title' => "Packing List",
            'page_child_title' => "Edit",
            'isSuperAdmin' => $this->isSuperAdmin,
            'packingList' => $packingList
        ];

        return view('packing_list.edit', $page_data);
    }

    public function get_packing_list_items_by_id(Request $request)
    {
        $packingListId = $request->input('packing_list_id');

        $items = PackingListItem::where('packing_list_id', $packingListId)
            ->with('carton:id')
            ->get()
            ->map(function ($item) {
                return [
                    'id' => $item->id,
                    'carton_name' => $item->carton_name ?? 'N/A',
                    'article_number' => $item->article_number,
                    'color' => $item->color,
                    'size' => $item->size,
                    'quantity' => $item->quantity,
                    'carton_id' => $item->carton_id
                ];
            });

        return response()->json($items);
    }

    public function packing_list_details(Request $request)
    {
        $id = $request->input('id');

        $packingList = PackingListMaster::with(['items.carton'])
            ->find($id);

        if (!$packingList) {
            return response()->json(['error' => 'Packing list not found'], 404);
        }

        return view('packing_list.details', compact('packingList'));
    }

    public function item_add(Request $request)
    {
        $poId          = $request->input('id');
        $vendorId      = $request->input('vendor_id');
        $color         = $request->input('color');
        $location      = $request->input('location');
        $articleNumber = $request->input('article_number'); // For vendor 2
        $country       = $request->input('country'); // For vendor 2

        $po = PoMaster::find($poId);
        if (! $po) {
            return response()->json(['error' => 'PO not found'], 404);
        }

        $job_num = $po->po_job_num;

        $packingConfig = PackingListConfigMaster::where('po_id', $poId)->first();
        if (! $packingConfig) {
            return response()->json(['error' => 'Packing list configuration not found for this PO'], 404);
        }

        $carton = CartonMaster::where('id', $packingConfig->carton_id)
            ->where('vendor_id', $vendorId)
            ->where('status', 0)
            ->first();

        $carton_id = $carton->id;

        // Check if packing list master already exists for this combination
        $existingPackingListQuery = PackingListMaster::where('po_id', $poId)
            ->where('color', $color)
            ->where('pack_status', 0);

        if ($vendorId == 7) {
            $existingPackingListQuery->where('location', $location);
        }

        $existingPackingList = $existingPackingListQuery->first();
        $existingPackingTableNo = $existingPackingList ? $existingPackingList->packing_table_no : null;
        $isFirstTime = is_null($existingPackingTableNo);

        if ($vendorId == 2) {
            // Vendor 2: Don't show article select, load sizes directly
            $articles = collect([]); // Empty for vendor 2
        } elseif ($vendorId == 4) {
            // Existing Benetton logic...
            $articles = PoItems::where('po_id', $poId)
                ->where('color', $color)
                ->pluck('article_number')
                ->unique()
                ->values();
        } elseif ($vendorId == 7) {
            // Vendor 7 logic
            $articles = PoItems::where('po_id', $poId)
                ->where('color', $color)
                ->where('location', $location)
                ->pluck('article_number')
                ->unique()
                ->values();
        } else {
            // Existing other vendors logic...
            $configItems = PackingListConfigItem::whereHas('config', function ($q) use ($poId) {
                $q->where('po_id', $poId);
            })
                ->where('color', $color)
                ->with('poItem')
                ->get();

            $filteredArticles = collect();

            $grouped = $configItems->groupBy(function ($item) {
                return $item->poItem->article_number . '|' . $item->size . '|' . $item->color;
            });

            foreach ($grouped as $group) {
                $poItem = $group->first()->poItem;
                if (! $poItem) {
                    continue;
                }

                $articleNumber  = $poItem->article_number;
                $size           = $group->first()->size;
                $poItemIds      = $group->pluck('po_item_id');
                $totalPackQty   = $group->sum('pack_qty');

                $actualPackedQty = PackingListItem::whereIn('po_item_id', $poItemIds)
                    ->where('size', $size)
                    ->sum('quantity');

                if ($actualPackedQty < $totalPackQty) {
                    $filteredArticles->push($articleNumber);
                }
            }

            $articles = $filteredArticles->unique()->values();
        }

        return view('packing_list.item_add', compact(
            'carton_id',
            'poId',
            'color',
            'location',
            'articles',
            'job_num',
            'vendorId',
            'articleNumber',
            'country',
            'isFirstTime',
            'existingPackingTableNo'
        ));
    }

    public function item_store(Request $request)
    {
        $carton_data = $request->input('cartondata');
        $po_details = $request->input('po_details');

        $po_id = $po_details['po_id'];
        $color = $po_details['color'];
        $location = $po_details['location'] ?? null;
        $net_weight = $po_details['net_weight'];
        $carton_id = $po_details['carton_id'];
        $country = $po_details['country'] ?? null;
        $packing_table_no = $po_details['packing_table_no'] ?? null; // Get packing table number
        $article_number = $carton_data[0]['article_number'] ?? null; // Get article from first carton item

        // Fetch PO early so we know vendor_id
        $po = PoMaster::with('vendor')->find($po_id);

        $selected_color = $color;
        if (! $po) {
            return response()->json(['error' => 'PO not found'], 404);
        }
        $vendorId = $po->vendor_id;

        // Common validation rules
        $rules = [
            'po_details.po_id'          => 'required|exists:po_masters,id',
            'po_details.carton_id'      => 'required|exists:carton_master,id',
            'cartondata.*.article_number' => 'required|string',
            'po_details.color'          => 'required|string',
            'po_details.packing_table_no' => 'required|in:1,2', // Validate packing table number
        ];

        if ($vendorId == 7) {
            $rules['po_details.location'] = 'required|string';
        }

        if ($vendorId == 2) {
            $rules['po_details.country'] = 'required|string';
        }

        $rules['cartondata.*.sizes']     = 'required|array|min:1';
        $rules['cartondata.sizes.size,*'] = 'required|string';
        $rules['cartondata.sizes.quantity.*'] = 'required|integer|min:1';

        $validated = $request->validate($rules);

        try {
            // Count existing packing lists for this PO only (all colors)
            $existingCount = PackingListMaster::where('po_id', $po_id)
                ->count();
            $suffix = $existingCount + 1;
            $generatedPackRefNo = "{$po->po_job_num}/{$suffix}";

            // Create or fetch PackingListMaster based on po_id, color, location, and pack_status
            $searchCriteria = [
                'po_id' => $po_id,
                'color' => $selected_color,
                'pack_status' => 0
            ];

            $createData = [
                'pack_ref_no' => $generatedPackRefNo,
                'vendor_id'  => $po->vendor_id,
                'po_no'      => $po->po_num,
                'po_date'    => $po->po_date,
                'color' => $selected_color,
                'packing_table_no' => $packing_table_no, // Add packing table number
                'created_by' => auth()->user()->id,
                'created_at' => now(),
            ];

            // Include location for vendor 2
            if ($vendorId == 2) {
                if ($article_number) {
                    $searchCriteria['article_number'] = $article_number;
                    $createData['article_number'] = $article_number;
                }
                if ($country) {
                    $searchCriteria['country'] = $country;
                    $createData['country'] = $country;
                }
            }

            // Include location for vendor 7
            if ($vendorId == 7) {
                $searchCriteria['location'] = $location;
                $createData['location'] = $location;
            }

            $packingList = PackingListMaster::firstOrCreate(
                $searchCriteria,
                $createData
            );

            $currentCartonNumber = $this->getNextCartonNumber($vendorId, $packingList->id);
            $cartonName = $this->formatCartonName($vendorId, $currentCartonNumber);
            $createdAt = now();

            foreach ($carton_data as $carton) {
                $sizes = $carton['sizes'];

                // Loop each size entry
                foreach ($sizes as $idx => $size) {
                    $qty = $size['quantity'];
                    $configItemId = $size['config_item_id'] ?? null;
                    $poItemId = $size['po_item_id'] ?? null;

                    // Check remaining qty for this size/color based on vendor
                    if ($vendorId == 2) {
                        // Vendor 2 logic - get from PackingListConfigItem and sum by size
                        $configItemsQuery = PackingListConfigItem::whereHas('config', function ($q) use ($po_id) {
                            $q->where('po_id', $po_id);
                        })
                            ->whereHas('poItem', function ($q) use ($carton, $color, $country) {
                                $q->where('article_number', $carton['article_number'])
                                    ->where('color', $color);
                                if ($country) {
                                    $q->where('country', $country);
                                }
                            })
                            ->where('size', $size['size']);

                        $configItems = $configItemsQuery->get();

                        if ($configItems->isEmpty()) {
                            return response()->json(['error' => "Size {$size['size']} not found in configuration"], 400);
                        }

                        // Sum all pack_qty for this size
                        $maxQty = $configItems->sum('pack_qty');

                        // Calculate already packed quantity
                        $alreadyPackedQuery = PackingListItem::whereHas('packingList', function ($q) use ($po_id) {
                            $q->where('po_id', $po_id);
                        })
                            ->where('article_number', $carton['article_number'])
                            ->where('color', $color)
                            ->where('size', $size['size']);

                        if ($country) {
                            $alreadyPackedQuery->where('country', $country);
                        }

                        $alreadyPacked = $alreadyPackedQuery->sum('quantity');

                        // Get po_item for creation
                        $poItemQuery = PoItems::where('po_id', $po_id)
                            ->where('article_number', $carton['article_number'])
                            ->where('color', $color)
                            ->where('size', $size['size']);

                        if ($country) {
                            $poItemQuery->where('country', $country);
                        }

                        $poItemForCreate = $poItemQuery->first();
                    } elseif ($vendorId == 4) {
                        // Vendor 4 (Benetton) logic
                        $poSize = PackingListConfigItem::where('po_id', $po_id)
                            ->where('vendor_id', 4)
                            ->where('color', $color)
                            ->where('size', $size['size'])
                            ->first();
                        if (! $poSize) {
                            return response()->json(['error' => "Size {$size['size']} not found in PoSizes"], 400);
                        }
                        $maxQty = $poSize->pack_qty;

                        $alreadyPacked = PackingListItem::whereHas('packingList', function ($q) use ($po_id) {
                            $q->where('po_id', $po_id);
                        })
                            ->where('color', $color)
                            ->where('size', $size['size'])
                            ->sum('quantity');

                        $poItemForCreate = PoItems::where('po_id', $po_id)
                            ->where('article_number', $carton['article_number'])
                            ->where('color', $color)
                            ->where('size', $size['size'])
                            ->first();
                    } elseif ($vendorId == 7) {
                        // Vendor 7 logic - direct from po_items
                        $poItem = PoItems::where('po_id', $po_id)
                            ->where('article_number', $carton['article_number'])
                            ->where('color', $color)
                            ->where('location', $location)
                            ->where('size', $size['size'])
                            ->first();

                        if (!$poItem) {
                            return response()->json(['error' => "Item not found for size {$size['size']} in location {$location}"], 400);
                        }

                        $maxQty = $poItem->qty;

                        $alreadyPacked = PackingListItem::whereHas('packingList', function ($q) use ($po_id, $location) {
                            $q->where('po_id', $po_id);
                            if ($location) {
                                $q->where('location', $location);
                            }
                        })
                            ->where('article_number', $carton['article_number'])
                            ->where('color', $color)
                            ->where('size', $size['size'])
                            ->sum('quantity');

                        $poItemForCreate = $poItem;
                    } else {
                        // Other vendors logic
                        $configItem = PackingListConfigItem::find($configItemId);
                        if (! $configItem) {
                            return response()->json(['error' => "Configuration item not found for size {$size['size']}"], 400);
                        }
                        $maxQty = $configItem->pack_qty;

                        $alreadyPacked = PackingListItem::whereHas('packingList', function ($q) use ($po_id) {
                            $q->where('po_id', $po_id);
                        })
                            ->where('article_number', $carton['article_number'])
                            ->where('size', $size['size'])
                            ->sum('quantity');

                        $poItemForCreate = PoItems::where('po_id', $po_id)
                            ->where('article_number', $carton['article_number'])
                            ->where('color', $color)
                            ->where('size', $size['size'])
                            ->first();
                    }

                    $remaining = $maxQty - $alreadyPacked;

                    if ($qty > $remaining) {
                        return response()->json([
                            'error' => "Quantity for size {$size['size']} exceeds available limit. Available: {$remaining}, Requested: {$qty}"
                        ], 400);
                    }

                    // Create the PackingListItem
                    $itemData = [
                        'packing_list_id' => $packingList->id,
                        'vendor_id'       => $po->vendor_id,
                        'po_item_id'      => ($vendorId == 4) ? null : ($poItemForCreate->id ?? null),
                        'carton_id'       => $carton_id,
                        'carton_name'     => $cartonName,
                        'article_number'  => $carton['article_number'],
                        'color'           => $color,
                        'size'            => $size['size'],
                        'quantity'        => $qty,
                        'net_weight'      => $net_weight,
                        'created_by'      => auth()->user()->id,
                        'created_at'      => $createdAt,
                    ];

                    // Add country for vendor 2
                    if ($vendorId == 2 && $country) {
                        $itemData['country'] = $country;
                    }

                    PackingListItem::create($itemData);
                }
            }

            return response()->json([
                'success' => true,
                'po_id'   => $po_id,
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    // Helper methods unchanged:
    private function getNextCartonNumber($vendorId, $packingListId)
    {
        if ($vendorId == 1) {
            $prefix = 'C';
            $lastCarton = PackingListItem::where('packing_list_id', $packingListId)
                ->where('carton_name', 'like', $prefix . '%')
                ->orderByRaw('CAST(SUBSTRING(carton_name, 2) AS UNSIGNED) DESC')
                ->first();

            return $lastCarton
                ? ((int) substr($lastCarton->carton_name, 1)) + 1
                : 1;
        } else {
            $lastCarton = PackingListItem::where('packing_list_id', $packingListId)
                ->whereRaw('carton_name REGEXP "^[0-9]+$"')
                ->orderByRaw('CAST(carton_name AS UNSIGNED) DESC')
                ->first();

            return $lastCarton
                ? ((int) $lastCarton->carton_name) + 1
                : 1;
        }
    }

    private function formatCartonName($vendorId, $number)
    {
        if ($vendorId == 1) {
            return 'C' . $number;
        } else {
            return (string) $number;
        }
    }

    public function item_edit(Request $request)
    {
        $itemId = $request->input('id');
        $poId   = $request->input('po_id');

        $po = PoMaster::find($poId);
        if (! $po) {
            return response()->json(['error' => 'PO not found'], 404);
        }
        $vendorId = $po->vendor_id;
        $job_num  = $po->po_job_num;

        $item = PackingListItem::find($itemId);
        if (! $item) {
            return response()->json(['error' => 'Item not found'], 404);
        }

        // Determine color
        $color = $item->color;

        // Get cartons
        $configuredCartonIds = PackingListConfigMaster::where('po_id', $poId)
            ->pluck('carton_id')->unique();
        $carton = CartonMaster::whereIn('id', $configuredCartonIds)
            ->where('vendor_id', $vendorId)
            ->where('status', 0)
            ->first();
        $carton_id = $carton->id;

        // Build articles list
        if ($vendorId == 4) {
            // from PoItems
            $articles = PoItems::where('po_id', $poId)
                ->where('color', $color)
                ->pluck('article_number')
                ->unique()->values();
        } else {
            // existing config logic
            $configItems = PackingListConfigItem::whereHas('config', fn($q) => $q->where('po_id', $poId))
                ->where('color', $color)->with('poItem')->get();

            $filtered = $configItems
                ->groupBy(fn($i) => "{$i->poItem->article_number}|{$i->size}|{$i->color}")
                ->flatMap(function ($group) use ($item) {
                    $poItem     = $group->first()->poItem;
                    $ids        = $group->pluck('po_item_id');
                    $totalPack  = $group->sum('pack_qty');
                    $packedQty  = PackingListItem::whereIn('po_item_id', $ids)
                        ->where('size', $group->first()->size)
                        ->sum('quantity');
                    return $packedQty < $totalPack ? [$poItem->article_number] : [];
                });
            $articles = collect($filtered)->unique()->values();
        }

        // Get config_item_id for the current item
        $configItemId = null;
        if ($vendorId == 4) {
            $configItemId = PackingListConfigItem::where('po_id', $poId)
                ->where('vendor_id', 4)
                ->where('color', $color)
                ->where('size', $item->size)
                ->first()->id ?? null;
        } else {
            $configItemId = PackingListConfigItem::where('po_id', $poId)
                ->where('color', $color)
                ->where('size', $item->size)
                ->whereHas('poItem', function ($q) use ($item) {
                    $q->where('article_number', $item->article_number);
                })
                ->first()->id ?? null;
        }

        // Calculate max available quantity for current item
        if ($vendorId == 4) {
            $poSize = PackingListConfigItem::where('po_id', $poId)
                ->where('vendor_id', 4)
                ->where('color', $color)
                ->where('size', $item->size)
                ->first();

            $maxQty = $poSize ? $poSize->pack_qty : 0;
            $packedQty = PackingListItem::whereHas('packingList', fn($q) => $q->where('po_id', $poId))
                ->where('color', $color)
                ->where('size', $item->size)
                ->sum('quantity');

            $maxAvailableQty = $maxQty - $packedQty;
            $currentSizeData = [
                'max_qty' => $maxQty,
                'packed_qty' => $packedQty,
                'remaining_qty' => $maxAvailableQty,
                'config_item_id' => $configItemId
            ];
        } else {
            $configItem = PackingListConfigItem::where('po_id', $poId)
                ->where('vendor_id', $item->vendor_id)
                ->where('color', $color)
                ->where('size', $item->size)
                ->first();

            $maxQty = $configItem ? $configItem->pack_qty : 0;
            $packedQty = PackingListItem::whereHas('packingList', fn($q) => $q->where('po_id', $poId))
                ->where('article_number', $item->article_number)
                ->where('size', $item->size)
                ->sum('quantity');

            $maxAvailableQty = $maxQty - $packedQty;
            $currentSizeData = [
                'max_qty' => $maxQty,
                'packed_qty' => $packedQty,
                'remaining_qty' => $maxAvailableQty,
                'config_item_id' => $configItemId
            ];
        }

        return view('packing_list.item_edit', compact(
            'carton_id',
            'articles',
            'poId',
            'item',
            'color',
            'job_num',
            'maxAvailableQty',
            'currentSizeData',
            'configItemId'
        ));
    }

    public function getAvailableSizes(Request $request)
    {
        $poId = $request->input('po_id');
        $color = $request->input('color');
        $articleNumber = $request->input('article_number');

        $po = PoMaster::find($poId);
        if (!$po) {
            return response()->json(['error' => 'PO not found'], 404);
        }

        $vendorId = $po->vendor_id;

        if ($vendorId == 4) {
            $sizes = PackingListConfigItem::where('po_id', $poId)
                ->where('vendor_id', 4)
                ->where('color', $color)
                ->select('size')
                ->distinct()
                ->get();
        } else {
            $sizes = PackingListConfigItem::whereHas('config', fn($q) => $q->where('po_id', $poId))
                ->whereHas('poItem', fn($q) => $q->where('article_number', $articleNumber))
                ->where('color', $color)
                ->select('size')
                ->distinct()
                ->get();
        }

        return response()->json([
            'success' => true,
            'sizes' => $sizes
        ]);
    }

    public function checkSizeAvailability(Request $request)
    {
        $poId = $request->input('po_id');
        $color = $request->input('color');
        $articleNumber = $request->input('article_number');
        $size = $request->input('size');
        $itemId = $request->input('item_id');

        $po = PoMaster::find($poId);
        if (!$po) {
            return response()->json(['error' => 'PO not found'], 404);
        }

        $vendorId = $po->vendor_id;

        if ($vendorId == 4) {
            $configItem = PackingListConfigItem::where('po_id', $poId)
                ->where('vendor_id', 4)
                ->where('color', $color)
                ->where('size', $size)
                ->first();

            if (!$configItem) {
                return response()->json(['success' => false, 'message' => 'Size configuration not found']);
            }

            $maxQty = $configItem->pack_qty;
            $packedQty = PackingListItem::whereHas('packingList', fn($q) => $q->where('po_id', $poId))
                ->where('color', $color)
                ->where('size', $size)
                ->where('id', '!=', $itemId)
                ->sum('quantity');
        } else {
            $configItem = PackingListConfigItem::whereHas('config', fn($q) => $q->where('po_id', $poId))
                ->whereHas('poItem', fn($q) => $q->where('article_number', $articleNumber))
                ->where('color', $color)
                ->where('size', $size)
                ->first();

            if (!$configItem) {
                return response()->json(['success' => false, 'message' => 'Size configuration not found']);
            }

            $maxQty = $configItem->pack_qty;
            $packedQty = PackingListItem::whereHas('packingList', fn($q) => $q->where('po_id', $poId))
                ->where('article_number', $articleNumber)
                ->where('size', $size)
                ->where('id', '!=', $itemId)
                ->sum('quantity');
        }

        $remainingQty = $maxQty - $packedQty;

        return response()->json([
            'success' => true,
            'data' => [
                'max_qty' => $maxQty,
                'packed_qty' => $packedQty,
                'remaining_qty' => $remainingQty,
                'config_item_id' => $configItem->id
            ]
        ]);
    }

    public function item_update(Request $request)
    {
        // Fetch PO to know vendor
        $po = PoMaster::find($request->input('po_id'));
        if (! $po) {
            return response()->json(['error' => 'PO not found'], 404);
        }
        $vendorId = $po->vendor_id;

        // Validation rules
        $rules = [
            'id'       => 'required|exists:packing_list_items,id',
            'po_id'    => 'required|exists:po_masters,id',
            'size' => 'required',
            'quantity' => 'required|integer|min:1',
        ];

        // Add config_item_id validation for non-vendor 4
        if ($vendorId != 4) {
            $rules['config_item_id'] = 'required|exists:packing_list_config_items,id';
        }

        $validated = $request->validate($rules);

        try {
            $item = PackingListItem::find($validated['id']);
            if (! $item) {
                return response()->json(['error' => 'Item not found'], 404);
            }

            // Determine the maximum allowed for this item
            $size = $item->size;
            $color = $item->color;
            $articleNumber = $item->article_number;

            if ($vendorId == 4) {
                $ps = PackingListConfigItem::where('po_id', $validated['po_id'])
                    ->where('vendor_id', 4)
                    ->where('color', $color)
                    ->where('size', $size)
                    ->first();

                if (! $ps) {
                    return response()->json(['error' => 'Size not found in PoSizes'], 400);
                }

                $maxQty = $ps->pack_qty;

                // Sum of all other items of same size/color in this PO (excluding current item)
                $packedOthers = PackingListItem::whereHas('packingList', function ($q) use ($validated) {
                    $q->where('po_id', $validated['po_id']);
                })
                    ->where('color', $color)
                    ->where('size', $size)
                    ->where('id', '!=', $item->id)
                    ->sum('quantity');
            } else {
                // Use the config_item_id from the request
                $configItem = PackingListConfigItem::find($validated['config_item_id']);
                if (! $configItem) {
                    return response()->json(['error' => 'Configuration item not found'], 400);
                }

                $maxQty = $configItem->pack_qty;

                $packedOthers = PackingListItem::whereHas('packingList', function ($q) use ($validated) {
                    $q->where('po_id', $validated['po_id']);
                })
                    ->where('article_number', $articleNumber)
                    ->where('size', $size)
                    ->where('id', '!=', $item->id)
                    ->sum('quantity');
            }

            $remaining = $maxQty - $packedOthers;

            if ($validated['quantity'] > $remaining) {
                return response()->json([
                    'error' => "Quantity exceeds available limit. Available: {$remaining}"
                ], 400);
            }

            // Update the item
            $updateData = [
                'size' => $validated['size'],
                'quantity' => $validated['quantity'],
            ];

            // Update config_item_id for non-vendor 4
            if ($vendorId != 4) {
                $updateData['config_item_id'] = $validated['config_item_id'];
            }

            $item->update($updateData);

            return response()->json([
                'success' => true,
                'message' => 'Item updated successfully',
                'po_id'   => $validated['po_id'],
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => 'An error occurred while updating the item'], 500);
        }
    }

    private function generateCartonName($vendorId, $packingListId, $color, $size, $articleNumber)
    {
        if ($vendorId == 1) {
            // First, check if a carton already exists with the same size and article number
            $existingCarton = PackingListItem::where('packing_list_id', $packingListId)
                ->where('color', $color)
                ->where('size', $size)
                ->where('article_number', $articleNumber)
                ->first();

            if ($existingCarton) {
                // Reuse the existing carton name
                return $existingCarton->carton_name;
            }

            // If not found, generate a new carton name with prefix 'C'
            $prefix = 'C';

            $lastCarton = PackingListItem::where('packing_list_id', $packingListId)
                ->where('carton_name', 'like', 'C%')
                ->orderByRaw('CAST(SUBSTRING(carton_name, 2) AS UNSIGNED) DESC')
                ->first();

            $nextNumber = $lastCarton ? ((int) substr($lastCarton->carton_name, 1)) + 1 : 1;

            return $prefix . $nextNumber;
        } else {
            // Other vendors – generate purely numeric carton name
            $lastCarton = PackingListItem::where('packing_list_id', $packingListId)
                ->whereRaw('carton_name REGEXP "^[0-9]+$"')
                ->orderByRaw('CAST(carton_name AS UNSIGNED) DESC')
                ->first();

            $nextNumber = $lastCarton ? ((int) $lastCarton->carton_name) + 1 : 1;

            return (string) $nextNumber;
        }
    }

    public function item_delete(Request $request)
    {
        try {
            $item = PackingListItem::find($request->id);

            if (!$item) {
                return response()->json(['error' => 'Item not found'], 404);
            }

            $packingList = PackingListMaster::find($item->packing_list_id);
            $itemCount = PackingListItem::where('packing_list_id', $packingList->id)->count();

            if ($itemCount <= 1) {
                return response()->json(['error' => 'Cannot delete the last item'], 400);
            }

            $item->delete();

            return response()->json(['success' => true]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function po_print($id)
    {
        // D-Mart (vendor_id 8) has its own print layout/logic — hand off immediately.
        $vendorCheck = PackingListMaster::find($id);
        if ($vendorCheck && $vendorCheck->vendor_id == 8) {
            return app(DmartPackingListController::class)->po_print($id);
        }

        // Load the packing list with related items, carton, po_item, vendor, po
        $packingList = PackingListMaster::with([
            'items.carton',
            'items.po_item',
            'vendor',
            'po'
        ])->find($id);

        if (!$packingList) {
            abort(404, 'Packing list not found');
        }

        //
        // 1. COMMON HEADER DATA
        //

        // PO Number
        $poNum = $packingList->po->po_num ?? '';
        $poDate = $packingList->po->po_date ?? '';
        $poJobNum = $packingList->po->po_job_num ?? '';

        // Unique PO item IDs in this packing list
        $uniquePoItemIds = $packingList->items->pluck('po_item_id')->unique()->values()->toArray();

        // Unique article numbers
        $uniqueArticleNumbers = $packingList->items->pluck('article_number')->unique()->values()->toArray();
        $articleNumbersDisplay = implode(', ', $uniqueArticleNumbers);

        // Unique colors
        $uniqueColor = $packingList->items->pluck('color')->unique()->values()->toArray();
        $uniqueColorDisplay = implode(', ', $uniqueColor);

        // FIRST Carton details (for header dims/weight display if needed)
        $uniqueCarton = $packingList->items->pluck('carton_id')->unique()->values()->toArray();
        $firstCartonId = $uniqueCarton[0] ?? null;
        $firstCarton = $firstCartonId ? CartonMaster::find($firstCartonId) : null;

        $ctnLength  = $firstCarton->length ?? '';
        $ctnBreadth = $firstCarton->breadth ?? '';
        $ctnHeight  = $firstCarton->height ?? '';

        $ctnDimDisplay = '';
        if ($ctnLength !== '' || $ctnBreadth !== '' || $ctnHeight !== '') {
            $ctnDimDisplay = "{$ctnLength}X{$ctnBreadth}X{$ctnHeight}";
        }

        $ctnWeight = $firstCarton->weight ?? '';

        // Gender display from PoItems
        $genderDisplay = '';
        if (!empty($uniquePoItemIds)) {
            $genderArr = PoItems::whereIn('id', $uniquePoItemIds)
                ->pluck('gender')
                ->filter()
                ->unique()
                ->values()
                ->toArray();
            $genderDisplay = implode(', ', $genderArr);
        }

        // Style description (type) display from PoItems
        $styleDescriptionsDisplay = '';
        if (!empty($uniquePoItemIds)) {
            if ($packingList->vendor_id == 4) {
                $styleArr = PoItems::whereIn('color', $uniqueColor)
                    ->where('po_id', $packingList->po->id)
                    ->pluck('part_description')
                    ->filter()
                    ->unique()
                    ->values()
                    ->toArray();
            } else {
                $styleArr = PoItems::whereIn('id', $uniquePoItemIds)
                    ->pluck('type')
                    ->filter()
                    ->unique()
                    ->values()
                    ->toArray();
            }
            $styleDescriptionsDisplay = implode(', ', $styleArr);
        }

        // Get position-based size order from config
        $sizeOrder = [];
        if ($packingList->po_id) {
            $sizeOrder = PackingListConfigItem::where('po_id', $packingList->po_id)
                ->where('status', 0)
                ->orderBy('position', 'asc')
                ->pluck('size')
                ->unique()
                ->values()
                ->toArray();
        }

        // Fallback to PO items if no config
        if (empty($sizeOrder) && $packingList->po_id) {
            $sizeOrder = PoItems::where('po_id', $packingList->po_id)
                ->pluck('size')
                ->unique()
                ->values()
                ->toArray();
        }

        // All sizes present in packing list - ORDERED BY POSITION
        $allSizes = collect($sizeOrder);

        // Determine carton prefix based on vendor
        $cartonPrefix = in_array($packingList->vendor_id, [1, 5, 6]) ? 'C' : '';

        // Get position mapping for sizes from config
        $sizePositionMap = [];
        if ($packingList->po_id) {
            $configItems = PackingListConfigItem::where('po_id', $packingList->po_id)
                ->where('status', 0)
                ->get();

            foreach ($configItems as $configItem) {
                $sizePositionMap[$configItem->size] = [
                    'position' => $configItem->position,
                    'per_carton_qty' => $configItem->per_carton_qty
                ];
            }
        }

        // Step 1: Identify mixed cartons by grouping items from database carton_name
        $itemsByDbCartonName = $packingList->items->groupBy('carton_name');
        $mixedCartonDbNames = [];

        foreach ($itemsByDbCartonName as $dbCartonName => $items) {
            $uniqueSizes = $items->pluck('size')->unique();
            if ($uniqueSizes->count() > 1) {
                $mixedCartonDbNames[] = $dbCartonName;
            }
        }

        // Step 2: Separate pure and mixed items, and assign position to each item
        $pureItems = collect();
        $mixedItems = collect();

        foreach ($packingList->items as $item) {
            $size = $item->size;
            $position = $sizePositionMap[$size]['position'] ?? 999;
            $perCartonQty = $sizePositionMap[$size]['per_carton_qty'] ?? 80;

            $item->position = $position;
            $item->per_carton_config_qty = $perCartonQty;

            if (in_array($item->carton_name, $mixedCartonDbNames)) {
                $item->is_mixed = true;
                $item->original_carton_name = $item->carton_name;
                $mixedItems->push($item);
            } else {
                $item->is_mixed = false;
                $pureItems->push($item);
            }
        }

        // Step 3: Categorize pure items into full and under-filled cartons
        $fullCartons = collect();
        $underFilledCartons = collect();

        foreach ($pureItems as $item) {
            $perCartonQty = $item->per_carton_config_qty ?? 0;

            // Changed: Use <= instead of < to handle exact matches correctly
            if ($perCartonQty > 0 && $item->quantity < $perCartonQty) {
                // Under-filled carton
                $item->is_under_filled = true;
                $underFilledCartons->push($item);
            } else {
                // Full carton (including exact matches)
                $item->is_under_filled = false;
                $fullCartons->push($item);
            }
        }

        // Step 4: Sort full cartons by position, then by ID
        $fullCartons = $fullCartons->sortBy([
            ['position', 'asc'],
            ['id', 'asc']
        ]);

        // Step 5: Sort under-filled cartons by position, then by ID
        $underFilledCartons = $underFilledCartons->sortBy([
            ['position', 'asc'],
            ['id', 'asc']
        ]);

        // Step 6: Group items by per_carton_qty (position group)
        $groupedByPerCartonQty = collect();

        // Add full cartons to their respective groups
        foreach ($fullCartons as $item) {
            $key = $item->per_carton_config_qty;
            if (!isset($groupedByPerCartonQty[$key])) {
                $groupedByPerCartonQty[$key] = [
                    'full' => collect(),
                    'under_filled' => collect(),
                    'position' => $item->position
                ];
            }
            $groupedByPerCartonQty[$key]['full']->push($item);
        }

        // Add under-filled cartons to their respective groups
        foreach ($underFilledCartons as $item) {
            $key = $item->per_carton_config_qty;
            if (!isset($groupedByPerCartonQty[$key])) {
                $groupedByPerCartonQty[$key] = [
                    'full' => collect(),
                    'under_filled' => collect(),
                    'position' => $item->position
                ];
            }
            $groupedByPerCartonQty[$key]['under_filled']->push($item);
        }

        // Sort groups by position
        $sortedGroups = $groupedByPerCartonQty->sortBy('position');

        // Step 7: Assign dynamic carton names in order
        $cartonCounter = 1;
        $sortedPureItems = collect();

        // FIRST PASS: Process all FULL cartons across ALL position groups
        foreach ($sortedGroups as $group) {
            foreach ($group['full'] as $item) {
                $item->dynamic_carton_name = $cartonPrefix . $cartonCounter;
                $cartonCounter++;
                $sortedPureItems->push($item);
            }
        }

        // SECOND PASS: Process all UNDER-FILLED cartons across ALL position groups
        foreach ($sortedGroups as $group) {
            foreach ($group['under_filled'] as $item) {
                $item->dynamic_carton_name = $cartonPrefix . $cartonCounter;
                $cartonCounter++;
                $sortedPureItems->push($item);
            }
        }

        // Step 8: Group mixed items by their original carton_name and assign dynamic names
        $mixedByOriginalCarton = $mixedItems->groupBy('original_carton_name');

        foreach ($mixedByOriginalCarton as $originalCartonName => $items) {
            $dynamicCartonName = $cartonPrefix . $cartonCounter;

            foreach ($items as $item) {
                $item->dynamic_carton_name = $dynamicCartonName;
                $item->position = 9999; // Ensure they come last
            }

            $cartonCounter++;
        }

        // Step 9: Combine all items in the correct order
        $sortedItems = $sortedPureItems->merge($mixedItems);

        // Replace the original items collection with sorted items
        $packingList->setRelation('items', $sortedItems);

        // Packed quantities per size - ORDERED BY POSITION
        $packedQuantities = collect();
        foreach ($sizeOrder as $size) {
            $packedQuantities[$size] = $packingList->items
                ->where('size', $size)
                ->sum('quantity');
        }

        //
        // Initialize variables for summary
        //
        $orderedQuantities = collect();
        $balances = collect();
        $percentages = collect();
        $tableData = null;

        // Initialize vendor ID 4 specific totals
        $totalCtn = 0;
        $totalNetWeight = 0;
        $totalGrossWeight = 0;
        $totalCbm = null;

        //
        // 2. VENDOR-SPECIFIC LOGIC
        //
        if ($packingList->vendor_id == 2) {
            //
            // VENDOR ID 2 (Skechers-specific)
            //

            $dispatchQuantities = collect();
            $totalDispatches = 0;
            $orderQuantitiesFromAllPacks = collect();
            $currentDispatchNumber = 1;

            $allPackingLists = PackingListMaster::where('po_id', $packingList->po_id)
                ->orderBy('id', 'asc')
                ->get();

            $totalDispatches = $allPackingLists->count();

            $currentPackingListItems = $packingList->items;
            $currentColorArticleCombinations = $currentPackingListItems
                ->groupBy(function ($item) {
                    return $item->color . '|' . $item->article_number;
                })
                ->keys();

            // Initialize orderQuantitiesFromAllPacks with position order
            $orderQuantitiesFromAllPacks = collect();
            foreach ($sizeOrder as $size) {
                $orderQuantitiesFromAllPacks[$size] = 0;
            }

            foreach ($currentColorArticleCombinations as $colorArticleKey) {
                list($color, $articleNumber) = explode('|', $colorArticleKey);

                $poItemIds = PoItems::where('po_id', $packingList->po_id)
                    ->where('article_number', $articleNumber)
                    ->where('color', $color)
                    ->pluck('id')
                    ->toArray();

                if (!empty($poItemIds)) {
                    $configItems = PackingListConfigItem::where('po_id', $packingList->po_id)
                        ->whereIn('po_item_id', $poItemIds)
                        ->where('status', 0)
                        ->get();

                    foreach ($configItems as $configItem) {
                        if (in_array($configItem->size, $sizeOrder)) {
                            $currentQty = $orderQuantitiesFromAllPacks->get($configItem->size, 0);
                            $orderQuantitiesFromAllPacks[$configItem->size] = $currentQty + $configItem->po_qty;
                        }
                    }
                }
            }

            $currentPackingListIndex = $allPackingLists->search(function ($item) use ($packingList) {
                return $item->id == $packingList->id;
            });

            $currentDispatchNumber = $currentPackingListIndex + 1;

            foreach ($allPackingLists as $index => $pList) {
                if ($index <= $currentPackingListIndex) {
                    $dispatchNumber = $index + 1;
                    $packingListItems = PackingListItem::where('packing_list_id', $pList->id)->get();

                    $filteredItems = $packingListItems->filter(function ($item) use ($currentColorArticleCombinations) {
                        $key = $item->color . '|' . $item->article_number;
                        return $currentColorArticleCombinations->contains($key);
                    });

                    // Initialize sizeQuantities with position order
                    $sizeQuantities = collect();
                    foreach ($sizeOrder as $size) {
                        $sizeQuantities[$size] = 0;
                    }

                    foreach ($filteredItems as $item) {
                        if (in_array($item->size, $sizeOrder)) {
                            $currentQty = $sizeQuantities->get($item->size, 0);
                            $sizeQuantities[$item->size] = $currentQty + $item->quantity;
                        }
                    }

                    $dispatchQuantities[$dispatchNumber] = $sizeQuantities;
                }
            }

            $uniquePoItemIds = collect();
            foreach ($currentPackingListItems as $item) {
                $poItem = PoItems::where('po_id', $packingList->po_id)
                    ->where('article_number', $item->article_number)
                    ->where('color', $item->color)
                    ->where('size', $item->size)
                    ->first();

                if ($poItem) {
                    $uniquePoItemIds->push($poItem->id);
                }
            }

            $uniquePoItemIds = $uniquePoItemIds->unique()->values()->toArray();

            // Initialize orderedQuantities with position order
            $orderedQuantities = collect();
            foreach ($sizeOrder as $size) {
                $orderedQuantities[$size] = 0;
            }

            if (!empty($uniquePoItemIds)) {
                $configItems = PackingListConfigItem::whereIn('po_item_id', $uniquePoItemIds)
                    ->where('status', 0)
                    ->get();

                foreach ($configItems as $configItem) {
                    if (in_array($configItem->size, $sizeOrder)) {
                        $currentQty = $orderedQuantities->get($configItem->size, 0);
                        $orderedQuantities[$configItem->size] = $currentQty + $configItem->pack_qty;
                    }
                }
            }

            foreach ($allSizes as $size) {
                $ordered = $orderQuantitiesFromAllPacks->get($size, 0);

                $totalDispatchedForSize = 0;
                foreach ($dispatchQuantities as $dispatchQty) {
                    $totalDispatchedForSize += $dispatchQty->get($size, 0);
                }

                $balance = $ordered - $totalDispatchedForSize;
                $percentage = $ordered > 0 ? ($totalDispatchedForSize / $ordered) * 100 : 0;

                $balances[$size] = $balance;
                $percentages[$size] = $percentage;
            }

            // Separate pure and mixed cartons for processing
            $pureItemsForTable = $sortedItems->where('is_mixed', false);
            $mixedItemsForTable = $sortedItems->where('is_mixed', true);

            // Group pure carton items by position, article, color, size
            $positionGroups = [];

            foreach ($pureItemsForTable->groupBy('position') as $position => $posItems) {
                foreach (
                    $posItems->groupBy(function ($item) {
                        return $item->article_number . '|' . $item->color . '|' . $item->size;
                    }) as $key => $items
                ) {
                    list($articleNumber, $color, $size) = explode('|', $key);

                    // Get per_carton_qty from config for this SPECIFIC combination
                    $configItem = PackingListConfigItem::where('po_id', $packingList->po_id)
                        ->whereHas('poItem', function ($q) use ($articleNumber, $color, $size) {
                            $q->where('article_number', $articleNumber)
                                ->where('color', $color)
                                ->where('size', $size);
                        })
                        ->first();

                    $perCartonQty = $configItem ? $configItem->per_carton_qty : 30;

                    // CHANGED: Group ALL consecutive cartons together regardless of fill status
                    $consecutiveRanges = [];
                    $currentRange = [];
                    $lastCartonNum = null;

                    foreach ($items as $item) {
                        $currentCartonNum = intval(str_replace($cartonPrefix, '', $item->dynamic_carton_name));

                        // Start a new range only if not consecutive
                        if ($lastCartonNum === null || $currentCartonNum != $lastCartonNum + 1) {
                            if (!empty($currentRange)) {
                                $consecutiveRanges[] = ['items' => $currentRange];
                            }
                            $currentRange = [$item];
                        } else {
                            $currentRange[] = $item;
                        }

                        $lastCartonNum = $currentCartonNum;
                    }

                    if (!empty($currentRange)) {
                        $consecutiveRanges[] = ['items' => $currentRange];
                    }

                    // Create groups for each consecutive range
                    foreach ($consecutiveRanges as $rangeIndex => $range) {
                        $rangeItems = collect($range['items']);

                        // FIXED: Use the specific perCartonQty for THIS size
                        $hasUnderFilled = $rangeItems->contains(function ($item) use ($perCartonQty) {
                            return $item->quantity < $perCartonQty;
                        });

                        // Create unique key without fill status separation
                        $rangeKey = $position . '|' . $articleNumber . '|' . $color . '|' . $size . '|' . $rangeIndex;

                        $positionGroups[$rangeKey] = [
                            'position' => $position,
                            'article_number' => $articleNumber,
                            'color' => $color,
                            'size' => $size,
                            'carton_names' => $rangeItems->pluck('dynamic_carton_name')->toArray(),
                            'items' => $rangeItems,
                            'size_quantities' => [$size => $rangeItems->sum('quantity')],
                            'is_mixed' => false,
                            'is_full' => !$hasUnderFilled,
                            'per_carton_qty' => $perCartonQty
                        ];
                    }
                }
            }

            // Group mixed carton items by dynamic_carton_name
            foreach ($mixedItemsForTable->groupBy('dynamic_carton_name') as $cartonName => $items) {
                $rangeKey = '9999|mixed|' . $cartonName;

                $sizeQuantities = [];
                foreach ($items as $item) {
                    $sizeQuantities[$item->size] = ($sizeQuantities[$item->size] ?? 0) + $item->quantity;
                }

                $firstItem = $items->first();

                $positionGroups[$rangeKey] = [
                    'position' => 9999,
                    'article_number' => $firstItem->article_number,
                    'color' => $firstItem->color,
                    'size' => 'Mixed',
                    'carton_names' => [$cartonName],
                    'items' => $items,
                    'size_quantities' => $sizeQuantities,
                    'is_mixed' => true,
                    'is_full' => false
                ];
            }

            // Create table rows
            $tableRows = [];
            $totals = [
                'carton_count' => 0,
                'per_size'     => array_fill_keys($sizeOrder, 0),
                'total_pieces' => 0,
                'total_net_weight' => 0,
                'total_gross_weight' => 0
            ];

            foreach ($positionGroups as $group) {
                $cartonNames = $group['carton_names'];
                $cartonCount = count($cartonNames);
                $allItems = $group['items'];

                $firstName = $cartonNames[0];
                $lastName = end($cartonNames);

                $ctnRange = $cartonCount > 1 ? $firstName . '-' . $lastName : $firstName;

                $totalQty = $allItems->sum('quantity');

                $firstItem = $allItems->first();
                $carton = $firstItem->carton;

                $netWeightPerCarton = $firstItem->net_weight ?? 0;
                $grossWeightPerCarton = ($firstItem->net_weight ?? 0) + 1.50;

                $totalNetWeightForRange = $netWeightPerCarton * $cartonCount;
                $totalGrossWeightForRange = $grossWeightPerCarton * $cartonCount;

                $dimension = '';
                if (
                    ($carton->length ?? 0) > 0
                    || ($carton->breadth ?? 0) > 0
                    || ($carton->height ?? 0) > 0
                ) {
                    $dimension = ($carton->length ?? 0)
                        . '*' . ($carton->breadth ?? 0)
                        . '*' . ($carton->height ?? 0)
                        . ' CMS';
                }

                $perCartonQty = $cartonCount > 0 ? round($totalQty / $cartonCount) : 0;

                $poItem = PoItems::where('po_id', $packingList->po_id)
                    ->where('article_number', $firstItem->article_number)
                    ->where('color', $firstItem->color)
                    ->first();

                $mrp = $poItem->mrp ?? '';
                $poItemId = $poItem->id ?? $firstItem->po_item_id;

                $row = [
                    'article_number'  => $group['article_number'],
                    'color'           => $group['color'],
                    'ctn_range'       => $ctnRange,
                    'ctn_first'       => $firstName,
                    'ctn_last'        => $lastName,
                    'first_carton_id' => $cartonNames[0],
                    'ttl_ctn'         => $cartonCount,
                    'per_size'        => [],
                    'per_ctn'         => $perCartonQty,
                    'total'           => $totalQty,
                    'net_wt_per'      => $netWeightPerCarton,
                    'grs_wt_per'      => $grossWeightPerCarton,
                    'net_wt_total'    => $totalNetWeightForRange,
                    'grs_wt_total'    => $totalGrossWeightForRange,
                    'ctn_dim'         => $dimension,
                    'mrp'             => $mrp,
                    'po_item_id'      => $poItemId,
                    'position'        => $group['position'],
                    'is_mixed'        => $group['is_mixed'],
                    'is_full'         => $group['is_full'] ?? false
                ];

                foreach ($sizeOrder as $sizeCol) {
                    $row['per_size'][$sizeCol] = 0;
                }

                foreach ($group['size_quantities'] as $size => $qty) {
                    if (in_array($size, $sizeOrder)) {
                        $row['per_size'][$size] = $qty;
                    }
                }

                $totals['carton_count'] += $cartonCount;
                foreach ($group['size_quantities'] as $size => $qty) {
                    if (isset($totals['per_size'][$size])) {
                        $totals['per_size'][$size] += $qty;
                    }
                }
                $totals['total_pieces'] += $totalQty;
                $totals['total_net_weight'] += $totalNetWeightForRange;
                $totals['total_gross_weight'] += $totalGrossWeightForRange;

                $tableRows[] = $row;
            }

            // Sort: Keep the natural order from dynamic carton names (already correct from earlier sorting)
            // Just extract the first carton number and sort by that
            usort($tableRows, function ($a, $b) use ($cartonPrefix) {
                $aFirst = intval(str_replace($cartonPrefix, '', explode('-', $a['ctn_range'])[0]));
                $bFirst = intval(str_replace($cartonPrefix, '', explode('-', $b['ctn_range'])[0]));
                return $aFirst - $bFirst;
            });

            $lpNumbers = [];
            if ($packingList->vendor_id == 2) {
                $lpNumberRecords = PackingListLpNumber::where('packing_list_id', $packingList->id)->get();
                foreach ($lpNumberRecords as $record) {
                    $key = $record->article_number . '|' . $record->color . '|' . $record->carton_range;
                    $lpNumbers[$key] = $record->lp_no;
                }
            }

            $tableData = [
                'lpNumbers' => $lpNumbers,
                'sizeOrder' => $sizeOrder,
                'rows'      => $tableRows,
                'totals'    => $totals
            ];
        } elseif ($packingList->vendor_id == 3) {
            //
            // VENDOR ID 3 (PUMA-specific)
            //

            $dispatchQuantities = collect();
            $totalDispatches = 0;
            $orderQuantitiesFromAllPacks = collect();
            $currentDispatchNumber = 1;

            $allPackingLists = PackingListMaster::where('po_id', $packingList->po_id)
                ->orderBy('id', 'asc')
                ->get();

            $totalDispatches = $allPackingLists->count();

            // Initialize with position order
            $orderQuantitiesFromAllPacks = collect();
            foreach ($sizeOrder as $size) {
                $orderQuantitiesFromAllPacks[$size] = 0;
            }

            $configOrderQty = PackingListConfigItem::where('po_id', $packingList->po_id)
                ->where('status', 0)
                ->where('color', $packingList->color)
                ->get();

            foreach ($configOrderQty as $config) {
                if (in_array($config->size, $sizeOrder)) {
                    $currentQty = $orderQuantitiesFromAllPacks->get($config->size, 0);
                    $orderQuantitiesFromAllPacks[$config->size] = $currentQty + $config->po_qty;
                }
            }

            $currentPackingListIndex = $allPackingLists->search(function ($item) use ($packingList) {
                return $item->id == $packingList->id;
            });

            $currentDispatchNumber = $currentPackingListIndex + 1;

            foreach ($allPackingLists as $index => $pList) {
                if ($index <= $currentPackingListIndex) {
                    $dispatchNumber = $index + 1;
                    $packingListItems = PackingListItem::where('packing_list_id', $pList->id)->get();

                    // Initialize with position order
                    $dispatchQtyBySize = collect();
                    foreach ($sizeOrder as $size) {
                        $dispatchQtyBySize[$size] = 0;
                    }

                    foreach ($packingListItems as $item) {
                        if (in_array($item->size, $sizeOrder)) {
                            $currentQty = $dispatchQtyBySize->get($item->size, 0);
                            $dispatchQtyBySize[$item->size] = $currentQty + $item->quantity;
                        }
                    }

                    $dispatchQuantities[$dispatchNumber] = $dispatchQtyBySize;
                }
            }

            $allSizesFromPO = collect($sizeOrder);

            // Initialize orderedQuantities with position order
            $orderedQuantities = collect();
            foreach ($sizeOrder as $size) {
                $orderedQuantities[$size] = 0;
            }

            if (!empty($uniquePoItemIds)) {
                $poItemsFiltered = PoItems::whereIn('id', $uniquePoItemIds)->get();
                foreach ($poItemsFiltered as $poItem) {
                    if (in_array($poItem->size, $sizeOrder)) {
                        $currentQty = $orderedQuantities->get($poItem->size, 0);
                        $orderedQuantities[$poItem->size] = $currentQty + $poItem->qty;
                    }
                }
            } else {
                if ($packingList->po_id) {
                    $allPoItems = PoItems::where('po_id', $packingList->po_id)->get();
                    foreach ($allPoItems as $poItem) {
                        if (in_array($poItem->size, $sizeOrder)) {
                            $currentQty = $orderedQuantities->get($poItem->size, 0);
                            $orderedQuantities[$poItem->size] = $currentQty + $poItem->qty;
                        }
                    }
                }
            }

            $allSizes = $allSizesFromPO;

            // Separate pure and mixed cartons
            $pureItems = $sortedItems->where('is_mixed', false);
            $mixedItems = $sortedItems->where('is_mixed', true);

            // Separate full and under-filled cartons from pure items
            $fullCartons = collect();
            $underFilledCartons = collect();

            foreach ($pureItems as $item) {
                $perCartonQty = $item->per_carton_config_qty ?? 0;
                if ($perCartonQty > 0 && $item->quantity < $perCartonQty) {
                    // Under-filled carton
                    $item->is_under_filled = true;
                    $underFilledCartons->push($item);
                } else {
                    // Full carton
                    $item->is_under_filled = false;
                    $fullCartons->push($item);
                }
            }

            // Group full cartons by position and quantity pattern
            $fullCartonGroups = [];

            foreach ($fullCartons->groupBy('position') as $position => $posItems) {
                // Group by quantity pattern
                $patternGroups = [];

                foreach ($posItems as $item) {
                    $quantityPattern = [];
                    foreach ($sizeOrder as $size) {
                        $quantityPattern[$size] = $item->size == $size ? $item->quantity : 0;
                    }

                    $patternKey = md5(json_encode($quantityPattern));

                    if (!isset($patternGroups[$patternKey])) {
                        $patternGroups[$patternKey] = [
                            'pattern' => $quantityPattern,
                            'items' => collect()
                        ];
                    }

                    $patternGroups[$patternKey]['items']->push($item);
                }

                // Create ranges for each pattern
                foreach ($patternGroups as $patternKey => $patternData) {
                    $items = $patternData['items']->sortBy('id');

                    // Group consecutive cartons
                    $cartonRanges = [];
                    $currentRange = [];
                    $lastCartonNum = null;

                    foreach ($items as $item) {
                        $currentCartonNum = intval(str_replace($cartonPrefix, '', $item->dynamic_carton_name));

                        if ($lastCartonNum === null || $currentCartonNum == $lastCartonNum + 1) {
                            $currentRange[] = $item;
                        } else {
                            if (!empty($currentRange)) {
                                $cartonRanges[] = $currentRange;
                            }
                            $currentRange = [$item];
                        }

                        $lastCartonNum = $currentCartonNum;
                    }

                    if (!empty($currentRange)) {
                        $cartonRanges[] = $currentRange;
                    }

                    // Create groups for each range
                    foreach ($cartonRanges as $range) {
                        $rangeItems = collect($range);
                        $sizeQuantities = [];

                        foreach ($rangeItems as $item) {
                            $sizeQuantities[$item->size] = ($sizeQuantities[$item->size] ?? 0) + $item->quantity;
                        }

                        $fullCartonGroups[] = [
                            'position' => $position,
                            'carton_names' => $rangeItems->pluck('dynamic_carton_name')->toArray(),
                            'items' => $rangeItems,
                            'quantity_pattern' => $patternData['pattern'],
                            'size_quantities' => $sizeQuantities,
                            'is_mixed' => false,
                            'is_under_filled' => false
                        ];
                    }
                }
            }

            // Group under-filled cartons by quantity pattern (not by position)
            $underFilledCartonGroups = [];
            $underFilledPatternGroups = [];

            foreach ($underFilledCartons as $item) {
                $quantityPattern = [];
                foreach ($sizeOrder as $size) {
                    $quantityPattern[$size] = $item->size == $size ? $item->quantity : 0;
                }

                $patternKey = md5(json_encode($quantityPattern));

                if (!isset($underFilledPatternGroups[$patternKey])) {
                    $underFilledPatternGroups[$patternKey] = [
                        'pattern' => $quantityPattern,
                        'items' => collect()
                    ];
                }

                $underFilledPatternGroups[$patternKey]['items']->push($item);
            }

            // Create ranges for under-filled cartons (sorted by ID, not position)
            foreach ($underFilledPatternGroups as $patternKey => $patternData) {
                $items = $patternData['items']->sortBy('id'); // Sort by packing list item ID

                // Group consecutive cartons
                $cartonRanges = [];
                $currentRange = [];
                $lastCartonNum = null;

                foreach ($items as $item) {
                    $currentCartonNum = intval(str_replace($cartonPrefix, '', $item->dynamic_carton_name));

                    if ($lastCartonNum === null || $currentCartonNum == $lastCartonNum + 1) {
                        $currentRange[] = $item;
                    } else {
                        if (!empty($currentRange)) {
                            $cartonRanges[] = $currentRange;
                        }
                        $currentRange = [$item];
                    }

                    $lastCartonNum = $currentCartonNum;
                }

                if (!empty($currentRange)) {
                    $cartonRanges[] = $currentRange;
                }

                // Create groups for each range
                foreach ($cartonRanges as $range) {
                    $rangeItems = collect($range);
                    $sizeQuantities = [];

                    foreach ($rangeItems as $item) {
                        $sizeQuantities[$item->size] = ($sizeQuantities[$item->size] ?? 0) + $item->quantity;
                    }

                    $underFilledCartonGroups[] = [
                        'position' => 999, // Use a high position value for sorting
                        'carton_names' => $rangeItems->pluck('dynamic_carton_name')->toArray(),
                        'items' => $rangeItems,
                        'quantity_pattern' => $patternData['pattern'],
                        'size_quantities' => $sizeQuantities,
                        'is_mixed' => false,
                        'is_under_filled' => true
                    ];
                }
            }

            // Group mixed carton items
            $mixedCartonGroups = [];
            foreach ($mixedItems->groupBy('dynamic_carton_name') as $cartonName => $items) {
                $sizeQuantities = [];
                foreach ($items as $item) {
                    $sizeQuantities[$item->size] = ($sizeQuantities[$item->size] ?? 0) + $item->quantity;
                }

                $mixedCartonGroups[] = [
                    'position' => 9999,
                    'carton_names' => [$cartonName],
                    'items' => $items,
                    'quantity_pattern' => [],
                    'size_quantities' => $sizeQuantities,
                    'is_mixed' => true,
                    'is_under_filled' => false
                ];
            }

            // Combine all groups in the correct order
            $positionGroups = [];

            // Sort full cartons by position
            usort($fullCartonGroups, function ($a, $b) {
                return $a['position'] - $b['position'];
            });

            // Sort under-filled cartons by first carton number (reflects ID order)
            usort($underFilledCartonGroups, function ($a, $b) use ($cartonPrefix) {
                $aFirst = intval(str_replace($cartonPrefix, '', $a['carton_names'][0]));
                $bFirst = intval(str_replace($cartonPrefix, '', $b['carton_names'][0]));
                return $aFirst - $bFirst;
            });

            // Sort mixed cartons by carton number
            usort($mixedCartonGroups, function ($a, $b) use ($cartonPrefix) {
                $aFirst = intval(str_replace($cartonPrefix, '', $a['carton_names'][0]));
                $bFirst = intval(str_replace($cartonPrefix, '', $b['carton_names'][0]));
                return $aFirst - $bFirst;
            });

            // Combine in order: full cartons, under-filled cartons, mixed cartons
            $positionGroups = array_merge($fullCartonGroups, $underFilledCartonGroups, $mixedCartonGroups);

            // Create table rows
            $tableRows = [];

            foreach ($positionGroups as $group) {
                $cartonCount = count($group['carton_names']);
                $firstCartonName = $group['carton_names'][0];
                $lastCartonName = end($group['carton_names']);

                $ctnRange = $cartonCount > 1
                    ? $firstCartonName . '-' . $lastCartonName
                    : $firstCartonName;

                $totalQty = 0;
                $totalNetWeight = 0;
                $totalGrossWeight = 0;

                foreach ($group['items'] as $item) {
                    $totalQty += $item->quantity;
                    $totalNetWeight += $item->net_weight ?? 0;
                    $totalGrossWeight += ($item->net_weight ?? 0) + 1.50;
                }

                $firstItem = $group['items']->first();
                $carton = $firstItem->carton;
                $dimension = '';
                if (
                    ($carton->length ?? 0) > 0
                    || ($carton->breadth ?? 0) > 0
                    || ($carton->height ?? 0) > 0
                ) {
                    $dimension = 'L' . ($carton->length ?? 0)
                        . '*B' . ($carton->breadth ?? 0)
                        . '*H' . ($carton->height ?? 0)
                        . 'CMS';
                }

                $perCartonQty = $cartonCount > 0 ? round($totalQty / $cartonCount) : 0;

                $row = [
                    'ctn_range'    => $ctnRange,
                    'ttl_ctn'      => $cartonCount,
                    'color'        => $firstItem->po_item->id_color ?? '',
                    'per_size'     => $group['size_quantities'],
                    'per_ctn'      => $perCartonQty,
                    'total'        => $totalQty,
                    'net_wt_per'   => $totalNetWeight,
                    'grs_wt_per'   => $totalGrossWeight,
                    'net_wt_total' => $totalNetWeight,
                    'grs_wt_total' => $totalGrossWeight,
                    'ctn_dim'      => $dimension,
                    'position'     => $group['position'],
                    'is_mixed'     => $group['is_mixed'],
                    'is_under_filled' => $group['is_under_filled'] ?? false
                ];

                $tableRows[] = $row;
            }

            // No additional sorting needed as groups are already organized
            $tableData = [
                'sizeOrder' => $sizeOrder,
                'rows'      => $tableRows,
            ];
        } elseif ($packingList->vendor_id == 7) {
            //
            // VENDOR ID 7 (Aditya-specific)
            //

            $location = $packingList->location ?? '';

            $allSizesFromPO = collect($sizeOrder);

            // Initialize with position order
            $orderQuantitiesFromConfig = collect();
            foreach ($sizeOrder as $size) {
                $orderQuantitiesFromConfig[$size] = 0;
            }

            if ($packingList->po_id && $location) {
                $configItems = PackingListConfigItem::where('po_id', $packingList->po_id)
                    ->where('status', 0)
                    ->whereHas('poItem', function ($query) use ($location) {
                        $query->where('location', $location);
                    })
                    ->get();

                foreach ($configItems as $config) {
                    if (in_array($config->size, $sizeOrder)) {
                        $currentQty = $orderQuantitiesFromConfig->get($config->size, 0);
                        $orderQuantitiesFromConfig[$config->size] = $currentQty + $config->po_qty;
                    }
                }
            }

            $ctnDimensions = '';
            if ($packingList->po_id) {
                $configMaster = PackingListConfigMaster::where('po_id', $packingList->po_id)->first();
                if ($configMaster && $configMaster->carton) {
                    $carton = $configMaster->carton;
                    if ($carton->length || $carton->breadth || $carton->height) {
                        $ctnDimensions = $carton->length . 'X' . $carton->breadth . 'X' . $carton->height;
                    }
                }
            }

            // Calculate weights and CBM per carton
            $cartonWeights = [];
            $cartonCbm = [];
            foreach ($sortedItems as $item) {
                $cartonName = $item->dynamic_carton_name;

                if (!isset($cartonWeights[$cartonName])) {
                    $cartonWeights[$cartonName] = $item->net_weight ?? 0;
                }

                if (!isset($cartonCbm[$cartonName])) {
                    $cartonCbm[$cartonName] = 0;
                }

                $cbm = $item->quantity * (
                    ($item->carton->length ?? 0) *
                    ($item->carton->breadth ?? 0) *
                    ($item->carton->height ?? 0)
                ) / 1000000;

                $cartonCbm[$cartonName] += $cbm;
            }

            // Separate pure and mixed cartons
            $pureItems = $sortedItems->where('is_mixed', false);
            $mixedItems = $sortedItems->where('is_mixed', true);

            // Group pure items by position and size
            $positionGroups = [];

            foreach ($pureItems->groupBy('position') as $position => $posItems) {
                foreach ($posItems->groupBy('size') as $size => $sizeItems) {
                    // Group consecutive cartons
                    $cartonRanges = [];
                    $currentRange = [];
                    $lastCartonNum = null;

                    foreach ($sizeItems->sortBy('id') as $item) {
                        $currentCartonNum = intval(str_replace($cartonPrefix, '', $item->dynamic_carton_name));

                        if ($lastCartonNum === null || $currentCartonNum == $lastCartonNum + 1) {
                            $currentRange[] = $item;
                        } else {
                            if (!empty($currentRange)) {
                                $cartonRanges[] = $currentRange;
                            }
                            $currentRange = [$item];
                        }

                        $lastCartonNum = $currentCartonNum;
                    }

                    if (!empty($currentRange)) {
                        $cartonRanges[] = $currentRange;
                    }

                    // Create groups for each range
                    foreach ($cartonRanges as $range) {
                        $rangeItems = collect($range);

                        $positionGroups[] = [
                            'position' => $position,
                            'carton_names' => $rangeItems->pluck('dynamic_carton_name')->toArray(),
                            'article_number' => $rangeItems->first()->article_number,
                            'style_description' => $rangeItems->first()->po_item->style_description ?? $rangeItems->first()->po_item->part_description ?? '',
                            'color' => $rangeItems->first()->color,
                            'sizes' => [$size => $rangeItems->sum('quantity')],
                            'total_qty' => $rangeItems->sum('quantity'),
                            'is_mixed' => false
                        ];
                    }
                }
            }

            // Group mixed carton items
            foreach ($mixedItems->groupBy('dynamic_carton_name') as $cartonName => $items) {
                $sizes = [];
                foreach ($items as $item) {
                    $sizes[$item->size] = ($sizes[$item->size] ?? 0) + $item->quantity;
                }

                $firstItem = $items->first();

                $positionGroups[] = [
                    'position' => 9999,
                    'carton_names' => [$cartonName],
                    'article_number' => $firstItem->article_number,
                    'style_description' => $firstItem->po_item->style_description ?? $firstItem->po_item->part_description ?? '',
                    'color' => $firstItem->color,
                    'sizes' => $sizes,
                    'total_qty' => $items->sum('quantity'),
                    'is_mixed' => true
                ];
            }

            // Create table rows
            $tableRows = [];
            $totals = [
                'carton_count' => 0,
                'per_size' => array_fill_keys($sizeOrder, 0),
                'total_pieces' => 0
            ];

            foreach ($positionGroups as $group) {
                $cartonCount = count($group['carton_names']);
                $firstName = $group['carton_names'][0];
                $lastName = end($group['carton_names']);

                $ctnRange = $cartonCount > 1
                    ? $firstName . ' to ' . $lastName
                    : $firstName . ' to ' . $firstName;

                $totalQty = $group['total_qty'];

                // Prepare size quantities array with all sizes initialized to 0
                $sizeQuantities = array_fill_keys($sizeOrder, 0);
                foreach ($group['sizes'] as $size => $qty) {
                    if (in_array($size, $sizeOrder)) {
                        $sizeQuantities[$size] = $qty;
                    }
                }

                $perCartonQty = $cartonCount > 0 ? round($totalQty / $cartonCount) : 0;

                $row = [
                    'article_number' => $group['article_number'],
                    'style_description' => $group['style_description'],
                    'color' => $group['color'],
                    'per_size' => $sizeQuantities,
                    'per_ctn' => $perCartonQty,
                    'total' => $totalQty,
                    'ctn_range' => $ctnRange,
                    'total_ctns' => $cartonCount,
                    'position' => $group['position'],
                    'is_mixed' => $group['is_mixed']
                ];

                $totals['carton_count'] += $cartonCount;
                foreach ($sizeQuantities as $size => $qty) {
                    if (isset($totals['per_size'][$size])) {
                        $totals['per_size'][$size] += $qty;
                    }
                }
                $totals['total_pieces'] += $totalQty;

                $tableRows[] = $row;
            }

            // Sort by position (pure cartons first, mixed last)
            usort($tableRows, function ($a, $b) {
                return $a['position'] - $b['position'];
            });

            $uniqueCartons = $sortedItems->pluck('dynamic_carton_name')->unique();
            $totalCtn = $uniqueCartons->count();

            $totalNetWeight = array_sum($cartonWeights);
            $totalGrossWeight = $totalNetWeight + ($totalCtn * 1.5);
            $totalCbm = array_sum($cartonCbm);

            $percentages = collect();
            foreach ($sizeOrder as $size) {
                $ordered = $orderQuantitiesFromConfig->get($size, 0);
                $packed = $totals['per_size'][$size] ?? 0;
                $percentage = $ordered > 0 ? round(($packed / $ordered) * 100) : 0;
                $percentages[$size] = $percentage;
            }

            $tableData = [
                'sizeOrder' => $sizeOrder,
                'rows' => $tableRows,
                'totals' => $totals
            ];

            $orderedQuantities = $orderQuantitiesFromConfig;
            $allSizes = collect($sizeOrder);
            $ctnDimDisplay = $ctnDimensions;
        } elseif ($packingList->vendor_id == 4) {
            //
            // VENDOR ID 4 (Benetton-specific)
            //
            $orderedQuantities = collect();
            $balances = collect();
            $percentages = collect();

            $uniqueColors = $packingList->items->pluck('color')->unique()->values()->toArray();

            // Initialize with position order
            foreach ($sizeOrder as $size) {
                $orderedQuantities[$size] = 0;
            }

            if ($packingList->po_id) {
                $poSizesFiltered = PoSizes::whereIn('color', $uniqueColors)
                    ->where('po_id', $packingList->po_id)
                    ->get();

                foreach ($poSizesFiltered as $poSize) {
                    if (in_array($poSize->size, $sizeOrder)) {
                        $currentQty = $orderedQuantities->get($poSize->size, 0);
                        $orderedQuantities[$poSize->size] = $currentQty + $poSize->qty;
                    }
                }
            }

            foreach ($allSizes as $size) {
                $ordered = $orderedQuantities->get($size, 0);
                $packed  = $packedQuantities->get($size, 0);
                $balance = $ordered - $packed;
                $percentage = $ordered > 0 ? ($packed / $ordered) * 100 : 0;

                $balances[$size]    = $balance;
                $percentages[$size] = $percentage;
            }

            // Separate pure and mixed cartons
            $pureItems = $sortedItems->where('is_mixed', false);
            $mixedItems = $sortedItems->where('is_mixed', true);

            // Group pure items by position, then by carton names
            $positionGroups = [];

            foreach ($pureItems->groupBy('position') as $position => $posItems) {
                // Group by unique carton names
                $cartonNameGroups = [];

                foreach ($posItems as $item) {
                    $cartonName = $item->dynamic_carton_name;

                    if (!isset($cartonNameGroups[$cartonName])) {
                        $cartonNameGroups[$cartonName] = collect();
                    }

                    $cartonNameGroups[$cartonName]->push($item);
                }

                // Now group consecutive cartons with same size pattern
                $cartonList = array_keys($cartonNameGroups);
                sort($cartonList, SORT_NATURAL);

                $ranges = [];
                $currentRange = [];
                $lastCartonNum = null;
                $lastSizePattern = null;

                foreach ($cartonList as $cartonName) {
                    $items = $cartonNameGroups[$cartonName];
                    $currentCartonNum = intval(str_replace($cartonPrefix, '', $cartonName));

                    // Create size pattern for this carton
                    $sizePattern = [];
                    foreach ($items as $item) {
                        $sizePattern[$item->size] = ($sizePattern[$item->size] ?? 0) + $item->quantity;
                    }
                    ksort($sizePattern);

                    $canGroup = false;
                    if ($lastCartonNum !== null && $lastSizePattern !== null) {
                        $isConsecutive = ($currentCartonNum == $lastCartonNum + 1);
                        $samePattern = ($sizePattern == $lastSizePattern);
                        $canGroup = $isConsecutive && $samePattern;
                    }

                    if ($canGroup) {
                        $currentRange[$cartonName] = $items;
                    } else {
                        if (!empty($currentRange)) {
                            $ranges[] = $currentRange;
                        }
                        $currentRange = [$cartonName => $items];
                    }

                    $lastCartonNum = $currentCartonNum;
                    $lastSizePattern = $sizePattern;
                }

                if (!empty($currentRange)) {
                    $ranges[] = $currentRange;
                }

                // Create groups for each range
                foreach ($ranges as $range) {
                    $cartonNames = array_keys($range);
                    $allItems = collect();
                    $sizeQuantities = [];
                    $sizes = [];

                    foreach ($range as $items) {
                        $allItems = $allItems->merge($items);
                        foreach ($items as $item) {
                            $sizeQuantities[$item->size] = ($sizeQuantities[$item->size] ?? 0) + $item->quantity;
                            if (!in_array($item->size, $sizes)) {
                                $sizes[] = $item->size;
                            }
                        }
                    }

                    $positionGroups[] = [
                        'position' => $position,
                        'carton_names' => $cartonNames,
                        'items' => $allItems,
                        'size_quantities' => $sizeQuantities,
                        'sizes' => $sizes,
                        'is_mixed' => false
                    ];
                }
            }

            // Group mixed carton items
            foreach ($mixedItems->groupBy('dynamic_carton_name') as $cartonName => $items) {
                $sizeQuantities = [];
                $sizes = [];

                foreach ($items as $item) {
                    $sizeQuantities[$item->size] = ($sizeQuantities[$item->size] ?? 0) + $item->quantity;
                    if (!in_array($item->size, $sizes)) {
                        $sizes[] = $item->size;
                    }
                }

                $positionGroups[] = [
                    'position' => 9999,
                    'carton_names' => [$cartonName],
                    'items' => $items,
                    'size_quantities' => $sizeQuantities,
                    'sizes' => $sizes,
                    'is_mixed' => true
                ];
            }

            // Create table rows
            $tableRows = [];
            $totals = [
                'carton_count' => 0,
                'per_size'     => array_fill_keys($sizeOrder, 0),
                'total_pieces' => 0,
                'net_weight'   => 0,
                'empty_box_weight' => 0,
                'gross_weight' => 0
            ];

            foreach ($positionGroups as $group) {
                $cartonNames = $group['carton_names'];
                $cartonCount = count($cartonNames);
                $allItems = $group['items'];

                $firstName = $cartonNames[0];
                $lastName = end($cartonNames);

                $ctnRange = $cartonCount > 1 ? $firstName . '-' . $lastName : $firstName;

                $totalQty = $allItems->sum('quantity');

                $firstItem = $allItems->first();
                $carton = $firstItem->carton;

                $netWeightPerCarton = $carton->net_weight ?? 0;
                $emptyBoxWeightPerCarton = $carton->empty_weight ?? 1.5;
                $grossWeightPerCarton = $carton->gross_weight ?? ($netWeightPerCarton + $emptyBoxWeightPerCarton);

                $totalNetWeightForRange = $netWeightPerCarton * $cartonCount;
                $totalEmptyBoxWeight = $emptyBoxWeightPerCarton * $cartonCount;
                $totalGrossWeightForRange = $grossWeightPerCarton * $cartonCount;

                $perCartonQty = $cartonCount > 0 ? round($totalQty / $cartonCount) : 0;

                $row = [
                    'ctn_range'        => $ctnRange,
                    'ctn_first'        => $firstName,
                    'ctn_last'         => $lastName,
                    'ttl_ctn'          => $cartonCount,
                    'color_code'       => $firstItem->color,
                    'per_size'         => array_fill_keys($sizeOrder, 0),
                    'per_ctn'          => $perCartonQty,
                    'grand_total'      => $totalQty,
                    'net_weight'       => $totalNetWeightForRange,
                    'empty_box_weight' => $totalEmptyBoxWeight,
                    'gross_weight'     => $totalGrossWeightForRange,
                    'totalCbm'   => array_unique($group['sizes']),
                    'position'   => $group['position'],
                    'is_mixed'   => $group['is_mixed']
                ];

                foreach ($group['size_quantities'] as $size => $qty) {
                    if (in_array($size, $sizeOrder)) {
                        $row['per_size'][$size] = $qty;
                    }
                }

                $totals['carton_count'] += $cartonCount;
                foreach ($group['size_quantities'] as $size => $qty) {
                    if (isset($totals['per_size'][$size])) {
                        $totals['per_size'][$size] += $qty;
                    }
                }
                $totals['total_pieces'] += $totalQty;
                $totals['net_weight'] += $totalNetWeightForRange;
                $totals['empty_box_weight'] += $totalEmptyBoxWeight;
                $totals['gross_weight'] += $totalGrossWeightForRange;

                $tableRows[] = $row;
            }

            // Sort by position (pure cartons first, mixed last)
            usort($tableRows, function ($a, $b) use ($cartonPrefix) {
                if ($a['position'] != $b['position']) {
                    return $a['position'] - $b['position'];
                }
                $aFirst = intval(str_replace($cartonPrefix, '', explode('-', $a['ctn_range'])[0]));
                $bFirst = intval(str_replace($cartonPrefix, '', explode('-', $b['ctn_range'])[0]));
                return $aFirst - $bFirst;
            });

            $totalCtn = $totals['carton_count'];
            $totalNetWeight = $totals['net_weight'];
            $totalGrossWeight = $totals['gross_weight'];

            $tableData = [
                'sizeOrder' => $sizeOrder,
                'rows'      => $tableRows,
                'totals'    => $totals
            ];
        } else {
            //
            // OTHER VENDORS (1, 5, 6 and others): generic summary
            //

            $allSizesFromPO = collect($sizeOrder);

            // Initialize with position order
            $orderedQuantities = collect();
            foreach ($sizeOrder as $size) {
                $orderedQuantities[$size] = 0;
            }

            if (!empty($uniquePoItemIds)) {
                $poItemsFiltered = PoItems::whereIn('id', $uniquePoItemIds)->get();
                foreach ($poItemsFiltered as $poItem) {
                    if (in_array($poItem->size, $sizeOrder)) {
                        $currentQty = $orderedQuantities->get($poItem->size, 0);
                        $orderedQuantities[$poItem->size] = $currentQty + $poItem->qty;
                    }
                }
            } else {
                if ($packingList->po_id) {
                    $allPoItems = PoItems::where('po_id', $packingList->po_id)->get();
                    foreach ($allPoItems as $poItem) {
                        if (in_array($poItem->size, $sizeOrder)) {
                            $currentQty = $orderedQuantities->get($poItem->size, 0);
                            $orderedQuantities[$poItem->size] = $currentQty + $poItem->qty;
                        }
                    }
                }
            }

            $allSizes = $allSizesFromPO;

            // Separate pure and mixed cartons
            $pureItems = $sortedItems->where('is_mixed', false);
            $mixedItems = $sortedItems->where('is_mixed', true);

            // Group pure items by position, then by article, color, size
            $positionGroups = [];

            foreach ($pureItems->groupBy('position') as $position => $posItems) {
                foreach (
                    $posItems->groupBy(function ($item) {
                        return $item->article_number . '|' . $item->color . '|' . $item->size;
                    }) as $groupKey => $groupItems
                ) {
                    list($articleNumber, $color, $size) = explode('|', $groupKey);

                    // Group consecutive cartons
                    $cartonRanges = [];
                    $currentRange = [];
                    $lastCartonNum = null;

                    foreach ($groupItems->sortBy('id') as $item) {
                        $currentCartonNum = intval(str_replace($cartonPrefix, '', $item->dynamic_carton_name));

                        if ($lastCartonNum === null || $currentCartonNum == $lastCartonNum + 1) {
                            $currentRange[] = $item;
                        } else {
                            if (!empty($currentRange)) {
                                $cartonRanges[] = $currentRange;
                            }
                            $currentRange = [$item];
                        }

                        $lastCartonNum = $currentCartonNum;
                    }

                    if (!empty($currentRange)) {
                        $cartonRanges[] = $currentRange;
                    }

                    // Create groups for each range
                    foreach ($cartonRanges as $range) {
                        $rangeItems = collect($range);
                        $cartonNames = $rangeItems->pluck('dynamic_carton_name')->toArray();
                        $cartonCount = count($cartonNames);

                        $firstName = $cartonNames[0];
                        $lastName = end($cartonNames);

                        $totalQty = $rangeItems->sum('quantity');

                        $firstItem = $rangeItems->first();
                        $carton = $firstItem->carton;

                        $netWeightPerCarton = $firstItem->net_weight ?? 0;
                        $grossWeightPerCarton = ($firstItem->net_weight + 1.45) ?? 0;

                        $dimension = '';
                        if (
                            ($carton->length ?? 0) > 0
                            || ($carton->breadth ?? 0) > 0
                            || ($carton->height ?? 0) > 0
                        ) {
                            $dimension = ($carton->length ?? 0)
                                . '*' . ($carton->breadth ?? 0)
                                . '*' . ($carton->height ?? 0)
                                . ' CMS';
                        }

                        $perCartonQty = $cartonCount > 0 ? round($totalQty / $cartonCount) : 0;
                        $mrp = $firstItem->po_item->mrp ?? '';
                        $poItemId = $firstItem->po_item_id;

                        $positionGroups[] = [
                            'position' => $position,
                            'article_number'  => $articleNumber,
                            'color'           => $color,
                            'size'            => $size,
                            'ctn_first'       => $firstName,
                            'ctn_last'        => $lastName,
                            'first_carton_id' => $cartonNames[0],
                            'ttl_ctn'         => $cartonCount,
                            'per_size'        => array_fill_keys($sizeOrder, 0),
                            'per_ctn'         => $perCartonQty,
                            'total'           => $totalQty,
                            'net_wt_per'      => $netWeightPerCarton,
                            'grs_wt_per'      => $grossWeightPerCarton,
                            'ctn_dim'         => $dimension,
                            'mrp'             => $mrp,
                            'po_item_id'      => $poItemId,
                            'is_mixed'        => false
                        ];

                        $positionGroups[count($positionGroups) - 1]['per_size'][$size] = $totalQty;
                    }
                }
            }

            // Group mixed carton items
            foreach ($mixedItems->groupBy('dynamic_carton_name') as $cartonName => $items) {
                $firstItem = $items->first();
                $carton = $firstItem->carton;

                $totalQty = $items->sum('quantity');
                $netWeightPerCarton = $firstItem->net_weight ?? 0;
                $grossWeightPerCarton = ($firstItem->net_weight + 1.45) ?? 0;

                $dimension = '';
                if (
                    ($carton->length ?? 0) > 0
                    || ($carton->breadth ?? 0) > 0
                    || ($carton->height ?? 0) > 0
                ) {
                    $dimension = ($carton->length ?? 0)
                        . '*' . ($carton->breadth ?? 0)
                        . '*' . ($carton->height ?? 0)
                        . ' CMS';
                }

                $mrp = $firstItem->po_item->mrp ?? '';
                $poItemId = $firstItem->po_item_id;

                $perSizeArray = array_fill_keys($sizeOrder, 0);
                foreach ($items as $item) {
                    if (in_array($item->size, $sizeOrder)) {
                        $perSizeArray[$item->size] += $item->quantity;
                    }
                }

                $positionGroups[] = [
                    'position' => 9999,
                    'article_number'  => $firstItem->article_number,
                    'color'           => $firstItem->color,
                    'size'            => 'Mixed',
                    'ctn_first'       => $cartonName,
                    'ctn_last'        => $cartonName,
                    'first_carton_id' => $cartonName,
                    'ttl_ctn'         => 1,
                    'per_size'        => $perSizeArray,
                    'per_ctn'         => $totalQty,
                    'total'           => $totalQty,
                    'net_wt_per'      => $netWeightPerCarton,
                    'grs_wt_per'      => $grossWeightPerCarton,
                    'ctn_dim'         => $dimension,
                    'mrp'             => $mrp,
                    'po_item_id'      => $poItemId,
                    'is_mixed'        => true
                ];
            }

            // Sort by position (pure cartons first, mixed last)
            usort($positionGroups, function ($a, $b) {
                return $a['position'] - $b['position'];
            });

            $tableRows = $positionGroups;

            $totals = [
                'carton_count' => 0,
                'per_size'     => array_fill_keys($sizeOrder, 0),
                'total_pieces' => 0,
            ];

            foreach ($tableRows as $row) {
                $totals['carton_count'] += $row['ttl_ctn'];
                foreach ($row['per_size'] as $size => $qty) {
                    if (isset($totals['per_size'][$size])) {
                        $totals['per_size'][$size] += $qty;
                    }
                }
                $totals['total_pieces'] += $row['total'];
            }

            $tableData = [
                'sizeOrder' => $sizeOrder,
                'rows'      => $tableRows,
                'totals'    => $totals,
            ];

            $totalCtn = $totals['carton_count'];
            $totalNetWeight = $totals['total_pieces'] * 0;
            $totalGrossWeight = $totals['total_pieces'] * 0;

            $dispatchQuantities = collect();
            $totalDispatches = 0;
            $orderQuantitiesFromAllPacks = collect();
            $currentDispatchNumber = 1;

            if (in_array($packingList->vendor_id, [1, 5, 6])) {
                $allPackingLists = PackingListMaster::where('po_id', $packingList->po_id)
                    ->orderBy('id', 'asc')
                    ->get();

                $totalDispatches = $allPackingLists->count();

                // Initialize with position order
                foreach ($sizeOrder as $size) {
                    $orderQuantitiesFromAllPacks[$size] = 0;
                }

                $configOrderQty = PackingListConfigItem::where('po_id', $packingList->po_id)
                    ->where('status', 0)
                    ->where('color', $packingList->color)
                    ->get();

                foreach ($configOrderQty as $config) {
                    if (in_array($config->size, $sizeOrder)) {
                        $currentQty = $orderQuantitiesFromAllPacks->get($config->size, 0);
                        $orderQuantitiesFromAllPacks[$config->size] = $currentQty + $config->po_qty;
                    }
                }

                // Find the position of current packing list
                $currentPackingListIndex = $allPackingLists->search(function ($item) use ($packingList) {
                    return $item->id == $packingList->id;
                });

                // Calculate dispatch quantities for all packing lists up to and including current one
                foreach ($allPackingLists as $index => $pList) {
                    if ($index <= $currentPackingListIndex) {
                        $dispatchNumber = $index + 1; // 1st dispatch, 2nd dispatch, etc.

                        // Get items for this specific packing list
                        $packingListItems = PackingListItem::where('packing_list_id', $pList->id)->get();

                        // Initialize with position order
                        $dispatchQtyBySize = collect();
                        foreach ($sizeOrder as $size) {
                            $dispatchQtyBySize[$size] = 0;
                        }

                        // Calculate quantities by size for this dispatch
                        foreach ($packingListItems as $item) {
                            if (in_array($item->size, $sizeOrder)) {
                                $currentQty = $dispatchQtyBySize->get($item->size, 0);
                                $dispatchQtyBySize[$item->size] = $currentQty + $item->quantity;
                            }
                        }

                        $dispatchQuantities[$dispatchNumber] = $dispatchQtyBySize;
                    }
                }

                // Add a flag to identify current packing list
                $currentDispatchNumber = $currentPackingListIndex + 1;
            }
        }

        if (!isset($dispatchQuantities)) {
            $dispatchQuantities = collect();
        }
        if (!isset($totalDispatches)) {
            $totalDispatches = 0;
        }
        if (!isset($orderQuantitiesFromAllPacks)) {
            $orderQuantitiesFromAllPacks = collect();
        }
        if (!isset($currentDispatchNumber)) {
            $currentDispatchNumber = 1;
        }

        //
        // 3. PASS ALL DATA TO VIEW
        //
        $viewData = [
            'packing_list'             => $packingList,
            'all_sizes'                => $allSizes,
            'packed_quantities'        => $packedQuantities,
            'ordered_quantities'       => $orderedQuantities,
            'balances'                 => $balances,
            'percentages'              => $percentages,
            'tableData'                => $tableData,
            'poNum'                    => $poNum,
            'poDate'                   => $poDate,
            'poJobNum'                 => $poJobNum,
            'genderDisplay'            => $genderDisplay,
            'styleDescriptionsDisplay' => $styleDescriptionsDisplay,
            'articleNumbersDisplay'    => $articleNumbersDisplay,
            'uniqueColorDisplay'       => $uniqueColorDisplay,
            'ctnDimDisplay'            => $ctnDimDisplay,
            'ctnWeight'                => $ctnWeight,
            'totalCtn'                 => $totalCtn,
            'totalNetWeight'           => $totalNetWeight,
            'totalGrossWeight'         => $totalGrossWeight,
            'dispatchQuantities'       => $dispatchQuantities,
            'totalDispatches'          => $totalDispatches,
            'orderQuantitiesFromAllPacks' => $orderQuantitiesFromAllPacks,
            'currentDispatchNumber'    => $currentDispatchNumber,
            'totalCbm'    => $totalCbm ?? null,
        ];

        // Choose the correct view template
        if ($packingList->vendor_id == 4) {
            $viewTemplate = 'packing_list.benetton_print';
        } elseif ($packingList->vendor_id == 3) {
            $viewTemplate = 'packing_list.puma_print';
        } elseif ($packingList->vendor_id == 2) {
            $viewTemplate = 'packing_list.skechers_print';
        } elseif ($packingList->vendor_id == 7) {
            $viewTemplate = 'packing_list.aditiya_print';
        } else {
            $viewTemplate = 'packing_list.jack_print';
        }

        // Generate PDF
        // $pdf = PDF::loadView($viewTemplate, $viewData)
        //     ->set_option('isHtml5ParserEnabled', true)
        //     ->set_option('isRemoteEnabled', true)
        //     ->setPaper('a4', 'landscape');

        // return $pdf->stream('Packing_list_print.pdf');

        return view($viewTemplate, $viewData);
    }

    public function saveLpNumber(Request $request)
    {
        try {
            $validated = $request->validate([
                'packing_list_id' => 'required|integer',
                'po_id' => 'required|integer',
                'article_number' => 'required|string',
                'color' => 'required|string',
                'carton_range' => 'required|string',
                'lp_no' => 'nullable|string',
            ]);

            $lpNumber = PackingListLpNumber::updateOrCreate(
                [
                    'packing_list_id' => $validated['packing_list_id'],
                    'po_id' => $validated['po_id'],
                    'article_number' => $validated['article_number'],
                    'color' => $validated['color'],
                    'carton_range' => $validated['carton_range'],
                ],
                [
                    'lp_no' => $validated['lp_no'],
                    'created_by' => auth()->id() ?? null,
                    'created_at' => now(),
                ]
            );

            return response()->json([
                'success' => true,
                'message' => 'LP Number saved successfully',
                'data' => $lpNumber
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function packing_list_complete(Request $request)
    {
        try {
            $packingList = PackingListMaster::find($request->id);

            if (! $packingList) {
                return response()->json([
                    'success' => false,
                    'message' => 'Packing List not found!',
                ], 404);
            }

            $packingList->pack_status = 1;
            $packingList->save();

            return response()->json([
                'success' => true,
                'message' => 'Packing List marked complete!',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ], 500);
        }
    }
}
