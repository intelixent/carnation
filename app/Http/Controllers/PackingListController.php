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
        } else {
            // Default: use PoItems table for all other vendors (1, 3, 5, 6)
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

        // Get excess percentage and calculate packQtyMatrix
        $excessPercentage = $po->vendor->excess ?? 0;
        $packQtyMatrix = [];
        $totalPackQty = 0;
        foreach ($colorSizeMatrix as $color => $sizes) {
            foreach ($sizes as $size => $qty) {
                $packQty = floor($qty * (1 + $excessPercentage / 100));
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
            'weightPerPieceData'
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

            // Get position, per_carton_qty, and weight_per_piece data for ALL vendors
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
                    $packQty = floor($poQty * (1 + $excess / 100));

                    // Get position, per_carton_qty, and weight_per_piece for Benetton as well
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
            } else {
                // For all other vendors (1, 3, 5, 6): use PoItems
                $items = PoItems::where('po_id', $po_id)->get();

                foreach ($items as $item) {
                    $color   = $item->color ?? $item->id_color ?? 'N/A';
                    $size    = $item->size ?? 'N/A';
                    $poQty   = $item->qty ?? 0;
                    $packQty = ceil($poQty * (1 + $excess / 100));

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

    public function get_sizes_with_qty(Request $request)
    {
        $poId     = $request->input('po_id');
        $article  = $request->input('article_number');
        $color    = $request->input('color');
        $location = $request->input('location'); // Add this

        // Fetch the PO (to get vendor_id)
        $po = PoMaster::with('vendor')->find($poId);
        if (! $po) {
            return response()->json(['error' => 'PO not found'], 404);
        }

        $vendorId = $po->vendor_id;
        $sizes = [];

        if ($vendorId == 4) {
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
            // New vendor 7 logic - directly from po_items
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

        // Get all packing lists for this PO and color
        $packingLists = PackingListMaster::where('po_id', $poId)
            ->where('color', $color)
            ->orderBy('created_at', 'desc')
            ->get();

        if ($packingLists->isEmpty()) {
            return response()->json([
                'packing_lists' => [],
                'can_add_items' => true
            ]);
        }

        $allPackingListsData = [];
        foreach ($packingLists as $packingList) {
            $items = PackingListItem::where('packing_list_id', $packingList->id)
                ->get()
                ->map(function ($item) {
                    return [
                        'id' => $item->id,
                        'carton_name' => $item->carton_name ?? 'N/A',
                        'article_number' => $item->article_number,
                        'color' => $item->color,
                        'size' => $item->size,
                        'quantity' => $item->quantity,
                        'carton_id' => $item->carton_id,
                    ];
                });

            $allPackingListsData[] = [
                'packing_list_id' => $packingList->id,
                'pack_ref_no' => $packingList->pack_ref_no,
                'pack_status' => $packingList->pack_status,
                'items' => $items
            ];
        }

        // Check if we can add more items for this specific color
        $canAddItems = $this->checkIfCanAddItems($poId, $color);

        return response()->json([
            'packing_lists' => $allPackingListsData,
            'can_add_items' => $canAddItems
        ]);
    }

    private function checkIfCanAddItems($poId, $color)
    {
        // Get the PO to check vendor
        $po = PoMaster::find($poId);
        if (!$po) {
            return false;
        }

        $vendorId = $po->vendor_id;

        if ($vendorId == 4) {
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
        } else {
            // For other vendors, check through config items for the specific color
            $configItems = PackingListConfigItem::whereHas('config', function ($q) use ($poId) {
                $q->where('po_id', $poId);
            })
                ->where('color', $color) // Filter by the specific color
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

        return false; // All items are fully packed for this color
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
        $poId     = $request->input('id');
        $vendorId = $request->input('vendor_id');
        $color    = $request->input('color');
        $location = $request->input('location');

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

        if ($vendorId == 4) {
            // Existing Benetton logic...
            $articles = PoItems::where('po_id', $poId)
                ->where('color', $color)
                ->pluck('article_number')
                ->unique()
                ->values();
        } elseif ($vendorId == 7) {
            // New vendor 7 logic
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
            'job_num'
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
        ];

        if ($vendorId == 7) {
            $rules['po_details.location'] = 'required|string';
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
                'created_by' => auth()->user()->id,
                'created_at' => now(),
            ];

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
                    if ($vendorId == 4) {
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
                            'error' => "Quantity for size {$size['size']} exceeds available limit. Available: {$remaining}"
                        ], 400);
                    }

                    // Create the PackingListItem
                    PackingListItem::create([
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
                    ]);
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
            // Use "X" or "*" as desired
            $ctnDimDisplay = "{$ctnLength}X{$ctnBreadth}X{$ctnHeight}";
        }

        // Weight field from first carton
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
                // For vendor ID 4 (Benetton), use part_description
                $styleArr = PoItems::whereIn('color', $uniqueColor)
                    ->where('po_id', $packingList->po->id)
                    ->pluck('part_description')
                    ->filter()
                    ->unique()
                    ->values()
                    ->toArray();
            } else {
                // For other vendors, use type
                $styleArr = PoItems::whereIn('id', $uniquePoItemIds)
                    ->pluck('type')
                    ->filter()
                    ->unique()
                    ->values()
                    ->toArray();
            }
            $styleDescriptionsDisplay = implode(', ', $styleArr);
        }

        // All sizes present in packing list (for both detail table and summary)
        $allSizes = $packingList->items->pluck('size')->unique()->sort()->values();

        // Packed quantities per size
        $packedQuantities = $packingList->items
            ->groupBy('size')
            ->map(fn($itemsForSize) => $itemsForSize->sum('quantity'));

        //
        // Initialize variables for summary (used mainly for vendor_id == 2)
        //
        $orderedQuantities = collect(); // filtered order qty only for items in list
        $balances = collect();
        $percentages = collect();
        // Note: In Blade, you compute orderTotal and packTotal as needed

        //
        // tableData for main detail table
        //
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
            // VENDOR ID 2 (Skechers-specific) - Using PUMA logic with color and article_number grouping
            //

            // Initialize dispatch-related variables
            $dispatchQuantities = collect();
            $totalDispatches = 0;
            $orderQuantitiesFromAllPacks = collect();
            $currentDispatchNumber = 1;

            // Get all packing lists for this PO ordered by ID (chronological order)
            $allPackingLists = PackingListMaster::where('po_id', $packingList->po_id)
                ->orderBy('id', 'asc')
                ->get();

            $totalDispatches = $allPackingLists->count();

            // Get all unique color and article combinations from current packing list
            $currentPackingListItems = PackingListItem::where('packing_list_id', $packingList->id)->get();
            $currentColorArticleCombinations = $currentPackingListItems
                ->groupBy(function ($item) {
                    return $item->color . '|' . $item->article_number;
                })
                ->keys();

            // Calculate ORDER QTY from PackingListConfigItem - aggregate by SIZE only for the template
            $orderQuantitiesFromAllPacks = collect();

            foreach ($currentColorArticleCombinations as $colorArticleKey) {
                list($color, $articleNumber) = explode('|', $colorArticleKey);

                // Get po_item_ids from PoItems based on article_number and color
                $poItemIds = PoItems::where('po_id', $packingList->po_id)
                    ->where('article_number', $articleNumber)
                    ->where('color', $color)
                    ->pluck('id')
                    ->toArray();

                if (!empty($poItemIds)) {
                    // Get order quantities from PackingListConfigItem
                    $configItems = PackingListConfigItem::where('po_id', $packingList->po_id)
                        ->whereIn('po_item_id', $poItemIds)
                        ->where('status', 0)
                        ->get();

                    // Aggregate by size across all color-article combinations
                    foreach ($configItems as $configItem) {
                        $currentQty = $orderQuantitiesFromAllPacks->get($configItem->size, 0);
                        $orderQuantitiesFromAllPacks[$configItem->size] = $currentQty + $configItem->po_qty;
                    }
                }
            }

            // Find the position of current packing list
            $currentPackingListIndex = $allPackingLists->search(function ($item) use ($packingList) {
                return $item->id == $packingList->id;
            });

            $currentDispatchNumber = $currentPackingListIndex + 1;

            // Calculate dispatch quantities for all packing lists up to and including current one
            foreach ($allPackingLists as $index => $pList) {
                if ($index <= $currentPackingListIndex) {
                    $dispatchNumber = $index + 1; // 1st dispatch, 2nd dispatch, etc.

                    // Get items for this specific packing list
                    $packingListItems = PackingListItem::where('packing_list_id', $pList->id)->get();

                    // 🔹 Filter items by current article_number + color combinations
                    $filteredItems = $packingListItems->filter(function ($item) use ($currentColorArticleCombinations) {
                        $key = $item->color . '|' . $item->article_number;
                        return $currentColorArticleCombinations->contains($key);
                    });

                    // Calculate quantities by size only for the filtered items
                    $sizeQuantities = collect();
                    foreach ($filteredItems as $item) {
                        $currentQty = $sizeQuantities->get($item->size, 0);
                        $sizeQuantities[$item->size] = $currentQty + $item->quantity;
                    }

                    $dispatchQuantities[$dispatchNumber] = $sizeQuantities;
                }
            }


            // Get unique po_item_ids for this packing list based on article_number and color from PoItems
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

            // Compute ordered quantities for items in this packing list from PackingListConfigItem
            if (!empty($uniquePoItemIds)) {
                $configItems = PackingListConfigItem::whereIn('po_item_id', $uniquePoItemIds)
                    ->where('status', 0)
                    ->get();

                $orderedQuantities = $configItems
                    ->groupBy('size')
                    ->map(fn($itemsForSize) => $itemsForSize->sum('pack_qty'));
            } else {
                $orderedQuantities = collect();
            }

            // Compute balances & percentages per size
            foreach ($allSizes as $size) {
                $ordered = $orderQuantitiesFromAllPacks->get($size, 0);

                // Calculate total dispatched for this size across all dispatches
                $totalDispatchedForSize = 0;
                foreach ($dispatchQuantities as $dispatchQty) {
                    $totalDispatchedForSize += $dispatchQty->get($size, 0);
                }

                $balance = $ordered - $totalDispatchedForSize;
                $percentage = $ordered > 0 ? ($totalDispatchedForSize / $ordered) * 100 : 0;

                $balances[$size] = $balance;
                $percentages[$size] = $percentage;
            }

            // Get dynamic sizeOrder from PO items or fallback
            $sizeOrder = [];
            if ($packingList->po_id) {
                $sizeOrder = PoItems::where('po_id', $packingList->po_id)
                    ->pluck('size')
                    ->unique()
                    ->values()
                    ->toArray();
            }
            if (empty($sizeOrder)) {
                $sizeOrder = $allSizes->toArray();
            }

            // Step 1: Group by size and find continuous ranges within each size
            $sizeRanges = [];

            foreach ($sizeOrder as $size) {
                $sizeItems = $packingList->items->where('size', $size);

                if ($sizeItems->isEmpty()) {
                    continue;
                }

                // Sort items by carton_name
                $sortedItems = $sizeItems->sortBy(function ($item) {
                    return intval($item->carton_name);
                });

                // Group continuous carton names for this size
                $currentGroup = [];
                $lastCartonName = null;

                foreach ($sortedItems as $item) {
                    $currentCartonName = intval($item->carton_name);

                    if ($lastCartonName === null || $currentCartonName == $lastCartonName + 1) {
                        $currentGroup[] = $item;
                    } else {
                        // Gap detected, save current group and start new one
                        if (!empty($currentGroup)) {
                            $sizeRanges[] = [
                                'size' => $size,
                                'items' => collect($currentGroup)
                            ];
                        }
                        $currentGroup = [$item];
                    }

                    $lastCartonName = $currentCartonName;
                }

                // Don't forget the last group
                if (!empty($currentGroup)) {
                    $sizeRanges[] = [
                        'size' => $size,
                        'items' => collect($currentGroup)
                    ];
                }
            }

            // Step 2: Merge ranges that have overlapping carton names
            $mergedRanges = [];

            foreach ($sizeRanges as $range) {
                $cartonNames = $range['items']->pluck('carton_name')->unique()->sort()->values()->toArray();
                $rangeKey = implode('-', $cartonNames);

                if (!isset($mergedRanges[$rangeKey])) {
                    $mergedRanges[$rangeKey] = [
                        'carton_names' => $cartonNames,
                        'items' => collect(),
                        'size_quantities' => []
                    ];
                }

                $mergedRanges[$rangeKey]['items'] = $mergedRanges[$rangeKey]['items']->merge($range['items']);
                $mergedRanges[$rangeKey]['size_quantities'][$range['size']] = $range['items']->sum('quantity');
            }

            // Step 3: Create table rows
            $tableRows = [];
            $totals = [
                'carton_count' => 0,
                'per_size'     => array_fill_keys($sizeOrder, 0),
                'total_pieces' => 0,
                'total_net_weight' => 0,
                'total_gross_weight' => 0
            ];

            foreach ($mergedRanges as $rangeKey => $rangeData) {
                $cartonNames = $rangeData['carton_names'];
                $cartonCount = count($cartonNames);
                $allItems = $rangeData['items'];

                $firstName = $cartonNames[0];
                $lastName = end($cartonNames);

                // Create carton range
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

                // Get po_item_id from PoItems based on article_number and color
                $poItem = PoItems::where('po_id', $packingList->po_id)
                    ->where('article_number', $firstItem->article_number)
                    ->where('color', $firstItem->color)
                    ->first();

                $mrp = $poItem->mrp ?? '';
                $poItemId = $poItem->id ?? $firstItem->po_item_id;

                $row = [
                    'article_number'  => $firstItem->article_number,
                    'color'           => $firstItem->color,
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
                ];

                // Initialize all size columns to 0
                foreach ($sizeOrder as $sizeCol) {
                    $row['per_size'][$sizeCol] = 0;
                }

                // Set quantities for each size
                foreach ($rangeData['size_quantities'] as $size => $qty) {
                    if (in_array($size, $sizeOrder)) {
                        $row['per_size'][$size] = $qty;
                    }
                }

                // Update totals
                $totals['carton_count'] += $cartonCount;
                foreach ($rangeData['size_quantities'] as $size => $qty) {
                    if (isset($totals['per_size'][$size])) {
                        $totals['per_size'][$size] += $qty;
                    }
                }
                $totals['total_pieces'] += $totalQty;
                $totals['total_net_weight'] += $totalNetWeightForRange;
                $totals['total_gross_weight'] += $totalGrossWeightForRange;

                $tableRows[] = $row;
            }

            // Sort table rows by first carton number
            usort($tableRows, function ($a, $b) {
                $aFirst = intval(explode('-', $a['ctn_range'])[0]);
                $bFirst = intval(explode('-', $b['ctn_range'])[0]);
                return $aFirst - $bFirst;
            });

            $tableData = [
                'sizeOrder' => $sizeOrder,
                'rows'      => $tableRows,
                'totals'    => $totals
            ];
        } elseif ($packingList->vendor_id == 3) {
            //
            // VENDOR ID 3 (PUMA-specific)
            //

            // Initialize dispatch-related variables
            $dispatchQuantities = collect();
            $totalDispatches = 0;
            $orderQuantitiesFromAllPacks = collect();
            $currentDispatchNumber = 1;

            // Get all packing lists for this PO ordered by ID (chronological order)
            $allPackingLists = PackingListMaster::where('po_id', $packingList->po_id)
                ->orderBy('id', 'asc')
                ->get();

            $totalDispatches = $allPackingLists->count();

            // Calculate ORDER QTY from all packing lists for this PO (total packed quantities across all dispatches)
            $allPackingListIds = $allPackingLists->pluck('id')->toArray();
            $allPackingListItems = PackingListItem::whereIn('packing_list_id', $allPackingListIds)->get();

            $orderQuantitiesFromAllPacks = PackingListConfigItem::where('po_id', $packingList->po_id)
                ->where('status', 0)
                ->where('color', $packingList->color)
                ->groupBy('size')
                ->selectRaw('size, SUM(po_qty) as total_pack_qty')
                ->pluck('total_pack_qty', 'size');

            // Find the position of current packing list
            $currentPackingListIndex = $allPackingLists->search(function ($item) use ($packingList) {
                return $item->id == $packingList->id;
            });

            $currentDispatchNumber = $currentPackingListIndex + 1;

            // Calculate dispatch quantities for all packing lists up to and including current one
            foreach ($allPackingLists as $index => $pList) {
                if ($index <= $currentPackingListIndex) {
                    $dispatchNumber = $index + 1; // 1st dispatch, 2nd dispatch, etc.

                    // Get items for this specific packing list
                    $packingListItems = PackingListItem::where('packing_list_id', $pList->id)->get();

                    // Calculate quantities by size for this dispatch
                    $dispatchQtyBySize = $packingListItems
                        ->groupBy('size')
                        ->map(function ($items) {
                            return $items->sum('quantity');
                        });

                    $dispatchQuantities[$dispatchNumber] = $dispatchQtyBySize;
                }
            }

            // 1. Get all sizes from the PO (not just from current packing list)
            $allSizesFromPO = collect();
            if ($packingList->po_id) {
                $allSizesFromPO = PoItems::where('po_id', $packingList->po_id)
                    ->pluck('size')
                    ->unique()
                    ->sort()
                    ->values();
            }

            // If no sizes found in PO, fallback to current packing list sizes
            if ($allSizesFromPO->isEmpty()) {
                $allSizesFromPO = $allSizes;
            }

            // 2. Compute ordered quantities for all sizes in the PO
            $orderedQuantities = collect();
            if (!empty($uniquePoItemIds)) {
                $poItemsFiltered = PoItems::whereIn('id', $uniquePoItemIds)->get();
                $orderedQuantities = $poItemsFiltered
                    ->groupBy('size')
                    ->map(fn($itemsForSize) => $itemsForSize->sum('qty'));
            } else {
                // If no specific items, get all items for this PO
                if ($packingList->po_id) {
                    $allPoItems = PoItems::where('po_id', $packingList->po_id)->get();
                    $orderedQuantities = $allPoItems
                        ->groupBy('size')
                        ->map(fn($itemsForSize) => $itemsForSize->sum('qty'));
                } else {
                    $orderedQuantities = collect();
                }
            }

            $allSizes = $allSizesFromPO;

            // Get dynamic sizeOrder from PO items or fallback
            $sizeOrder = [];
            if ($packingList->po_id) {
                $sizeOrder = PoItems::where('po_id', $packingList->po_id)
                    ->pluck('size')
                    ->unique()
                    ->values()
                    ->toArray();
            }
            if (empty($sizeOrder)) {
                $sizeOrder = $allSizes->toArray();
            }

            // NEW APPROACH: Group by carton ranges with quantity consistency check

            // Step 1: Sort all items by carton_name
            $sortedItems = $packingList->items->sortBy(function ($item) {
                return intval($item->carton_name);
            });

            // Step 2: Group items by carton and calculate per-carton quantities by size
            $cartonData = [];
            foreach ($sortedItems as $item) {
                $cartonName = $item->carton_name;
                if (!isset($cartonData[$cartonName])) {
                    $cartonData[$cartonName] = [
                        'items' => collect(),
                        'size_quantities' => [],
                        'total_qty' => 0,
                        'net_weight' => $item->net_weight ?? 0,
                        'carton' => $item->carton,
                        'po_item' => $item->po_item,
                    ];
                }

                $cartonData[$cartonName]['items']->push($item);
                $size = $item->size;
                $cartonData[$cartonName]['size_quantities'][$size] =
                    ($cartonData[$cartonName]['size_quantities'][$size] ?? 0) + $item->quantity;
                $cartonData[$cartonName]['total_qty'] += $item->quantity;
            }

            // Step 3: Sort cartons by carton number
            $sortedCartons = collect($cartonData)->sortBy(function ($data, $cartonName) {
                return intval($cartonName);
            });

            // Step 4: Group cartons into ranges with same quantity pattern
            $cartonRanges = [];
            $currentRange = [];
            $lastCartonName = null;
            $lastQuantityPattern = null;

            foreach ($sortedCartons as $cartonName => $cartonInfo) {
                $currentCartonNum = intval($cartonName);

                // Create a signature for this carton's quantity pattern
                $quantityPattern = [];
                foreach ($sizeOrder as $size) {
                    $quantityPattern[$size] = $cartonInfo['size_quantities'][$size] ?? 0;
                }

                // Check if this carton can be grouped with the current range
                $canGroup = false;

                if ($lastCartonName !== null && $lastQuantityPattern !== null) {
                    // Check if carton numbers are continuous (or same) AND quantity patterns match
                    $isConsecutive = ($currentCartonNum == $lastCartonName + 1 || $currentCartonNum == $lastCartonName);
                    $samePattern = ($quantityPattern == $lastQuantityPattern);

                    $canGroup = $isConsecutive && $samePattern;
                }

                if ($canGroup) {
                    // Add to current range
                    $currentRange[] = [
                        'carton_name' => $cartonName,
                        'carton_num' => $currentCartonNum,
                        'data' => $cartonInfo,
                        'quantity_pattern' => $quantityPattern
                    ];
                } else {
                    // Save current range and start new one
                    if (!empty($currentRange)) {
                        $cartonRanges[] = $currentRange;
                    }
                    $currentRange = [[
                        'carton_name' => $cartonName,
                        'carton_num' => $currentCartonNum,
                        'data' => $cartonInfo,
                        'quantity_pattern' => $quantityPattern
                    ]];
                }

                $lastCartonName = $currentCartonNum;
                $lastQuantityPattern = $quantityPattern;
            }

            // Don't forget the last range
            if (!empty($currentRange)) {
                $cartonRanges[] = $currentRange;
            }

            // Step 5: Create table rows from carton ranges
            $tableRows = [];

            foreach ($cartonRanges as $range) {
                $cartonCount = count($range);
                $firstCarton = $range[0];
                $lastCarton = end($range);

                // Create carton range display
                $ctnRange = $cartonCount > 1
                    ? $firstCarton['carton_name'] . '-' . $lastCarton['carton_name']
                    : $firstCarton['carton_name'];

                // Calculate totals for this range
                $totalQty = 0;
                $totalNetWeight = 0;
                $totalGrossWeight = 0;
                $sizeQuantities = [];

                foreach ($range as $cartonInfo) {
                    $totalQty += $cartonInfo['data']['total_qty'];
                    $totalNetWeight += $cartonInfo['data']['net_weight'];
                    $totalGrossWeight += $cartonInfo['data']['net_weight'] + 1.50;

                    // Add size quantities
                    foreach ($cartonInfo['quantity_pattern'] as $size => $qty) {
                        $sizeQuantities[$size] = ($sizeQuantities[$size] ?? 0) + $qty;
                    }
                }

                $firstItem = $firstCarton['data']['items']->first();
                $carton = $firstCarton['data']['carton'];

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
                    'per_size'     => [],
                    'per_ctn'      => $perCartonQty,
                    'total'        => $totalQty,
                    'net_wt_per'   => $totalNetWeight,
                    'grs_wt_per'   => $totalGrossWeight,
                    'net_wt_total' => $totalNetWeight,
                    'grs_wt_total' => $totalGrossWeight,
                    'ctn_dim'      => $dimension,
                ];

                // Initialize all size columns to 0
                foreach ($sizeOrder as $sizeCol) {
                    $row['per_size'][$sizeCol] = 0;
                }

                // Set quantities for each size in this range
                foreach ($sizeQuantities as $size => $qty) {
                    if (in_array($size, $sizeOrder)) {
                        $row['per_size'][$size] = $qty;
                    }
                }

                $tableRows[] = $row;
            }

            // Sort table rows by first carton number
            usort($tableRows, function ($a, $b) {
                $aFirst = intval(explode('-', $a['ctn_range'])[0]);
                $bFirst = intval(explode('-', $b['ctn_range'])[0]);
                return $aFirst - $bFirst;
            });

            $tableData = [
                'sizeOrder' => $sizeOrder,
                'rows'      => $tableRows,
            ];
        } elseif ($packingList->vendor_id == 7) {
            //
            // VENDOR ID 7 (Aditya-specific)
            //

            // Get location from packing list
            $location = $packingList->location ?? '';

            // Get all sizes from the PO for this location
            $allSizesFromPO = collect();
            if ($packingList->po_id && $location) {
                $allSizesFromPO = PoItems::where('po_id', $packingList->po_id)
                    ->where('location', $location)
                    ->pluck('size')
                    ->unique()
                    ->sort()
                    ->values();
            }

            // If no sizes found, fallback to current packing list sizes
            if ($allSizesFromPO->isEmpty()) {
                $allSizesFromPO = $allSizes;
            }

            // Get dynamic sizeOrder from PO items for this location
            $sizeOrder = [];
            if ($packingList->po_id && $location) {
                $sizeOrder = PoItems::where('po_id', $packingList->po_id)
                    ->where('location', $location)
                    ->pluck('size')
                    ->unique()
                    ->values()
                    ->toArray();
            }
            if (empty($sizeOrder)) {
                $sizeOrder = $allSizesFromPO->toArray();
            }

            // Get order quantities from PackingListConfigItem for this location
            $orderQuantitiesFromConfig = collect();
            if ($packingList->po_id && $location) {
                $orderQuantitiesFromConfig = PackingListConfigItem::where('po_id', $packingList->po_id)
                    ->where('status', 0)
                    ->whereHas('poItem', function ($query) use ($location) {
                        $query->where('location', $location);
                    })
                    ->groupBy('size')
                    ->selectRaw('size, SUM(po_qty) as total_order_qty')
                    ->pluck('total_order_qty', 'size');
            }

            // Get carton dimensions from PackingListConfigMaster
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

            // Calculate net weight per carton_name (sum quantities but count each carton only once for weight)
            $cartonWeights = [];
            $cartonCbm = [];
            foreach ($packingList->items as $item) {
                $cartonName = $item->carton_name;

                // For net weight - only count each carton_name once, use the net_weight from the item
                if (!isset($cartonWeights[$cartonName])) {
                    $cartonWeights[$cartonName] = $item->net_weight ?? 0;
                }

                // Calculate CBM for each item
                if (!isset($cartonCbm[$cartonName])) {
                    $cartonCbm[$cartonName] = 0;
                }

                $cbm = $item->quantity * (
                    ($item->carton->length ?? 0) *
                    ($item->carton->breadth ?? 0) *
                    ($item->carton->height ?? 0)
                ) / 1000000; // Convert to cubic meters

                $cartonCbm[$cartonName] += $cbm;
            }

            // Group items directly by carton_name and combine quantities by size
            $cartonGroups = [];

            foreach ($packingList->items as $item) {
                $cartonName = $item->carton_name;
                $poItem = $item->po_item;

                if (!isset($cartonGroups[$cartonName])) {
                    $cartonGroups[$cartonName] = [
                        'carton_name' => $cartonName,
                        'carton_num' => intval($cartonName),
                        'article_number' => $item->article_number,
                        'style_description' => $poItem->style_description ?? $poItem->part_description ?? '',
                        'color' => $item->color,
                        'sizes' => array_fill_keys($sizeOrder, 0),
                        'total_qty' => 0
                    ];
                }

                // Add quantity for this size
                if (in_array($item->size, $sizeOrder)) {
                    $cartonGroups[$cartonName]['sizes'][$item->size] += $item->quantity;
                    $cartonGroups[$cartonName]['total_qty'] += $item->quantity;
                }
            }

            // Sort cartons by carton number
            uasort($cartonGroups, function ($a, $b) {
                return $a['carton_num'] - $b['carton_num'];
            });

            // Group consecutive cartons with same quantity pattern for ranges
            $cartonRanges = [];
            $currentRange = [];
            $lastCartonNum = null;
            $lastSizePattern = null;

            foreach ($cartonGroups as $cartonData) {
                $currentCartonNum = $cartonData['carton_num'];
                $sizePattern = $cartonData['sizes'];

                // Check if this carton can be grouped with current range
                $canGroup = false;
                if ($lastCartonNum !== null && $lastSizePattern !== null) {
                    $isConsecutive = ($currentCartonNum == $lastCartonNum + 1);
                    $samePattern = ($sizePattern == $lastSizePattern);
                    $canGroup = $isConsecutive && $samePattern;
                }

                if ($canGroup) {
                    // Add to current range
                    $currentRange[] = $cartonData;
                } else {
                    // Save current range and start new one
                    if (!empty($currentRange)) {
                        $cartonRanges[] = $currentRange;
                    }
                    $currentRange = [$cartonData];
                }

                $lastCartonNum = $currentCartonNum;
                $lastSizePattern = $sizePattern;
            }

            // Don't forget the last range
            if (!empty($currentRange)) {
                $cartonRanges[] = $currentRange;
            }

            // Create table rows from carton ranges
            $tableRows = [];
            $totals = [
                'carton_count' => 0,
                'per_size' => array_fill_keys($sizeOrder, 0),
                'total_pieces' => 0
            ];

            foreach ($cartonRanges as $range) {
                $cartonCount = count($range);
                $firstCarton = $range[0];
                $lastCarton = end($range);

                // Create carton range display
                $ctnRange = $cartonCount > 1
                    ? $firstCarton['carton_name'] . ' to ' . $lastCarton['carton_name']
                    : $firstCarton['carton_name'] . ' to ' . $firstCarton['carton_name'];

                // Calculate totals for this range
                $totalQty = 0;
                $sizeQuantities = array_fill_keys($sizeOrder, 0);

                foreach ($range as $carton) {
                    $totalQty += $carton['total_qty'];
                    foreach ($sizeOrder as $size) {
                        $sizeQuantities[$size] += $carton['sizes'][$size];
                    }
                }

                // Calculate per carton quantity (total divided by number of cartons)
                $perCartonQty = $cartonCount > 0 ? round($totalQty / $cartonCount) : 0;

                $row = [
                    'article_number' => $firstCarton['article_number'],
                    'style_description' => $firstCarton['style_description'],
                    'color' => $firstCarton['color'],
                    'per_size' => $sizeQuantities,
                    'per_ctn' => $perCartonQty,
                    'total' => $totalQty,
                    'ctn_range' => $ctnRange,
                    'total_ctns' => $cartonCount
                ];

                // Update totals
                $totals['carton_count'] += $cartonCount;
                foreach ($sizeQuantities as $size => $qty) {
                    if (isset($totals['per_size'][$size])) {
                        $totals['per_size'][$size] += $qty;
                    }
                }
                $totals['total_pieces'] += $totalQty;

                $tableRows[] = $row;
            }

            // Calculate totals for footer
            $uniqueCartons = $packingList->items->pluck('carton_name')->unique();
            $totalCtn = $uniqueCartons->count();

            // Calculate total net weight (sum of net weights for unique cartons only)
            $totalNetWeight = array_sum($cartonWeights);

            // Calculate total gross weight (net weight + 1.5 per unique carton)
            $totalGrossWeight = $totalNetWeight + ($totalCtn * 1.5);

            // Calculate total CBM
            $totalCbm = array_sum($cartonCbm);

            // Calculate percentages for summary
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

            // Set order quantities for summary
            $orderedQuantities = $orderQuantitiesFromConfig;

            // Set location-specific data
            $allSizes = collect($sizeOrder);

            // Override the global dimension display with the one from config
            $ctnDimDisplay = $ctnDimensions;
        } elseif ($packingList->vendor_id == 4) {
            //
            // VENDOR ID 4 (Benetton-specific) - Group by size with continuous cartons, then merge same carton names across sizes
            //
            $orderedQuantities = collect();
            $balances = collect();
            $percentages = collect();

            // Get dynamic sizeOrder from PoSizes or fallback
            $sizeOrder = [];
            if ($packingList->po_id) {
                $sizeOrder = PoSizes::where('po_id', $packingList->po_id)
                    ->pluck('size')
                    ->unique()
                    ->values()
                    ->toArray();
            }
            if (empty($sizeOrder)) {
                $sizeOrder = $allSizes->toArray();
            }

            $uniqueColors = $packingList->items->pluck('color')->unique()->values()->toArray();

            // Get ordered quantities from PoSize
            if ($packingList->po_id) {
                $poSizesFiltered = PoSizes::whereIn('color', $uniqueColors)->where('po_id', $packingList->po_id)->get();
                $orderedQuantities = $poSizesFiltered
                    ->groupBy('size')
                    ->map(fn($itemsForSize) => $itemsForSize->sum('qty'));
            }

            // Compute balances & percentages for summary
            foreach ($allSizes as $size) {
                $ordered = $orderedQuantities->get($size, 0);
                $packed  = $packedQuantities->get($size, 0);
                $balance = $ordered - $packed;
                $percentage = $ordered > 0 ? ($packed / $ordered) * 100 : 0;

                $balances[$size]    = $balance;
                $percentages[$size] = $percentage;
            }

            // Step 1: Group by size and find continuous ranges within each size
            $sizeRanges = [];

            foreach ($sizeOrder as $size) {
                $sizeItems = $packingList->items->where('size', $size);

                if ($sizeItems->isEmpty()) {
                    continue;
                }

                // Sort items by carton_name
                $sortedItems = $sizeItems->sortBy(function ($item) {
                    return intval($item->carton_name);
                });

                // Group continuous carton names for this size
                $currentGroup = [];
                $lastCartonName = null;

                foreach ($sortedItems as $item) {
                    $currentCartonName = intval($item->carton_name);

                    if ($lastCartonName === null || $currentCartonName == $lastCartonName + 1) {
                        $currentGroup[] = $item;
                    } else {
                        // Gap detected, save current group and start new one
                        if (!empty($currentGroup)) {
                            $sizeRanges[] = [
                                'size' => $size,
                                'items' => collect($currentGroup)
                            ];
                        }
                        $currentGroup = [$item];
                    }

                    $lastCartonName = $currentCartonName;
                }

                // Don't forget the last group
                if (!empty($currentGroup)) {
                    $sizeRanges[] = [
                        'size' => $size,
                        'items' => collect($currentGroup)
                    ];
                }
            }

            // Step 2: Group ranges by carton names (merge different sizes with same carton names)
            $cartonGroups = [];

            foreach ($sizeRanges as $range) {
                $cartonNames = $range['items']->pluck('carton_name')->unique()->sort()->values()->toArray();
                $cartonNamesKey = implode(',', $cartonNames); // Use comma to create unique key

                if (!isset($cartonGroups[$cartonNamesKey])) {
                    $cartonGroups[$cartonNamesKey] = [
                        'carton_names' => $cartonNames,
                        'items' => collect(),
                        'size_quantities' => [],
                        'sizes' => []
                    ];
                }

                $cartonGroups[$cartonNamesKey]['items'] = $cartonGroups[$cartonNamesKey]['items']->merge($range['items']);
                $cartonGroups[$cartonNamesKey]['size_quantities'][$range['size']] = $range['items']->sum('quantity');
                $cartonGroups[$cartonNamesKey]['sizes'][] = $range['size'];
            }

            // Step 3: Create table rows
            $tableRows = [];
            $totals = [
                'carton_count' => 0,
                'per_size'     => array_fill_keys($sizeOrder, 0),
                'total_pieces' => 0,
                'net_weight'   => 0,
                'empty_box_weight' => 0,
                'gross_weight' => 0
            ];

            foreach ($cartonGroups as $cartonGroup) {
                $cartonNames = $cartonGroup['carton_names'];
                $cartonCount = count($cartonNames);
                $allItems = $cartonGroup['items'];

                $firstName = $cartonNames[0];
                $lastName = end($cartonNames);

                // Create carton range
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
                    'per_size'         => array_fill_keys($sizeOrder, 0), // Initialize all sizes to 0
                    'per_ctn'          => $perCartonQty,
                    'grand_total'      => $totalQty,
                    'net_weight'       => $totalNetWeightForRange,
                    'empty_box_weight' => $totalEmptyBoxWeight,
                    'gross_weight'     => $totalGrossWeightForRange,
                    'totalCbm'   => array_unique($cartonGroup['sizes']) // Track which sizes are in this row
                ];

                // Set quantities for each size that exists in this carton group
                foreach ($cartonGroup['size_quantities'] as $size => $qty) {
                    if (in_array($size, $sizeOrder)) {
                        $row['per_size'][$size] = $qty;
                    }
                }

                // Update totals
                $totals['carton_count'] += $cartonCount;
                foreach ($cartonGroup['size_quantities'] as $size => $qty) {
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

            // Sort table rows by first carton number
            usort($tableRows, function ($a, $b) {
                $aFirst = intval(explode('-', $a['ctn_range'])[0]);
                $bFirst = intval(explode('-', $b['ctn_range'])[0]);
                return $aFirst - $bFirst;
            });

            // Set the vendor ID 4 specific totals
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
            // OTHER VENDORS: generic summary similar to vendor_id 2
            //

            // 1. Get all sizes from the PO (not just from current packing list)
            $allSizesFromPO = collect();
            if ($packingList->po_id) {
                $allSizesFromPO = PoItems::where('po_id', $packingList->po_id)
                    ->pluck('size')
                    ->unique()
                    ->sort()
                    ->values();
            }

            // If no sizes found in PO, fallback to current packing list sizes
            if ($allSizesFromPO->isEmpty()) {
                $allSizesFromPO = $allSizes;
            }

            // 2. Compute ordered quantities for all sizes in the PO
            $orderedQuantities = collect();
            if (!empty($uniquePoItemIds)) {
                $poItemsFiltered = PoItems::whereIn('id', $uniquePoItemIds)->get();
                $orderedQuantities = $poItemsFiltered
                    ->groupBy('size')
                    ->map(fn($itemsForSize) => $itemsForSize->sum('qty'));
            } else {
                // If no specific items, get all items for this PO
                if ($packingList->po_id) {
                    $allPoItems = PoItems::where('po_id', $packingList->po_id)->get();
                    $orderedQuantities = $allPoItems
                        ->groupBy('size')
                        ->map(fn($itemsForSize) => $itemsForSize->sum('qty'));
                } else {
                    $orderedQuantities = collect();
                }
            }

            $allSizes = $allSizesFromPO;

            // 3. Determine sizeOrder for any detail table or consistent ordering in summary
            $sizeOrder = [];
            if ($packingList->po_id) {
                $sizeOrder = PoItems::where('po_id', $packingList->po_id)
                    ->pluck('size')
                    ->unique()
                    ->values()
                    ->toArray();
            }
            if (empty($sizeOrder)) {
                $sizeOrder = $allSizes->toArray();
            }

            // 4. (Optional) Build a generic detail tableData if you want similar breakdown as Skechers:
            //    If you do not need a full detail table for other vendors, you can skip building tableData.
            $groupedItems = $packingList->items->groupBy(function ($item) {
                return $item->article_number . '|' . $item->color . '|' . $item->size;
            });

            $tableRows = [];
            $totals = [
                'carton_count' => 0,
                'per_size'     => array_fill_keys($sizeOrder, 0),
                'total_pieces' => 0,
            ];

            foreach ($groupedItems as $groupKey => $groupItems) {
                list($articleNumber, $color, $size) = explode('|', $groupKey);

                // Carton info
                $cartonNames = $groupItems->pluck('carton_name')->unique()->sort()->values();
                $cartonIds   = $groupItems->pluck('carton_id')->unique()->values();
                $cartonCount = $cartonNames->count();

                $firstName = $cartonCount > 0 ? $cartonNames->first() : '';
                $lastName  = $cartonCount > 0 ? $cartonNames->last()  : '';
                $firstCartonId = $cartonCount > 0 ? $cartonIds->first() : null;

                $totalQty = $groupItems->sum('quantity');

                $firstItem = $groupItems->first();
                $carton    = $firstItem->carton;
                // dd($firstItem);
                // Weight/dimension if needed
                $netWeightPerCarton = $firstItem->net_weight ?? 0;
                $grossWeightPerCarton = ($firstItem->net_weight + 1.45) ?? 0;
                // $netWeightPerCarton   = $carton->net_weight ?? 0;
                // $grossWeightPerCarton = $carton->gross_weight ?? 0;
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

                $row = [
                    'article_number'  => $articleNumber,
                    'color'           => $color,
                    'size'            => $size,
                    'ctn_first'       => $firstName,
                    'ctn_last'        => $lastName,
                    'first_carton_id' => $firstCartonId,
                    'ttl_ctn'         => $cartonCount,
                    'per_size'        => array_fill_keys($sizeOrder, 0),
                    'per_ctn'         => $perCartonQty,
                    'total'           => $totalQty,
                    'net_wt_per'      => $netWeightPerCarton,
                    'grs_wt_per'      => $grossWeightPerCarton,
                    'ctn_dim'         => $dimension,
                    'mrp'             => $mrp,
                    'po_item_id'      => $poItemId,
                ];
                $row['per_size'][$size] = $totalQty;

                // Update totals
                $totals['carton_count'] += $cartonCount;
                $totals['per_size'][$size] += $totalQty;
                $totals['total_pieces'] += $totalQty;

                $tableRows[] = $row;
            }

            $tableData = [
                'sizeOrder' => $sizeOrder,
                'rows'      => $tableRows,
                'totals'    => $totals,
            ];

            // Pass totals or leave variables for view consistency
            $totalCtn = $totals['carton_count'];
            // If you have weight totals, compute similarly; otherwise leave as 0
            $totalNetWeight = $totals['total_pieces'] * 0; // or sum from rows if meaningful
            $totalGrossWeight = $totals['total_pieces'] * 0;

            $dispatchQuantities = collect(); // Collection to store dispatch quantities by dispatch number
            $totalDispatches = 0;
            $orderQuantitiesFromAllPacks = collect(); // This will store the total order qty from all packing lists

            if (in_array($packingList->vendor_id, [1, 5, 6])) {
                // Get all packing lists for this PO ordered by ID (chronological order)
                $allPackingLists = PackingListMaster::where('po_id', $packingList->po_id)
                    ->orderBy('id', 'asc')
                    ->get();

                $totalDispatches = $allPackingLists->count();

                // Calculate ORDER QTY from all packing lists for this PO (total packed quantities across all dispatches)
                $allPackingListIds = $allPackingLists->pluck('id')->toArray();
                $allPackingListItems = PackingListItem::whereIn('packing_list_id', $allPackingListIds)->get();

                $orderQuantitiesFromAllPacks = PackingListConfigItem::where('po_id', $packingList->po_id)
                    ->where('status', 0)
                    ->where('color', $packingList->color)
                    ->groupBy('size')
                    ->selectRaw('size, SUM(po_qty) as total_pack_qty')
                    ->pluck('total_pack_qty', 'size');

                // Find the position of current packing list
                $currentPackingListIndex = $allPackingLists->search(function ($item) use ($packingList) {
                    return $item->id == $packingList->id;
                });

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

                        // Calculate quantities by size for this dispatch
                        $dispatchQtyBySize = $packingListItems
                            ->groupBy('size')
                            ->map(function ($items) {
                                return $items->sum('quantity');
                            });

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
            'dispatchQuantities'       => $dispatchQuantities, // Add this
            'totalDispatches'          => $totalDispatches,    // Add this
            'orderQuantitiesFromAllPacks' => $orderQuantitiesFromAllPacks, // Add this
            'currentDispatchNumber'    => $currentDispatchNumber, // Add this
            'totalCbm'    => $totalCbm, // Add this
        ];

        //echo "<pre>".print_r($viewData,true)."</pre>";
        // dd($viewData);

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
