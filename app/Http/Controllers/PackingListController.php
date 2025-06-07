<?php

namespace App\Http\Controllers;

use App\Http\Controllers\BaseController;
use Illuminate\Http\Request;
use App\Models\VendorMaster;
use App\Models\CartonMaster;
use App\Models\PoMaster;
use App\Models\PoItems;
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

        $styleRef = '';
        if ($po->vendor_id == 1) {
            $articleInfo = json_decode($po->article_info, true);
            $styleRef = $articleInfo['Article description'] ?? '';
        } elseif ($po->vendor_id == 3) {
            $articleInfo = json_decode($po->article_info, true);
            $styleRef = $articleInfo['style_description'] ?? '';
        }

        $poItems = PoItems::where('po_id', $po->id)->get();

        $colorSizeMatrix = [];
        $allSizes = [];

        foreach ($poItems as $item) {
            // Use both color columns - prioritize 'color' over 'id_color'
            $color = $item->color ?? $item->id_color ?? 'N/A';
            $size = $item->size ?? 'N/A';
            $qty = $item->qty ?? 0;

            if (!in_array($size, $allSizes)) {
                $allSizes[] = $size;
            }

            // Initialize if not exists
            if (!isset($colorSizeMatrix[$color][$size])) {
                $colorSizeMatrix[$color][$size] = 0;
            }

            // Add quantity (in case there are multiple items with same color/size)
            $colorSizeMatrix[$color][$size] += $qty;
        }

        // Get excess percentage from vendor
        $excessPercentage = $po->vendor->excess ?? 0;
        $packQtyMatrix = [];
        $totalPackQty = 0;

        // Calculate pack quantities for each color and size
        foreach ($colorSizeMatrix as $color => $sizes) {
            foreach ($sizes as $size => $qty) {
                // Calculate pack qty: original qty + excess percentage, then ceil
                $packQty = ceil($qty + ($qty * $excessPercentage / 100));
                $packQtyMatrix[$color][$size] = $packQty;
                $totalPackQty += $packQty;
            }
        }

        // Calculate totals by size for PO and Pack quantities
        $poQtyBySizeTotal = [];
        $packQtyBySizeTotal = [];

        foreach ($allSizes as $size) {
            $poQtyBySizeTotal[$size] = 0;
            $packQtyBySizeTotal[$size] = 0;

            foreach ($colorSizeMatrix as $color => $sizes) {
                $poQtyBySizeTotal[$size] += $sizes[$size] ?? 0;
            }

            foreach ($packQtyMatrix as $color => $sizes) {
                $packQtyBySizeTotal[$size] += $sizes[$size] ?? 0;
            }
        }

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
            'packQtyBySizeTotal'
        ));
    }

    public function save_config_po_details(Request $request)
    {
        try {
            $po_id = $request->input('po_id');
            $carton_id = $request->input('carton_id');

            // Get PO items to process
            $po = PoMaster::with('vendor')->find($po_id);
            if (!$po) {
                return response()->json(['error' => 'PO not found'], 404);
            }

            $excess = $po->vendor->excess ?? 0;
            $shortage = $po->vendor->shortage ?? 0;
            $vendor_id = $po->vendor_id;

            // Create single master record for the PO
            $configMaster = PackingListConfigMaster::create([
                'po_id' => $po_id,
                'vendor_id' => $vendor_id,
                'carton_id' => $carton_id,
                'excess' => $excess,
                'shortage' => $shortage,
                'created_by' => auth()->user()->id,
                'created_at' => now(),
                'status' => 0,
            ]);

            $poItems = PoItems::where('po_id', $po_id)->get();

            // Create config items for each po item
            foreach ($poItems as $poItem) {
                $color = $poItem->color ?? $poItem->id_color ?? 'N/A';
                $poQty = $poItem->qty ?? 0;

                // Calculate pack qty using excess percentage from vendor
                $excessPercentage = $excess;
                $packQty = ceil($poQty + ($poQty * $excessPercentage / 100));

                PackingListConfigItem::create([
                    'config_id' => $configMaster->id,
                    'po_item_id' => $poItem->id,
                    'color' => $color,
                    'size' => $poItem->size ?? 'N/A',
                    'po_qty' => $poQty,
                    'pack_qty' => $packQty,
                    'created_by' => auth()->user()->id,
                    'created_at' => now(),
                    'status' => 0,
                ]);
            }

            return response()->json([
                'success' => true,
                'message' => 'Packing list configuration saved successfully'
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
            'po_date_formatted' => \Carbon\Carbon::parse($po->po_date)->format('d-m-Y'),
            'vendor_name' => $po->vendor ? $po->vendor->name : 'N/A',
            'excess' => $po->vendor->excess,
            'vendor_id' => $po->vendor_id
        ]);
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
        $poId = $request->input('po_id');
        $article = $request->input('article_number');

        $configItems = PackingListConfigItem::whereHas('config', function ($query) use ($poId) {
            $query->where('po_id', $poId);
        })
            ->whereHas('poItem', function ($query) use ($article) {
                $query->where('article_number', $article);
            })
            ->with(['poItem', 'config'])
            ->get();

        $sizes = [];
        foreach ($configItems as $item) {
            $maxQty = $item->pack_qty;

            $packedQty = PackingListItem::whereHas('packingList', function ($query) use ($poId) {
                $query->where('po_id', $poId);
            })
                ->where('article_number', $article)
                ->where('size', $item->size)
                ->sum('quantity');

            $remainingQty = $maxQty - $packedQty;

            if ($remainingQty <= 0) {
                continue; // Skip fully packed sizes
            }

            $sizes[] = [
                'size' => $item->size,
                'max_qty' => $maxQty,
                'packed_qty' => $packedQty,
                'remaining_qty' => $remainingQty,
                'config_item_id' => $item->id
            ];
        }

        return response()->json($sizes);
    }

    public function get_packing_list_items(Request $request)
    {
        $poId = $request->input('po_id');

        $packingList = PackingListMaster::where('po_id', $poId)->first();

        if (!$packingList) {
            return response()->json([]);
        }

        $items = PackingListItem::where('packing_list_id', $packingList->id)
            ->with('carton:id')
            ->get()
            ->map(function ($item) {
                return [
                    'id' => $item->id,
                    'carton_name' => $item->carton_name ?? 'N/A',
                    'article_number' => $item->article_number,
                    'size' => $item->size,
                    'quantity' => $item->quantity,
                    'carton_id' => $item->carton_id
                ];
            });

        return response()->json($items);
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
        $poId = $request->input('id');
        $vendorId = $request->vendor_id;
        $color = $request->color;

        $po = PoMaster::find($poId);

        $job_num = $po->po_job_num;

        $packingConfig = PackingListConfigMaster::where('po_id', $poId)->first();

        if (!$packingConfig) {
            return response()->json(['error' => 'Packing list configuration not found for this PO'], 404);
        }

        $carton = CartonMaster::where('id', $packingConfig->carton_id)
            ->where('vendor_id', $vendorId)
            ->where('status', 0)
            ->first();

        $carton_id = $carton->id;

        // Get all relevant config items
        $configItems = PackingListConfigItem::whereHas('config', function ($query) use ($poId) {
            $query->where('po_id', $poId);
        })
            ->where('color', $color)
            ->with('poItem')
            ->get();

        $filteredArticles = collect();

        // Group by article_number, size, and color
        $grouped = $configItems->groupBy(function ($item) {
            return $item->poItem->article_number . '|' . $item->size . '|' . $item->color;
        });

        foreach ($grouped as $group) {
            $poItem = $group->first()->poItem;
            if (!$poItem) continue;

            $articleNumber = $poItem->article_number;
            $size = $group->first()->size;
            $poItemIds = $group->pluck('po_item_id');

            $totalPackQty = $group->sum('pack_qty');

            $actualPackedQty = PackingListItem::whereIn('po_item_id', $poItemIds)
                ->where('size', $size)
                ->sum('quantity');

            if ($actualPackedQty < $totalPackQty) {
                $filteredArticles->push($articleNumber);
            }
        }

        $articles = $filteredArticles->unique()->values();

        return view('packing_list.item_add', compact('carton_id', 'poId', 'color', 'articles', 'job_num'));
    }

    public function item_store(Request $request)
    {
        $validated = $request->validate([
            'po_id' => 'required|exists:po_masters,id',
            'carton_id' => 'required|exists:carton_master,id',
            'article_number' => 'required',
            'color' => 'required',
            'size' => 'required',
            'quantity' => 'required|integer|min:1',
            'config_item_id' => 'required'
        ]);

        try {
            // Check if quantity exceeds available limit
            $configItem = PackingListConfigItem::find($request->config_item_id);

            if (!$configItem) {
                return response()->json(['error' => 'Configuration item not found'], 400);
            }

            // Calculate already packed quantity
            $packedQty = PackingListItem::whereHas('packingList', function ($query) use ($request) {
                $query->where('po_id', $request->po_id);
            })
                ->where('article_number', $request->article_number)
                ->where('size', $request->size)
                ->sum('quantity');

            $remainingQty = $configItem->pack_qty - $packedQty;

            if ($request->quantity > $remainingQty) {
                return response()->json([
                    'error' => "Quantity exceeds available limit. Available: {$remainingQty}"
                ], 400);
            }

            $po = PoMaster::with('vendor')->find($request->po_id);

            $packingList = PackingListMaster::firstOrCreate(
                ['po_id' => $request->po_id],
                [
                    'vendor_id' => $po->vendor_id,
                    'po_no' => $po->po_num,
                    'po_date' => $po->po_date,
                    'created_by' => auth()->id()
                ]
            );

            // Generate carton name based on vendor ID
            $cartonName = $this->generateCartonName($po->vendor_id, $packingList->id, $request->size, $request->article_number);

            $poItem = PoItems::where('po_id', $request->po_id)
                ->where('article_number', $request->article_number)
                ->where('size', $request->size)
                ->first();

            PackingListItem::create([
                'packing_list_id' => $packingList->id,
                'po_item_id' => $poItem->id,
                'carton_id' => $request->carton_id,
                'carton_name' => $cartonName,
                'article_number' => $request->article_number,
                'size' => $request->size,
                'quantity' => $request->quantity,
                'created_by' => auth()->id()
            ]);

            return response()->json([
                'success' => true,
                'po_id' => $validated['po_id']
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function item_edit(Request $request)
    {
        $itemId = $request->input('id');
        $poId = $request->input('po_id');

        $po = PoMaster::find($poId);

        $job_num = $po->po_job_num;

        $item = PackingListItem::find($itemId);
        if (!$item) {
            return response()->json(['error' => 'Item not found'], 404);
        }

        $configItem = PackingListConfigItem::where('po_item_id', $item->po_item_id)
            ->first();

        if ($configItem) {
            $color = $configItem->color;
        }

        // Get the vendor_id from PO
        $po = PoMaster::find($poId);
        $vendorId = $po->vendor_id;

        // Get configured cartons for this PO
        $configuredCartonIds = PackingListConfigMaster::where('po_id', $poId)
            ->pluck('carton_id')
            ->unique();

        $carton = CartonMaster::where('id', $configuredCartonIds)
            ->where('vendor_id', $vendorId)
            ->where('status', 0)
            ->first();

        $carton_id = $carton->id;

        // Get filtered articles (not fully packed)
        $configItems = PackingListConfigItem::whereHas('config', function ($query) use ($poId) {
            $query->where('po_id', $poId);
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
            if (!$poItem) continue;

            $articleNumber = $poItem->article_number;
            $size = $group->first()->size;
            $poItemIds = $group->pluck('po_item_id');

            $totalPackQty = $group->sum('pack_qty');

            $actualPackedQty = PackingListItem::whereIn('po_item_id', $poItemIds)
                ->where('size', $size)
                ->where('id', '!=', $item->id) // exclude current item
                ->sum('quantity');

            if ($actualPackedQty < $totalPackQty) {
                $filteredArticles->push($articleNumber);
            }
        }

        $articles = $filteredArticles->unique()->values();

        // Get sizes with quantities for the selected article and color
        $sizes = PackingListConfigItem::whereHas('config', function ($query) use ($poId) {
            $query->where('po_id', $poId);
        })
            ->where('color', $color)
            ->whereHas('poItem', function ($query) use ($item) {
                $query->where('article_number', $item->article_number);
            })
            ->with(['poItem', 'config'])
            ->get();

        $sizesWithQty = [];
        foreach ($sizes as $configItem) {
            $maxQty = $configItem->pack_qty;

            $packedQty = PackingListItem::whereHas('packingList', function ($query) use ($poId) {
                $query->where('po_id', $poId);
            })
                ->where('article_number', $item->article_number)
                ->where('size', $configItem->size)
                ->where('id', '!=', $item->id) // exclude current item
                ->sum('quantity');

            $remainingQty = $maxQty - $packedQty;

            // Skip fully packed sizes *except* the size of the item being edited
            if ($remainingQty <= 0 && $item->size !== $configItem->size) {
                continue;
            }

            $sizesWithQty[] = [
                'size' => $configItem->size,
                'max_qty' => $maxQty,
                'packed_qty' => $packedQty,
                'remaining_qty' => max(0, $remainingQty),
                'config_item_id' => $configItem->id
            ];
        }

        return view('packing_list.item_edit', compact(
            'carton_id',
            'articles',
            'poId',
            'item',
            'sizesWithQty',
            'color',
            'job_num'
        ));
    }

    public function item_update(Request $request)
    {
        $validated = $request->validate([
            'id' => 'required|exists:packing_list_items,id',
            'po_id' => 'required|exists:po_masters,id',
            'carton_id' => 'required|exists:carton_master,id',
            'article_number' => 'required',
            'color' => 'required',
            'size' => 'required',
            'quantity' => 'required|integer|min:1',
            'config_item_id' => 'required'
        ]);

        try {
            $item = PackingListItem::find($validated['id']);

            // Check if quantity exceeds available limit
            $configItem = PackingListConfigItem::find($request->config_item_id);

            if (!$configItem) {
                return response()->json(['error' => 'Configuration item not found'], 400);
            }

            // Calculate already packed quantity (excluding current item)
            $packedQty = PackingListItem::whereHas('packingList', function ($query) use ($request) {
                $query->where('po_id', $request->po_id);
            })
                ->where('article_number', $request->article_number)
                ->where('size', $request->size)
                ->where('id', '!=', $item->id)
                ->sum('quantity');

            $remainingQty = $configItem->pack_qty - $packedQty;

            if ($request->quantity > $remainingQty) {
                return response()->json([
                    'error' => "Quantity exceeds available limit. Available: {$remainingQty}"
                ], 400);
            }

            $poItem = PoItems::where('po_id', $request->po_id)
                ->where('article_number', $request->article_number)
                ->where('size', $request->size)
                ->first();

            // Get vendor ID from PO
            $po = PoMaster::find($request->po_id);

            // Generate new carton name if carton_id changed
            $cartonName = $item->carton_name;
            if ($item->carton_id != $request->carton_id) {
                $cartonName = $this->generateCartonName($po->vendor_id, $item->packing_list_id, $request->size, $request->article_number);
            }

            $item->update([
                'po_item_id' => $poItem->id,
                'carton_id' => $request->carton_id,
                'carton_name' => $cartonName,
                'article_number' => $request->article_number,
                'size' => $request->size,
                'quantity' => $request->quantity,
                'updated_by' => auth()->id()
            ]);

            return response()->json([
                'success' => true,
                'po_id' => $validated['po_id']
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    private function generateCartonName($vendorId, $packingListId, $size, $articleNumber)
    {
        if ($vendorId == 1) {
            // First, check if a carton already exists with the same size and article number
            $existingCarton = PackingListItem::where('packing_list_id', $packingListId)
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
        $packingList = PackingListMaster::with([
            'items.carton',
            'items.po_item',
            'vendor',
            'po'
        ])->find($id);

        if (!$packingList) {
            abort(404, 'Packing list not found');
        }

        $allSizes = $packingList->items->pluck('size')->unique()->sort()->values();

        $packedQuantities = $packingList->items->groupBy('size')->map(function ($items) {
            return $items->sum('quantity');
        });

        $orderedQuantities = collect();
        if ($packingList->po_id) {
            $poItems = PoItems::where('po_id', $packingList->po_id)->get();
            $orderedQuantities = $poItems->groupBy('size')->map(function ($items) {
                return $items->sum('qty');
            });
        }

        $balances = collect();
        $percentages = collect();

        foreach ($allSizes as $size) {
            $ordered = $orderedQuantities->get($size, 0);
            $packed = $packedQuantities->get($size, 0);
            $balance = $ordered - $packed;
            $percentage = $ordered > 0 ? ($packed / $ordered) * 100 : 0;

            $balances[$size] = $balance;
            $percentages[$size] = $percentage;
        }

        // Initialize table data variable
        $tableData = null;

        // PUMA-specific table generation
        if ($packingList->vendor_id == 3) {
            // Get dynamic size order from PO items instead of static array
            $sizeOrder = [];
            if ($packingList->po_id) {
                $sizeOrder = PoItems::where('po_id', $packingList->po_id)
                    ->pluck('size')
                    ->unique()
                    ->values()
                    ->toArray();
            }

            // If no sizes found from PO, fall back to packing list sizes
            if (empty($sizeOrder)) {
                $sizeOrder = $allSizes->toArray();
            }

            // Group items by size (not by carton content pattern)
            $sizeGroups = $packingList->items->groupBy('size');

            // Build table rows - one row per size
            $tableRows = [];

            foreach ($sizeOrder as $size) {
                if (!$sizeGroups->has($size)) {
                    continue;
                }

                $sizeItems = $sizeGroups->get($size);

                // Get all carton names for this size and sort them numerically
                $cartonNames = $sizeItems->pluck('carton_name')->unique()->values();

                $cartonCount = $cartonNames->count();

                $ctnRange = '';
                if ($cartonCount > 0) {
                    $firstName = $cartonNames->first();
                    $lastName = $cartonNames->last();

                    $ctnRange = ($cartonCount == 1)
                        ? $firstName
                        : $firstName . '-' . $lastName;
                }

                // Get total quantity for this size
                $totalQty = $sizeItems->sum('quantity');

                // Get carton details (use first carton's details for weight and dimensions)
                $firstItem = $sizeItems->first();
                $carton = $firstItem->carton;

                // Calculate totals
                $netWeightPerCarton = $carton->net_weight ?? 0;
                $grossWeightPerCarton = $carton->gross_weight ?? 0;
                $totalNetWeight = $netWeightPerCarton * $cartonCount;
                $totalGrossWeight = $grossWeightPerCarton * $cartonCount;

                // Format dimensions as L*B*H CMS
                $dimension = '';
                if (($carton->length ?? 0) > 0 || ($carton->breadth ?? 0) > 0 || ($carton->height ?? 0) > 0) {
                    $dimension = 'L' . ($carton->length ?? 0) . '*B' . ($carton->breadth ?? 0) . '*H' . ($carton->height ?? 0) . 'CMS';
                }

                // Calculate per carton quantity (total qty divided by number of cartons)
                $perCartonQty = $cartonCount > 0 ? round($totalQty / $cartonCount) : 0;

                $row = [
                    'ctn_range' => $ctnRange,
                    'ttl_ctn' => $cartonCount,
                    'color' => $firstItem->po_item->id_color ?? '',
                    'per_size' => [],
                    'per_ctn' => $perCartonQty,
                    'total' => $totalQty,
                    'net_wt_per' => $netWeightPerCarton,
                    'grs_wt_per' => $grossWeightPerCarton,
                    'net_wt_total' => $totalNetWeight,
                    'grs_wt_total' => $totalGrossWeight,
                    'ctn_dim' => $dimension
                ];

                // Fill size quantities - only current size will have quantity, others will be 0
                foreach ($sizeOrder as $sizeCol) {
                    $row['per_size'][$sizeCol] = ($sizeCol == $size) ? $totalQty : 0;
                }

                $tableRows[] = $row;
            }

            $tableData = [
                'sizeOrder' => $sizeOrder,
                'rows' => $tableRows
            ];
        }

        // Determine template based on vendor
        $viewTemplate = $packingList->vendor_id == 3
            ? 'packing_list.puma_print'
            : 'packing_list.jack_print';

        $pdf = PDF::loadView($viewTemplate, [
            'packing_list' => $packingList,
            'all_sizes' => $allSizes,
            'packed_quantities' => $packedQuantities,
            'ordered_quantities' => $orderedQuantities,
            'balances' => $balances,
            'percentages' => $percentages,
            'tableData' => $tableData  // Only used by PUMA template
        ])
            ->set_option('isHtml5ParserEnabled', true)
            ->set_option('isRemoteEnabled', true)
            ->setPaper('a4', 'landscape');

        return $pdf->stream('Packing_list_print.pdf');
    }
}
